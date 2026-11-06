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
            ->when($category, fn ($w) => $w->where('product_category_id', $category->id))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('short_description', 'like', "%{$q}%")->orWhere('tags', 'like', "%{$q}%")->orWhere('sku', $q)))
            ->when($request->boolean('featured'), fn ($w) => $w->where('is_featured', true))
            ->when($sort === 'price_asc', fn ($w) => $w->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($sort === 'price_desc', fn ($w) => $w->orderByRaw('COALESCE(sale_price, price) desc'))
            ->when($sort === 'newest', fn ($w) => $w->latest('published_at'))
            ->when(! in_array($sort, ['price_asc', 'price_desc', 'newest'], true), fn ($w) => $w->orderByDesc('is_featured')->orderBy('sort_order')->latest('id'))
            ->paginate((int) store('per_page', 12))->withQueryString();

        return view('shop.index', [
            'products' => $products,
            'category' => $category,
            'categories' => ProductCategory::whereHas('products', fn ($w) => $w->published())->orderBy('sort_order')->orderBy('name')->get(),
            'featured' => ! $category && $q === '' && ! $request->has('page') ? Product::published()->with('category')->where('is_featured', true)->orderBy('sort_order')->take(4)->get() : collect(),
            'q' => $q,
            'sort' => $sort,
        ]);
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
        if ($related->count() < 4 && $product->product_category_id) {
            $related = $related->merge(Product::published()->with('category')->where('product_category_id', $product->product_category_id)->where('id', '!=', $product->id)->whereNotIn('id', $related->pluck('id'))->take(4 - $related->count())->get());
        }

        return view('shop.product', ['product' => $product, 'related' => $related]);
    }
}
