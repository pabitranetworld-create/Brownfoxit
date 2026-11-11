<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        return $this->listing($request, null);
    }

    protected function listing(Request $request, ?ProductCategory $category): View
    {
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'default');
        $products = Product::published()->with('category')
            ->when($category, fn ($w) => $w->whereIn('product_category_id', array_merge([$category->id], $category->descendantIds())))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('short_description', 'like', "%{$q}%")->orWhere('tags', 'like', "%{$q}%")->orWhere('sku', $q)))
            ->when($request->boolean('featured'), fn ($w) => $w->where('is_featured', true))
            ->when($sort === 'price_asc', fn ($w) => $w->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($sort === 'price_desc', fn ($w) => $w->orderByRaw('COALESCE(sale_price, price) desc'))
            ->when($sort === 'newest', fn ($w) => $w->latest('published_at'))
            ->when(! in_array($sort, ['price_asc', 'price_desc', 'newest'], true), fn ($w) => $w->orderByDesc('is_featured')->orderBy('sort_order')->latest('id'))
            ->paginate((int) store('per_page', 12))->withQueryString();

        $nav = $this->categoryNav($category);

        return view('shop.index', [
            'products' => $products,
            'category' => $category,
            'categories' => $nav['top'],
            'subcategories' => $nav['sub'],
            'subParent' => $nav['sub_parent'],
            'activeTop' => $nav['active_top'],
            'ancestors' => $category ? $category->ancestors() : [],
            'featured' => ! $category && $q === '' && ! $request->has('page') ? Product::published()->with('category')->where('is_featured', true)->orderBy('sort_order')->take(3)->get() : collect(),
            'q' => $q,
            'sort' => $sort,
        ]);
    }

    /**
     * Top-level categories (shown as chips) and, when one is open, its sub-categories.
     * A category is listed only when it or one of its sub-categories has a published product.
     */
    protected function categoryNav(?ProductCategory $current): array
    {
        $all = ProductCategory::query()->orderBy('sort_order')->orderBy('name')->get();
        $withProducts = Product::published()->whereNotNull('product_category_id')->distinct()->pluck('product_category_id')->map(fn ($id) => (int) $id)->all();
        $byParent = $all->groupBy(fn ($c) => (int) $c->parent_id);
        $has = function (ProductCategory $c) use (&$has, $byParent, $withProducts): bool {
            if (in_array((int) $c->id, $withProducts, true)) {
                return true;
            }
            foreach ($byParent->get((int) $c->id, collect()) as $child) {
                if ($has($child)) {
                    return true;
                }
            }

            return false;
        };
        $top = $byParent->get(0, collect())->filter($has)->values();

        $activeTop = null;
        $sub = collect();
        $subParent = null;
        if ($current) {
            $chain = array_merge($current->ancestors(), [$current]);
            $activeTop = $chain[0]->id;
            // children of the open category; for a leaf, show its siblings so the visitor can switch
            $parentForSubs = $byParent->has((int) $current->id) && $byParent->get((int) $current->id)->filter($has)->isNotEmpty() ? $current->id : $current->parent_id;
            if ($parentForSubs) {
                $sub = $byParent->get((int) $parentForSubs, collect())->filter($has)->values();
                $subParent = $all->firstWhere('id', $parentForSubs);
            }
        }

        return ['top' => $top, 'sub' => $sub, 'sub_parent' => $subParent, 'active_top' => $activeTop];
    }

    public function category(Request $request, string $slug): View
    {
        return $this->listing($request, ProductCategory::where('slug', $slug)->firstOrFail());
    }

    public function show(string $slug): View
    {
        $product = Product::where('slug', $slug)->with('category')->firstOrFail();
        if ($product->status !== 'published' && ! auth()->user()?->isAdmin()) {
            abort(404);
        }
        $related = collect();
        if ($product->related_ids) {
            $related = Product::published()->with('category')->whereIn('id', $product->related_ids)->get();
        }
        if ($related->count() < 3 && $product->product_category_id) {
            $related = $related->merge(Product::published()->with('category')->where('product_category_id', $product->product_category_id)->where('id', '!=', $product->id)->whereNotIn('id', $related->pluck('id'))->take(3 - $related->count())->get());
        }

        return view('shop.product', ['product' => $product, 'related' => $related]);
    }
}
