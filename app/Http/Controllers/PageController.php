<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\PageBuilder\LayoutValidator;
use Illuminate\View\View;

class PageController extends Controller
{
    /** Public page: /{slug} – only published pages. */
    public function show(string $slug, LayoutValidator $validator): View
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        $layout = $validator->clean($page->layout); // re-validated so old layouts get new defaults

        return view(\App\PageBuilder\Theme::view($page, $layout), [
            'page' => $page,
            'layout' => $layout,
            'preview' => false,
            'editor' => false,
        ]);
    }

    /** The custom 404 page: the builder page "page-not-found" if published, else a simple built-in one. */
    public function notFound(): \Symfony\Component\HttpFoundation\Response
    {
        $page = Page::published()->where('slug', 'page-not-found')->first();
        if (! $page) {
            return response()->view('errors.404', [], 404);
        }
        $layout = app(LayoutValidator::class)->clean($page->layout);

        return response()->view(\App\PageBuilder\Theme::view($page, $layout), ['page' => $page, 'layout' => $layout, 'preview' => false, 'editor' => false], 404);
    }
}
