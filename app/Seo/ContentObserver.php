<?php

namespace App\Seo;

use App\Http\Controllers\SeoController;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps search engines happy when content changes:
 *  - a changed URL (slug) gets a permanent 301 redirect from the old address, and existing redirects
 *    that pointed to the old address are updated, so no redirect chains appear;
 *  - the cached sitemap is refreshed.
 */
class ContentObserver
{
    public function updated(Model $m): void
    {
        if ($m->wasChanged('slug') && filled($m->getOriginal('slug'))) {
            $old = (clone $m)->forceFill(['slug' => $m->getOriginal('slug')]);
            $from = Subjects::path($old);
            $to = Subjects::path($m);
            if (Redirect::normalize($from) !== Redirect::normalize($to)) {
                rescue(function () use ($from, $to) {
                    Redirect::where('to_url', $from)->orWhere('to_url', url($from))->get()->each(fn ($r) => $r->update(['to_url' => $to]));
                    Redirect::where('from_hash', md5(Redirect::normalize($to)))->delete(); // the new address must not redirect away
                    Redirect::updateOrCreate(['from_hash' => md5(Redirect::normalize($from))], ['from_path' => $from, 'to_url' => $to, 'status_code' => 301, 'is_active' => true, 'source' => 'slug-change', 'notes' => 'URL changed']);
                }, null, false);
            }
        }
        SeoController::flush();
    }

    public function created(Model $m): void
    {
        SeoController::flush();
    }

    public function deleted(Model $m): void
    {
        SeoController::flush();
    }
}
