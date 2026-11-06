<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        return $this->listing($request, null);
    }

    public function category(Request $request, string $slug): View
    {
        return $this->listing($request, PostCategory::where('slug', $slug)->firstOrFail());
    }

    protected function listing(Request $request, ?PostCategory $category): View
    {
        $q = trim((string) $request->query('q', ''));
        $posts = Post::published()->with(['category', 'author'])
            ->when($category, fn ($w) => $w->where('post_category_id', $category->id))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%{$q}%")->orWhere('excerpt', 'like', "%{$q}%")->orWhere('body', 'like', "%{$q}%")))
            ->latest('published_at')->paginate(9)->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'category' => $category,
            'categories' => PostCategory::whereHas('posts', fn ($w) => $w->published())->withCount(['posts' => fn ($w) => $w->published()])->orderBy('name')->get(),
            'q' => $q,
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::where('slug', $slug)->with(['category', 'author'])->firstOrFail();
        $visible = $post->status === Post::STATUS_PUBLISHED && $post->published_at && $post->published_at->isPast();
        abort_unless($visible || auth()->user()?->isAdmin(), 404);

        return view('blog.show', [
            'post' => $post,
            'related' => Post::published()->where('id', '!=', $post->id)->when($post->post_category_id, fn ($q) => $q->where('post_category_id', $post->post_category_id))->latest('published_at')->with(['category', 'author'])->take(3)->get(),
            'prev' => Post::published()->where('published_at', '<', $post->published_at)->latest('published_at')->first(['slug', 'title']),
            'next' => Post::published()->where('published_at', '>', $post->published_at)->oldest('published_at')->first(['slug', 'title']),
        ]);
    }
}
