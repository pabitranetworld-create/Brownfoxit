<?php

namespace App\Seo;

use Illuminate\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * Renders a public URL of this site exactly as an anonymous visitor (or Googlebot) would get it,
 * inside a separate copy of the application — no network request, so it also works on hosts that
 * block calls to their own domain. Everything is restored afterwards.
 */
class PageFetcher
{
    /** @return array{status:int, html:string, location:?string, headers:array} */
    public static function get(string $path): array
    {
        $old = app();
        $current = request();
        $server = [
            'HTTP_HOST' => $current->getHttpHost(),
            'HTTPS' => $current->secure() ? 'on' : 'off',
            'SERVER_PORT' => $current->getPort(),
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; BrownFoxIT-SEO-Audit/1.0)',
            'HTTP_ACCEPT' => 'text/html',
        ];
        $req = Request::create($path, 'GET', [], [], [], $server);
        $resolver = Model::getConnectionResolver();
        $events = Model::getEventDispatcher();
        $result = ['status' => 0, 'html' => '', 'location' => null, 'headers' => []];
        try {
            $app = require base_path('bootstrap/app.php');
            $kernel = $app->make(Kernel::class);
            $res = $kernel->handle($req);
            $result = [
                'status' => $res->getStatusCode(),
                'html' => (string) $res->getContent(),
                'location' => $res->headers->get('Location'),
                'headers' => ['x-robots-tag' => $res->headers->get('X-Robots-Tag'), 'content-type' => $res->headers->get('Content-Type')],
            ];
            rescue(fn () => $kernel->terminate($req, $res), null, false);
            rescue(fn () => $app['db']->disconnect(), null, false);
        } catch (Throwable $e) {
            $result['status'] = 500;
            $result['error'] = $e->getMessage();
        } finally {
            Container::setInstance($old);
            Facade::setFacadeApplication($old);
            Facade::clearResolvedInstances();
            if ($resolver) {
                Model::setConnectionResolver($resolver);
            }
            if ($events) {
                Model::setEventDispatcher($events);
            }
            Paginator::currentPathResolver(fn () => $old['request']->url());
            Paginator::currentPageResolver(function ($pageName = 'page') use ($old) {
                $page = $old['request']->input($pageName);

                return filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1 ? (int) $page : 1;
            });
        }

        return $result;
    }
}
