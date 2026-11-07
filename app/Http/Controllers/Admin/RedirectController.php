<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use App\Seo\PageFetcher;
use App\Seo\WordPressRedirects;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $redirects = Redirect::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('from_path', 'like', "%{$q}%")->orWhere('to_url', 'like', "%{$q}%")))
            ->when($request->query('source'), fn ($w, $s) => $w->where('source', $s))
            ->orderByDesc('id')->paginate(50)->withQueryString();
        $problems = [];
        foreach ($redirects as $r) {
            [$final, $hops, $loop] = Redirect::follow($r->to_url, $r->from_path);
            if ($r->is_active && ($loop || $hops)) {
                $problems[$r->id] = $loop ? 'Loop!' : 'Chain: ends at '.$final;
            }
        }

        return view('admin.redirects.index', [
            'redirects' => $redirects, 'q' => $q, 'problems' => $problems,
            'suggestions' => Cache::get('wp_redirect_suggestions'),
            'oldSite' => Cache::get('wp_redirect_site', 'https://brownfoxit.com'),
        ]);
    }

    /** Validate one redirect. Returns [data, error]. */
    protected function check(Request $request, ?Redirect $existing = null): array
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:500'],
            'to_url' => ['required', 'string', 'max:500'],
            'status_code' => ['required', 'in:301,302,307,308'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $from = Redirect::normalize($data['from_path']);
        $to = trim($data['to_url']);
        if (preg_match('#^https?://#i', $to)) {
            if (! preg_match('#^https://[a-z0-9.-]+\.[a-z]{2,}(/[^\s]*)?$#i', $to)) {
                return [null, 'An external destination must be a full https:// address.'];
            }
            if (Redirect::isOwnUrl($to)) {
                $to = (parse_url($to, PHP_URL_PATH) ?: '/').(($qs = parse_url($to, PHP_URL_QUERY)) ? '?'.$qs : '');
            }
        } else {
            $to = '/'.ltrim($to, '/');
        }
        if ($from === '/' ) {
            return [null, 'The home page cannot be redirected.'];
        }
        if (preg_match('#^/(admin|_deploy|install|assets|vendor|uploads|storage|sitemap|robots\.txt)\b#', $from)) {
            return [null, 'System addresses cannot be redirected.'];
        }
        if (! preg_match('#^https?://#i', $to) && Redirect::normalize($to) === $from) {
            return [null, 'The old and new address are the same.'];
        }
        $dupe = Redirect::where('from_hash', md5($from))->when($existing, fn ($w) => $w->whereKeyNot($existing->id))->first();
        if ($dupe) {
            return [null, 'A redirect from '.$from.' already exists (to '.$dupe->to_url.').'];
        }
        [$final, $hops, $loop] = Redirect::follow($to, $from);
        if ($loop) {
            return [null, 'This would create a redirect loop.'];
        }
        if ($hops > 0) {
            $to = $final; // point straight to the final address — no chains
        }

        return [['from_path' => $from, 'to_url' => $to, 'status_code' => (int) $data['status_code'], 'notes' => $data['notes'] ?? null], null];
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $err] = $this->check($request);
        if ($err) {
            return back()->withInput()->withErrors(['from_path' => $err]);
        }
        Redirect::create($data + ['is_active' => true, 'source' => 'manual']);

        return back()->with('status', 'Redirect যোগ হয়েছে: '.$data['from_path'].' → '.$data['to_url']);
    }

    public function update(Request $request, Redirect $redirect): RedirectResponse
    {
        [$data, $err] = $this->check($request, $redirect);
        if ($err) {
            return back()->withErrors(['from_path' => $err]);
        }
        $redirect->update($data + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Redirect আপডেট হয়েছে।');
    }

    public function toggle(Redirect $redirect): RedirectResponse
    {
        if (! $redirect->is_active) {
            [, , $loop] = Redirect::follow($redirect->to_url, $redirect->from_path);
            if ($loop) {
                return back()->withErrors(['from_path' => 'Turning this on would create a loop.']);
            }
        }
        $redirect->update(['is_active' => ! $redirect->is_active]);

        return back()->with('status', $redirect->is_active ? 'চালু হয়েছে।' : 'বন্ধ হয়েছে।');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $redirect->delete();

        return back()->with('status', 'Redirect মুছে ফেলা হয়েছে।');
    }

    /** What happens when someone opens an address? */
    public function test(Request $request): JsonResponse
    {
        $path = '/'.ltrim((string) $request->input('path', '/'), '/');
        $hops = [];
        for ($i = 0; $i < 6; $i++) {
            $r = PageFetcher::get($path);
            $hops[] = ['path' => $path, 'status' => $r['status'], 'location' => $r['location']];
            if (! $r['location'] || $r['status'] < 300 || $r['status'] >= 400) {
                break;
            }
            $loc = $r['location'];
            if (! Redirect::isOwnUrl($loc) && preg_match('#^https?://#', $loc)) {
                break;
            }
            $path = (parse_url($loc, PHP_URL_PATH) ?: '/').(($q = parse_url($loc, PHP_URL_QUERY)) ? '?'.$q : '');
        }

        return response()->json(['hops' => $hops]);
    }

    /** Fetch the old WordPress site's addresses and propose destinations (server-side, before the old site is switched off). */
    public function suggest(Request $request): RedirectResponse
    {
        $data = $request->validate(['site' => ['required', 'url:https,http', 'max:200']]);
        $host = (string) parse_url($data['site'], PHP_URL_HOST);
        $ip = gethostbyname($host);
        if (! app()->environment('local', 'testing') && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return back()->withErrors(['site' => 'This address is not allowed.']);
        }
        @set_time_limit(240);
        $rows = (new WordPressRedirects($data['site']))->suggest();
        Cache::put('wp_redirect_suggestions', $rows, now()->addDays(30));
        Cache::put('wp_redirect_site', $data['site'], now()->addDays(30));

        return back()->with('status', count($rows).'টি পুরোনো ঠিকানা পাওয়া গেছে — নিচে দেখে নিশ্চিত করুন।');
    }

    public function apply(Request $request): RedirectResponse
    {
        $rows = collect((array) Cache::get('wp_redirect_suggestions', []))->keyBy('from');
        $made = 0;
        $skipped = [];
        foreach ((array) $request->input('pick', []) as $from => $on) {
            $to = trim((string) $request->input('to.'.md5($from), $rows[$from]['to'] ?? ''));
            if (! $on || ! $rows->has($from) || $to === '') {
                continue;
            }
            $req = new Request(['from_path' => $from, 'to_url' => $to, 'status_code' => '301', 'notes' => 'Old WordPress address ('.$rows[$from]['kind'].')']);
            [$data, $err] = rescue(fn () => $this->check($req), [null, 'invalid'], false);
            if ($err) {
                $skipped[] = $from.': '.$err;

                continue;
            }
            Redirect::create($data + ['is_active' => true, 'source' => 'wordpress']);
            $made++;
        }
        $r = back()->with('status', $made.'টি redirect তৈরি হয়েছে।');

        return $skipped ? $r->withErrors(['from_path' => 'বাদ গেছে: '.implode(' · ', array_slice($skipped, 0, 8))]) : $r;
    }

    public function clearSuggestions(): RedirectResponse
    {
        Cache::forget('wp_redirect_suggestions');

        return back();
    }
}
