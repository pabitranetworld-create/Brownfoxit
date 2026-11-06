@php([$regular, $sale] = $product->prices($variant ?? null))
<span class="st-price">
    @if ($product->hasVariants() && empty($variant))
        <small>From</small> {{ money($product->fromPrice()) }}
    @elseif ($sale !== null)
        <del>{{ money($regular) }}</del> <ins>{{ money($sale) }}</ins>
    @else
        {{ money($regular) }}
    @endif
</span>
