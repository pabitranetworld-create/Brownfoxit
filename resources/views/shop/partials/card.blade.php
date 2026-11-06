@php([$regular, $sale] = $product->prices())
<article class="st-card pb-item">
    <a href="{{ $product->link() }}" class="st-card-img pb-img fx-zoom">
        @if ($product->image)<img src="{{ media($product->image) }}" alt="{{ $product->name }}" loading="lazy">@else<span class="st-noimg"><i class="fa-solid fa-box-open"></i></span>@endif
        @if ($sale !== null && ! $product->hasVariants())<span class="st-badge">Sale</span>@elseif ($product->is_featured)<span class="st-badge alt">Featured</span>@endif
    </a>
    <div class="st-card-body">
        @if ($product->category)<a class="st-card-cat" href="{{ $product->category->link() }}">{{ $product->category->name }}</a>@endif
        <h3><a href="{{ $product->link() }}">{{ $product->name }}</a></h3>
        @if ($product->short_description)<p>{{ \Illuminate\Support\Str::limit(strip_tags($product->short_description), 90) }}</p>@endif
        <div class="st-card-foot">
            @include('shop.partials.price', ['product' => $product])
            @if ($product->hasVariants())
                <a class="st-btn sm" href="{{ $product->link() }}">Choose</a>
            @elseif ($product->inStock())
                <form method="post" action="{{ route('cart.add') }}" data-add-cart>@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><button class="st-btn sm" type="submit"><i class="fa-solid fa-cart-plus"></i> <span>Add</span></button></form>
            @else
                <span class="st-out">Out of stock</span>
            @endif
        </div>
    </div>
</article>
