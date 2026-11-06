<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormData;
use App\Admin\Schema;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * One controller for every content table listed in App\Admin\Schema::resources().
 */
class ResourceController extends Controller
{
    public function index(Request $request, string $resource): View
    {
        $def = Schema::resource($resource);
        $query = $def['model']::query()->with($def['with'] ?? []);

        if ($search = trim((string) $request->query('q'))) {
            $first = $def['fields'][0]['key'];
            $query->where($first, 'like', '%'.$search.'%');
        }

        if (! empty($def['order_by'])) {
            $query->orderBy(...$def['order_by']);
        } elseif (! empty($def['sortable'])) {
            $query->orderBy('sort_order')->orderBy('id');
        } else {
            $query->latest('id');
        }

        return view('admin.resource.index', [
            'key' => $resource,
            'def' => $def,
            'items' => $query->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(string $resource): View
    {
        $def = Schema::resource($resource);
        $defaults = [];
        foreach ($def['fields'] as $f) {
            if (array_key_exists('default', $f)) {
                $defaults[$f['key']] = $f['default'];
            }
        }
        if (isset($def['model']) && in_array('published_at', array_column($def['fields'], 'key'), true)) {
            $defaults['published_at'] = now()->format('Y-m-d\TH:i');
        }

        return $this->form($resource, $def, null, $defaults);
    }

    public function edit(string $resource, int $id): View
    {
        $def = Schema::resource($resource);
        $item = $def['model']::findOrFail($id);

        $values = [];
        foreach ($def['fields'] as $f) {
            $v = $item->{$f['key']};
            if (is_array($v) && in_array($f['type'], ['text', 'textarea'], true)) {
                $v = implode(', ', array_map(fn ($x) => is_scalar($x) ? (string) $x : '', $v));
            }
            $values[$f['key']] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d\TH:i') : $v;
        }

        return $this->form($resource, $def, $item, $values);
    }

    public function store(Request $request, string $resource, FormData $form): RedirectResponse
    {
        $def = Schema::resource($resource);

        return $this->save($request, $resource, $def, new $def['model'], $form);
    }

    public function update(Request $request, string $resource, int $id, FormData $form): RedirectResponse
    {
        $def = Schema::resource($resource);

        return $this->save($request, $resource, $def, $def['model']::findOrFail($id), $form);
    }

    /** Copy a record as a new draft (products, projects …). */
    public function duplicate(string $resource, int $id): RedirectResponse
    {
        $def = Schema::resource($resource);
        $item = $def['model']::findOrFail($id);
        $copy = $item->replicate();
        $titleKey = $def['slug_from'] ?? $def['fields'][0]['key'];
        $copy->{$titleKey} = $item->{$titleKey}.' (copy)';
        if (isset($def['slug_from'])) {
            $copy->slug = $this->uniqueSlug($def['model'], (string) $copy->{$titleKey}, null);
        }
        if (array_key_exists('status', $copy->getAttributes())) {
            $copy->status = 'draft';
        }
        if (array_key_exists('published_at', $copy->getAttributes())) {
            $copy->published_at = null;
        }
        $copy->save();

        return redirect()->route('admin.resource.edit', [$resource, $copy->id])->with('status', 'কপি তৈরি হয়েছে (Draft)।');
    }

    public function destroy(string $resource, int $id): RedirectResponse
    {
        $def = Schema::resource($resource);
        $def['model']::findOrFail($id)->delete();

        return redirect()->route('admin.resource.index', $resource)->with('status', "{$def['singular']} মুছে ফেলা হয়েছে।");
    }

    protected function form(string $resource, array $def, ?Model $item, array $values): View
    {
        return view('admin.resource.form', [
            'key' => $resource,
            'def' => $def,
            'item' => $item,
            'values' => old('data', $values),
        ]);
    }

    protected function save(Request $request, string $resource, array $def, Model $item, FormData $form): RedirectResponse
    {
        $input = (array) $request->input('data', []);
        $files = (array) $request->file('data_files', []);

        if (! $form->validate($def['fields'], $input, $files)) {
            return back()->withInput()->withErrors($form->errors);
        }

        $values = $form->values($def['fields'], $input, $files);

        // "Add new category" style fields
        foreach ($def['fields'] as $f) {
            if ($f['type'] === 'model_select' && ! empty($f['allow_new']) && filled($new = trim((string) ($input[$f['key'].'_new'] ?? '')))) {
                $related = $f['model']::firstOrCreate(['slug' => Str::slug($new)], [$f['display'] => $new]);
                $values[$f['key']] = $related->id;
            } elseif ($f['type'] === 'model_select' && blank($values[$f['key']])) {
                $values[$f['key']] = null;
            }
        }

        $item->fill($values);

        if (! empty($def['slug_from']) && blank($item->slug)) {
            $item->slug = $this->uniqueSlug($def['model'], (string) $item->{$def['slug_from']}, $item->id);
        }

        if ($resource === 'posts') {
            $item->user_id ??= $request->user()->id;
            if ($item->status === 'published' && ! $item->published_at) {
                $item->published_at = now();
            }
        }

        $item->save();

        $route = $request->has('save_and_new')
            ? route('admin.resource.create', $resource)
            : route('admin.resource.edit', [$resource, $item->id]);

        return redirect($route)->with('status', "{$def['singular']} সেভ হয়েছে।");
    }

    protected function uniqueSlug(string $model, string $text, ?int $ignoreId): string
    {
        $base = Str::slug($text) ?: Str::lower(Str::random(8));
        $slug = $base;
        $n = 2;
        while ($model::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
