<div class="st-table-wrap"><table class="st-table">
    <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
    <tbody>
        @foreach ($orders as $o)
            <tr>
                <td data-label="Order"><strong>{{ $o->number }}</strong><br><small class="st-muted">{{ $o->items->pluck('name')->implode(', ') }}</small></td>
                <td data-label="Date">{{ $o->created_at->format('d M Y') }}</td>
                <td data-label="Status"><span class="st-status s-{{ $o->status }}">{{ \App\Models\Order::STATUSES[$o->status] ?? $o->status }}</span></td>
                <td data-label="Total">{{ money($o->total, $o->currency) }}</td>
                <td><a class="st-btn sm ghost" href="{{ $o->link() }}">{{ $o->canPay() ? 'Pay now' : 'View' }}</a></td>
            </tr>
        @endforeach
    </tbody>
</table></div>
