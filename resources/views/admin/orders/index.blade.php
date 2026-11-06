@extends('admin.layout')
@section('title', 'Orders')
@section('content')
    <div class="adm-stats">
        <div class="adm-card"><small>আজকের বিক্রি</small><strong>{{ money($stats['today']) }}</strong></div>
        <div class="adm-card"><small>এই মাসে</small><strong>{{ money($stats['month']) }}</strong></div>
        <div class="adm-card"><small>মোট (paid)</small><strong>{{ money($stats['all']) }}</strong></div>
        <div class="adm-card"><small>Paid অর্ডার</small><strong>{{ $stats['paid_count'] }}</strong></div>
    </div>
    <div class="adm-card">
        <div class="adm-toolbar">
            <form method="GET" class="adm-search"><i class="fa-solid fa-magnifying-glass"></i><input name="q" value="{{ $q }}" placeholder="অর্ডার নম্বর, নাম, ইমেইল, Transaction ID…">@if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif</form>
            <div class="adm-tabs-inline">
                <a href="{{ route('admin.orders.index') }}" @class(['on' => $status === ''])>সব</a>
                @foreach (\App\Models\Order::STATUSES as $k => $label)<a href="{{ route('admin.orders.index', ['status' => $k]) }}" @class(['on' => $status === $k])>{{ $label }} <small>{{ $counts[$k] ?? 0 }}</small></a>@endforeach
            </div>
        </div>
        @if ($items->isEmpty())
            <div class="adm-empty"><i class="fa-solid fa-receipt"></i><p>কোনো অর্ডার নেই।</p></div>
        @else
            <div class="adm-table-wrap"><table class="adm-table">
                <thead><tr><th>অর্ডার</th><th>গ্রাহক</th><th>অবস্থা</th><th>পেমেন্ট</th><th>মোট</th><th>তারিখ</th></tr></thead>
                <tbody>
                    @foreach ($items as $o)
                        <tr @class(['adm-unread' => in_array($o->status, ['on_hold', 'processing'], true)])>
                            <td data-label="অর্ডার"><a class="adm-strong" href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a><br><span class="adm-muted">{{ $o->items_count }} item</span></td>
                            <td data-label="গ্রাহক">{{ $o->billing['name'] ?? '' }}<br><span class="adm-muted">{{ $o->billing['email'] ?? '' }}</span></td>
                            <td data-label="অবস্থা"><span @class(['adm-badge', 'ok' => in_array($o->status, ['completed', 'processing'], true), 'new' => $o->status === 'on_hold'])>{{ \App\Models\Order::STATUSES[$o->status] ?? $o->status }}</span></td>
                            <td data-label="পেমেন্ট">{{ \App\Models\Order::PAYMENT_STATUSES[$o->payment_status] ?? $o->payment_status }}<br><span class="adm-muted">{{ \App\Store\Gateways::label($o->payment_method) }}</span></td>
                            <td data-label="মোট"><strong>{{ money($o->total, $o->currency) }}</strong></td>
                            <td data-label="তারিখ" class="adm-muted">{{ $o->created_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            {{ $items->links('admin.pagination') }}
        @endif
    </div>
@endsection
