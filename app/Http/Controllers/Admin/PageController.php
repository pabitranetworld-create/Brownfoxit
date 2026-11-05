<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\PageBuilder\LayoutValidator;
use App\PageBuilder\Registry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $show = $request->query('show') === 'templates' ? 'templates' : 'pages';
        $q = trim((string) $request->query('q'));

        $pages = Page::query()
            ->where('is_template', $show === 'templates')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
            ->orderBy('title')
            ->paginate(30)->withQueryString();

        return view('admin.pages.index', compact('pages', 'show', 'q') + [
            'templates' => Page::where('is_template', true)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, LayoutValidator $validator): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190'],
            'template_id' => ['nullable', 'string', 'max:20'],
        ], ['title.required' => 'পেজের নাম দিন।']);

        if (filled($data['slug']) && ($error = $this->slugProblem($this->slug($data['slug'])))) {
            return back()->withInput()->withErrors(['slug' => $error]);
        }
        $slug = $this->uniqueSlug($data['slug'] ?: $data['title']);

        $layout = LayoutValidator::empty();
        $template = 'default';
        $start = (string) ($data['template_id'] ?? '');
        if ($start === 'html') {
            // Blank canvas with one edge-to-edge HTML block: build any custom page with code.
            $template = 'canvas';
            $layout = $validator->clean(['version' => 1, 'sections' => [[
                'id' => 'htmlsection', 'settings' => ['width' => 'bleed', 'pt' => 0, 'pb' => 0, 'gap' => 'none', 'anim' => 'none'],
                'columns' => [['id' => 'htmlcolumn', 'settings' => ['w' => 100, 'anim' => 'none'], 'widgets' => [[
                    'id' => 'htmlwidget1', 'type' => 'html',
                    'data' => ['code' => "<div class=\"my-page\">\n  <h1>My custom page</h1>\n  <p>Write any HTML here. CSS and JavaScript go in the next boxes.</p>\n</div>", 'css' => ".my-page { min-height: 100vh; display: grid; place-content: center; text-align: center; padding: 40px; }\n.my-page h1 { font-size: 56px; color: #fff; }", 'js' => '', 'wrap' => 'none'],
                    'settings' => ['anim' => 'none'],
                ]]]],
            ]]]);
        } elseif (ctype_digit($start) && ($tpl = Page::find((int) $start))) {
            $layout = $validator->clean($tpl->workingLayout());
            $template = $tpl->template ?: 'default';
        }

        $page = Page::create([
            'title' => $data['title'],
            'slug' => $slug,
            'status' => Page::STATUS_DRAFT,
            'template' => $template,
            'layout' => $layout,
            'show_page_header' => $template === 'default',
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.pages.builder', $page);
    }

    public function builder(Page $page, LayoutValidator $validator): View
    {
        return view('admin.pages.builder', [
            'page' => $page,
            'layout' => $validator->clean($page->workingLayout()),
            'defs' => Registry::forEditor(),
            'pageData' => [
                'title' => $page->title, 'slug' => $page->slug, 'status' => $page->status, 'is_template' => $page->is_template,
                'seo_title' => $page->seo_title, 'seo_description' => $page->seo_description, 'og_image' => $page->og_image,
                'noindex' => $page->noindex, 'show_page_header' => $page->show_page_header, 'header_subtitle' => $page->header_subtitle,
                'template' => $page->template ?: 'default', 'custom_head' => $page->custom_head, 'custom_css' => $page->custom_css, 'custom_js' => $page->custom_js,
                'unpublished' => $page->hasUnpublishedChanges(),
            ],
            'templates' => Page::TEMPLATES,
        ]);
    }

    /** Save draft (and page settings). The live page does not change. */
    public function save(Request $request, Page $page, LayoutValidator $validator): JsonResponse
    {
        $settings = $this->validateSettings($request, $page);
        if ($settings instanceof JsonResponse) {
            return $settings;
        }

        $layout = $validator->clean(json_decode((string) $request->input('layout'), true));
        $page->fill($settings);
        $page->draft_layout = $layout;
        $page->updated_by = $request->user()->id;
        $page->save();
        $this->revision($page, $layout, 'draft saved');

        return response()->json(['ok' => true, 'message' => 'Draft সেভ হয়েছে', 'layout' => $layout, 'status' => $page->status, 'slug' => $page->slug, 'url' => $page->url()]);
    }

    public function publish(Request $request, Page $page, LayoutValidator $validator): JsonResponse
    {
        $settings = $this->validateSettings($request, $page);
        if ($settings instanceof JsonResponse) {
            return $settings;
        }

        $layout = $request->filled('layout')
            ? $validator->clean(json_decode((string) $request->input('layout'), true))
            : $validator->clean($page->workingLayout());

        $page->fill($settings);
        $page->layout = $layout;
        $page->draft_layout = null;
        $page->status = Page::STATUS_PUBLISHED;
        $page->published_at ??= now();
        $page->updated_by = $request->user()->id;
        $page->save();
        $this->revision($page, $layout, 'published');

        return response()->json(['ok' => true, 'message' => $page->is_template ? 'Template সেভ হয়েছে' : 'পেজ প্রকাশিত হয়েছে', 'layout' => $layout, 'status' => $page->status, 'slug' => $page->slug, 'url' => $page->url()]);
    }

    public function unpublish(Page $page): RedirectResponse|JsonResponse
    {
        $page->update(['status' => Page::STATUS_DRAFT, 'draft_layout' => $page->draft_layout ?? $page->layout]);

        return request()->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'পেজ সাইট থেকে সরানো হয়েছে (Draft)', 'status' => $page->status])
            : back()->with('status', "\"{$page->title}\" এখন Draft, সাইটে দেখা যাবে না।");
    }

    public function duplicate(Request $request, Page $page): RedirectResponse
    {
        $copy = $page->replicate(['published_at']);
        $copy->title = $page->title.' (Copy)';
        $copy->slug = $this->uniqueSlug($page->slug.'-copy');
        $copy->status = Page::STATUS_DRAFT;
        $copy->layout = $page->workingLayout();
        $copy->draft_layout = null;
        $copy->is_template = $request->boolean('as_template');
        if ($copy->is_template) {
            $copy->title = $page->title.' (Template)';
            $copy->slug = $this->uniqueSlug('template-'.$page->slug);
        }
        $copy->save();

        return redirect()->route('admin.pages.builder', $copy)
            ->with('status', $copy->is_template ? 'Template তৈরি হয়েছে।' : 'পেজের কপি তৈরি হয়েছে (Draft)।');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index', $page->is_template ? ['show' => 'templates'] : [])->with('status', "\"{$page->title}\" মুছে ফেলা হয়েছে।");
    }

    /** Live preview used by the builder iframe (POST = unsaved layout). */
    public function preview(Request $request, Page $page, LayoutValidator $validator): View
    {
        $layout = $request->isMethod('post')
            ? $validator->clean(json_decode((string) $request->input('layout'), true))
            : $validator->clean($page->workingLayout());

        if ($request->isMethod('post') && $request->has('page')) {
            $p = (array) $request->input('page');
            $page->title = (string) ($p['title'] ?? $page->title);
            $page->show_page_header = filter_var($p['show_page_header'] ?? $page->show_page_header, FILTER_VALIDATE_BOOLEAN);
            $page->header_subtitle = $p['header_subtitle'] ?? $page->header_subtitle;
            foreach (['template', 'custom_head', 'custom_css', 'custom_js'] as $k) {
                if (array_key_exists($k, $p)) {
                    $page->{$k} = $k === 'template' && ! isset(Page::TEMPLATES[$p[$k]]) ? 'default' : $p[$k];
                }
            }
        }

        return view('pages.show', [
            'page' => $page,
            'layout' => $layout,
            'preview' => true,
            'editor' => $request->boolean('editor'),
        ]);
    }

    public function revisions(Page $page): JsonResponse
    {
        return response()->json($page->revisions()->with('user:id,name')->take(PageRevision::KEEP)->get()
            ->map(fn ($r) => ['id' => $r->id, 'note' => $r->note, 'when' => $r->created_at->diffForHumans(), 'date' => $r->created_at->format('d M Y, h:i A'), 'user' => $r->user?->name]));
    }

    public function restore(Page $page, PageRevision $revision, LayoutValidator $validator): JsonResponse
    {
        abort_unless($revision->page_id === $page->id, 404);
        $layout = $validator->clean($revision->layout);
        $page->update(['draft_layout' => $layout]);
        $this->revision($page, $layout, 'restored');

        return response()->json(['ok' => true, 'message' => 'পুরোনো ভার্সন ফিরিয়ে আনা হয়েছে (Draft হিসেবে)। প্রকাশ করতে Publish চাপুন।', 'layout' => $layout]);
    }

    /** The builder reports failed requests / JS errors here so they show up in the server log. */
    public function clientLog(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::warning('Builder client problem', [
            'user' => $request->user()?->id,
            'what' => Str::limit((string) $request->input('what'), 200),
            'status' => (int) $request->input('status'),
            'url' => Str::limit((string) $request->input('url'), 300),
            'detail' => Str::limit((string) $request->input('detail'), 1500),
            'agent' => Str::limit((string) $request->userAgent(), 200),
        ]);

        return response()->json(['ok' => true]);
    }

    /* ------------------------------------------------------------- */

    protected function validateSettings(Request $request, Page $page): array|JsonResponse
    {
        $p = (array) $request->input('page', []);
        if (! $p) {
            return [];
        }
        $p['slug'] = $this->slug($p['slug'] ?? $page->slug);

        $v = validator($p, [
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'string', 'max:190', Rule::unique('pages', 'slug')->ignore($page->id)],
            'seo_title' => ['nullable', 'string', 'max:190'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:255', 'regex:#^(uploads/[A-Za-z0-9_\-./]+|https://\S+)$#'],
            'header_subtitle' => ['nullable', 'string', 'max:300'],
            'template' => ['nullable', Rule::in(array_keys(Page::TEMPLATES))],
            'custom_head' => ['nullable', 'string', 'max:200000'],
            'custom_css' => ['nullable', 'string', 'max:1000000'],
            'custom_js' => ['nullable', 'string', 'max:1000000'],
        ], [
            'title.required' => 'পেজের নাম দিন।',
            'slug.unique' => 'এই URL অন্য একটা পেজে ব্যবহার হচ্ছে।',
        ]);

        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }
        if (! $page->is_template && ($error = $this->slugProblem($p['slug']))) {
            return response()->json(['ok' => false, 'message' => $error], 422);
        }

        return [
            'title' => $p['title'],
            'slug' => $p['slug'],
            'seo_title' => $p['seo_title'] ?? null,
            'seo_description' => $p['seo_description'] ?? null,
            'og_image' => $p['og_image'] ?? null,
            'noindex' => filter_var($p['noindex'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'show_page_header' => filter_var($p['show_page_header'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'header_subtitle' => $p['header_subtitle'] ?? null,
            'template' => $p['template'] ?? $page->template ?? 'default',
            'custom_head' => array_key_exists('custom_head', $p) ? $p['custom_head'] : $page->custom_head,
            'custom_css' => array_key_exists('custom_css', $p) ? $p['custom_css'] : $page->custom_css,
            'custom_js' => array_key_exists('custom_js', $p) ? $p['custom_js'] : $page->custom_js,
        ];
    }

    protected function slug(string $text): string
    {
        return Str::slug($text) ?: 'page';
    }

    /** A free slug for new pages and copies: "about", "about-2", … */
    protected function uniqueSlug(string $text): string
    {
        $base = $this->slug($text);
        $slug = $base;
        $n = 2;
        while (Page::where('slug', $slug)->exists() || in_array($slug, Page::RESERVED, true)) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    protected function slugProblem(string $slug): ?string
    {
        return in_array($slug, Page::RESERVED, true) ? "\"{$slug}\" URL-টা সাইটের নিজের কাজে সংরক্ষিত, অন্য নাম দিন।" : null;
    }

    protected function revision(Page $page, array $layout, string $note): void
    {
        $last = $page->revisions()->first();
        if ($last && $last->layout == $layout && $last->note === $note) {
            return; // nothing changed
        }
        $page->revisions()->create(['title' => $page->title, 'layout' => $layout, 'note' => $note, 'user_id' => auth()->id()]);
        $old = $page->revisions()->skip(PageRevision::KEEP)->take(1000)->pluck('id');
        if ($old->isNotEmpty()) {
            PageRevision::whereIn('id', $old)->delete();
        }
    }
}
