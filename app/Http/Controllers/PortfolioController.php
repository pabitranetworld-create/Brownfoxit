<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::public()->ordered()->get();

        return view('portfolio.index', [
            'projects' => $projects,
            'categories' => $projects->flatMap(fn ($p) => $p->categories())->unique()->values(),
        ]);
    }

    public function show(string $slug): View
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        abort_unless(($project->is_active && ($project->status ?? 'published') === 'published') || auth()->user()?->isAdmin(), 404);
        $cats = $project->categories();
        $related = Project::public()->where('id', '!=', $project->id)
            ->when($cats, fn ($q) => $q->where(fn ($w) => collect($cats)->each(fn ($c) => $w->orWhere('category', 'like', '%'.$c.'%'))))
            ->ordered()->take(3)->get();
        $all = Project::public()->ordered()->get(['id', 'slug', 'title']);
        $i = $all->search(fn ($p) => $p->id === $project->id);

        return view('portfolio.show', [
            'project' => $project,
            'related' => $related,
            'prev' => $i !== false && $i > 0 ? $all[$i - 1] : null,
            'next' => $i !== false && $i < $all->count() - 1 ? $all[$i + 1] : null,
        ]);
    }
}
