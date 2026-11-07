<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use App\Seo\Seo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs before anything else:
 *  1. one preferred address on the real domain (https + with/without www),
 *  2. the owner's redirects (old WordPress URLs, changed slugs),
 *  3. a "noindex" header on draft/staging domains so they never end up in search results.
 */
class SeoRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD'], true) && ! $request->is('admin', 'admin/*', '_deploy', 'install', 'install/*', 'up')) {
            if ($r = $this->hostRedirect($request)) {
                return $r;
            }
            if ($r = $this->ownerRedirect($request)) {
                return $r;
            }
        }

        $response = $next($request);

        if (! rescue(fn () => Seo::siteIndexable(), true, false)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    protected function hostRedirect(Request $request): ?Response
    {
        $prod = preg_replace('#^https?://#', '', strtolower(trim((string) rescue(fn () => Seo::cfg('production_host', ''), '', false), '/ ')));
        if ($prod === '') {
            return null;
        }
        $host = strtolower($request->getHost());
        $bare = preg_replace('/^www\./', '', $prod);
        if ($host !== $bare && $host !== 'www.'.$bare) {
            return null; // another domain (e.g. the draft domain) – leave it alone
        }
        $httpsWanted = (bool) rescue(fn () => Seo::cfg('force_https', true), true, false);
        $secure = $request->secure() || strtolower((string) $request->header('X-Forwarded-Proto')) === 'https';
        if ($host !== $prod || ($httpsWanted && ! $secure)) {
            $target = ($httpsWanted || $secure ? 'https://' : 'http://').$prod.$request->getRequestUri();

            return redirect()->away($target, 301);
        }

        return null;
    }

    protected function ownerRedirect(Request $request): ?Response
    {
        $map = rescue(fn () => Redirect::map(), [], false);
        if (! $map) {
            return null;
        }
        $path = Redirect::normalize($request->getPathInfo());
        if (! isset($map[$path])) {
            return null;
        }
        [$to, $code, $id] = $map[$path];
        if (! preg_match('#^https?://#i', $to)) {
            $to = '/'.ltrim($to, '/');
            if (Redirect::normalize($to) === $path) {
                return null; // never redirect a page to itself
            }
        }
        $qs = $request->getQueryString();
        if ($qs && ! str_contains($to, '?')) {
            $to .= '?'.$qs;
        }
        rescue(fn () => DB::table('redirects')->where('id', $id)->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]), null, false);

        return redirect()->to($to, in_array($code, [301, 302, 307, 308], true) ? $code : 301, ['Cache-Control' => 'no-cache']);
    }
}
