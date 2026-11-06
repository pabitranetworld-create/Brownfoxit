@extends('admin.layout')
@section('title', 'Customers')
@section('content')
    <div class="adm-card">
        <div class="adm-toolbar"><form method="GET" class="adm-search"><i class="fa-solid fa-magnifying-glass"></i><input name="q" value="{{ $q }}" placeholder="নাম, ইমেইল, ফোন…"></form></div>
        @if ($items->isEmpty())
            <div class="adm-empty"><i class="fa-solid fa-users"></i><p>এখনো কোনো গ্রাহক অ্যাকাউন্ট নেই।</p></div>
        @else
            <div class="adm-table-wrap"><table class="adm-table">
                <thead><tr><th>নাম</th><th>ফোন</th><th>অর্ডার</th><th>মোট কেনা</th><th>যোগ দিয়েছেন</th></tr></thead>
                <tbody>@foreach ($items as $u)<tr><td><strong>{{ $u->name }}</strong><br><span class="adm-muted">{{ $u->email }}</span></td><td>{{ $u->phone ?: '—' }}</td><td><a href="{{ route('admin.orders.index', ['q' => $u->email]) }}">{{ $u->orders_count }}</a></td><td>{{ money($u->spent ?? 0) }}</td><td class="adm-muted">{{ $u->created_at->format('d M Y') }}</td></tr>@endforeach</tbody>
            </table></div>
            {{ $items->links('admin.pagination') }}
        @endif
    </div>
@endsection
