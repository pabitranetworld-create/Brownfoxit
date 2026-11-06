<dl class="st-totals">
    <div><dt>Subtotal</dt><dd>{{ money($t['subtotal']) }}</dd></div>
    @if ($t['discount'] > 0)<div class="disc"><dt>Discount @if ($t['coupon'])<small>({{ $t['coupon']->code }})</small>@endif</dt><dd>− {{ money($t['discount']) }}</dd></div>@endif
    @if ($t['needs_shipping'])<div><dt>Shipping</dt><dd>{{ $t['shipping'] > 0 ? money($t['shipping']) : 'Free' }}</dd></div>@endif
    @if ($t['tax'] > 0)<div><dt>{{ store('tax_label') ?: 'Tax' }}</dt><dd>{{ money($t['tax']) }}</dd></div>@endif
    <div class="total"><dt>Total</dt><dd>{{ money($t['total']) }}</dd></div>
</dl>
