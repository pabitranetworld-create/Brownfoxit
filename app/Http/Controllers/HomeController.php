<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'services' => Service::active()->where('show_on_home', true)->ordered()->get(),
            'clients' => Client::active()->ordered()->get(),
            'projects' => Project::active()->featured()->ordered()->get(),
            'testimonials' => Testimonial::active()->ordered()->get(),
            'posts' => Post::published()
                ->with(['category', 'author'])
                ->latest('published_at')
                ->take(6)
                ->get(),
        ]);
    }
}
