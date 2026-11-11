@php([$regular, $sale] = $product->prices())
<article class="sp-card pb-item">
    <a href="{{ $product->link() }}" class="sp-card-img pb-img fx-zoom" tabindex="-1" aria-hidden="true">
        @if ($product->image)<img src="{{ media($product->image) }}" alt="{{ $product->name }}" loading="lazy">@else<span class="st-noimg"><i class="fa-solid fa-box-open"></i></span>@endif
        @if ($sale !== null && ! $product->hasVariants())<span class="st-badge">Sale</span>@elseif ($product->is_featured)<span class="st-badge alt">Featured</span>@endif
    </a>
    <div class="sp-card-body">
        <h3 title="{{ $product->name }}"><a href="{{ $product->link() }}">{{ $product->name }}</a></h3>
        <div class="sp-card-price">@include('shop.partials.price', ['product' => $product])</div>
        <div class="sp-card-actions">
            <a class="sp-act view" href="{{ $product->link() }}" aria-label="View {{ $product->name }}"><i class="fa-regular fa-eye"></i> <span>View</span></a>
            @if ($product->hasVariants())
                <a class="sp-act buy" href="{{ $product->link() }}#buy"><i class="fa-solid fa-bolt"></i> <span>Buy Now</span></a>
                <a class="sp-act cart" href="{{ $product->link() }}#buy"><i class="fa-solid fa-cart-plus"></i> <span>Add to cart</span></a>
            @elseif ($product->inStock())
                <form method="post" action="{{ route('cart.add') }}" data-add-cart class="sp-act-form">
                    @csrf<input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button class="sp-act buy" type="submit" name="buy_now" value="1"><i class="fa-solid fa-bolt"></i> <span>Buy Now</span></button>
                    <button class="sp-act cart" type="submit"><i class="fa-solid fa-cart-plus"></i> <span>Add to cart</span></button>
                </form>
            @else
                <span class="sp-act out">Out of stock</span>
            @endif
        </div>
    </div>
</article>
