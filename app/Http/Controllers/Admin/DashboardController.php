<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Schema;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [];
        foreach (Schema::resources() as $key => $res) {
            $stats[$key] = [
                'title' => $res['title'],
                'icon' => $res['icon'],
                'count' => $res['model']::count(),
            ];
        }

        return view('admin.dashboard', [
            'stats' => $stats,
            'sections' => Schema::sections(),
        ]);
    }

    /** "Clear cache" button: needed after editing .env by hand on the server. */
    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');

        return back()->with('status', 'Cache পরিষ্কার করা হয়েছে।');
    }
}
