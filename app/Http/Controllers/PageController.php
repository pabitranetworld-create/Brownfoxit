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

        return view('pages.show', [
            'page' => $page,
            'layout' => $validator->clean($page->layout), // re-validated so old layouts get new defaults
            'preview' => false,
            'editor' => false,
        ]);
    }
}
