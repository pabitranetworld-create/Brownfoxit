@extends('admin.layout')
@section('title', 'Order '.$order->number)
@section('content')
    <div class="adm-formhead">
        <a href="{{ route('admin.orders.index') }}" class="adm-backlink"><i class="fa-solid fa-arrow-left"></i> সব অর্ডার</a>
        <a class="adm-btn ghost sm" href="{{ $order->link() }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> গ্রাহকের পেজ</a>
        <form method="POST" action="{{ route('admin.orders.resend', $order) }}">@csrf<button class="adm-btn ghost sm"><i class="fa-solid fa-envelope"></i> ইমেইল আবার পাঠান</button></form>
    </div>

    @if (! $order->isPaid() && ! in_array($order->status, ['cancelled', 'refunded'], true))
        <div class="adm-card adm-paybox">
            <div>
                <h3><i class="fa-solid fa-hourglass-half"></i> পেমেন্ট এখনো নিশ্চিত হয়নি</h3>
                <p class="adm-muted">পদ্ধতি: <strong>{{ $gatewayLabel }}</strong>@if ($order->manual_reference) · গ্রাহকের দেওয়া Transaction ID: <strong style="color:#fff">{{ $order->manual_reference }}</strong>@endif · মোট {{ money($order->total, $order->currency) }}</p>
            </div>
            <div class="adm-paybox-actions">
                <form method="POST" action="{{ route('admin.orders.recheck', $order) }}">@csrf<button class="adm-btn ghost"><i class="fa-solid fa-rotate"></i> গেটওয়েতে আবার যাচাই</button></form>
                <form method="POST" action="{{ route('admin.orders.confirm', $order) }}" data-confirm="নিশ্চিত তো যে {{ money($order->total, $order->currency) }} আপনার অ্যাকাউন্টে এসেছে? এরপর গ্রাহক ডাউনলোড পাবে।">@csrf<input name="note" placeholder="নোট (যেমন bKash TrxID মিলেছে)"><button class="adm-btn"><i class="fa-solid fa-circle-check"></i> পেমেন্ট নিশ্চিত করুন</button></form>
            </div>
        </div>
    @endif

    <div class="adm-grid-2">
        <div>
            <div class="adm-card">
                <h2 class="adm-h">প্রোডাক্ট</h2>
                <div class="adm-table-wrap"><table class="adm-table">
                    <thead><tr><th>নাম</th><th>দাম</th><th>সংখ্যা</th><th>মোট</th></tr></thead>
                    <tbody>@foreach ($order->items as $i)<tr><td>{{ $i->name }}@if ($i->variant_name)<br><span class="adm-muted">{{ $i->variant_name }}</span>@endif</td><td>{{ money($i->price, $order->currency) }}</td><td>{{ $i->qty }}</td><td>{{ money($i->total, $order->currency) }}</td></tr>@endforeach</tbody>
                </table></div>
                <dl class="adm-dl">
                    <dt>Subtotal</dt><dd>{{ money($order->subtotal, $order->currency) }}</dd>
                    @if ((float) $order->discount)<dt>ছাড় ({{ $order->coupon_code }})</dt><dd>− {{ money($order->discount, $order->currency) }}</dd>@endif
                    @if ((float) $order->shipping)<dt>শিপিং</dt><dd>{{ money($order->shipping, $order->currency) }}</dd>@endif
                    @if ((float) $order->tax)<dt>ট্যাক্স</dt><dd>{{ money($order->tax, $order->currency) }}</dd>@endif
                    <dt><strong>মোট</strong></dt><dd><strong>{{ money($order->total, $order->currency) }}</strong></dd>
                </dl>
            </div>

            <div class="adm-card">
                <h2 class="adm-h">পেমেন্ট</h2>
                @forelse ($order->payments as $p)
                    <div class="adm-payrow">
                        <span class="adm-badge {{ $p->status === 'success' ? 'ok' : ($p->status === 'pending' ? 'new' : '') }}">{{ $p->status }}</span>
                        <div><strong>{{ \App\Store\Gateways::label($p->gateway) }}</strong> · {{ money($p->amount, $p->currency) }} · ref <code>{{ $p->reference }}</code>@if ($p->gateway_txn_id) · gateway TrxID <code>{{ $p->gateway_txn_id }}</code>@endif @if ($p->method) · {{ $p->method }}@endif
                            <br><span class="adm-muted">{{ $p->created_at->format('d M Y H:i') }}@if ($p->verified_at) · যাচাই {{ $p->verified_at->format('d M H:i') }}@endif @if ($p->message) · {{ $p->message }}@endif</span>
                            @if ($p->meta)<details><summary class="adm-muted">গেটওয়ের উত্তর</summary><pre class="adm-pre">{{ json_encode($p->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></details>@endif
                        </div>
                    </div>
                @empty
                    <p class="adm-muted">এখনো কোনো পেমেন্ট চেষ্টা হয়নি।</p>
                @endforelse
            </div>

            <div class="adm-card">
                <h2 class="adm-h">ডাউনলোড</h2>
                @if ($order->downloads->isEmpty())
                    <p class="adm-muted">কোনো ডাউনলোড নেই{{ $order->isPaid() ? '' : ' (পেমেন্ট নিশ্চিত হলে যোগ হবে)' }}।</p>
                    @if ($order->isPaid())<form method="POST" action="{{ route('admin.orders.grant', $order) }}">@csrf<button class="adm-btn ghost sm"><i class="fa-solid fa-plus"></i> প্রোডাক্টের ফাইল থেকে ডাউনলোড দিন</button></form>@endif
                @else
                    @foreach ($order->downloads as $d)
                        <div class="adm-payrow">
                            <span class="adm-badge {{ $d->problem() ? '' : 'ok' }}">{{ $d->problem() ? 'বন্ধ' : 'চালু' }}</span>
                            <div><strong>{{ $d->file_name }}</strong><br><span class="adm-muted">{{ $d->download_count }} বার ডাউনলোড · বাকি {{ $d->downloads_remaining ?? '∞' }} · মেয়াদ {{ $d->expires_at?->format('d M Y') ?? 'নেই' }}@if ($d->problem()) · {{ $d->problem() }}@endif</span></div>
                            <form method="POST" action="{{ route('admin.orders.download', [$order, $d]) }}" class="adm-inline">@csrf
                                @if ($d->revoked_at)<button name="action" value="restore" class="adm-btn ghost sm">ফেরত দিন</button>@else<button name="action" value="revoke" class="adm-btn ghost sm danger">বাতিল</button>@endif
                                <button name="action" value="extend" class="adm-btn ghost sm">+৩০ দিন / +৫ বার</button>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div>
            <div class="adm-card">
                <h2 class="adm-h">গ্রাহক</h2>
                <dl class="adm-dl">
                    @foreach (['name' => 'নাম', 'email' => 'ইমেইল', 'phone' => 'ফোন', 'company' => 'কোম্পানি', 'address' => 'ঠিকানা', 'city' => 'শহর', 'postcode' => 'পোস্টকোড', 'country' => 'দেশ'] as $k => $label)
                        @if (filled($order->billing[$k] ?? null))<dt>{{ $label }}</dt><dd>{{ $order->billing[$k] }}</dd>@endif
                    @endforeach
                    <dt>অ্যাকাউন্ট</dt><dd>{{ $order->user ? 'হ্যাঁ' : 'Guest' }}</dd>
                    @if ($order->customer_note)<dt>গ্রাহকের নোট</dt><dd>{{ $order->customer_note }}</dd>@endif
                </dl>
            </div>
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="adm-card">
                @csrf @method('PUT')
                <h2 class="adm-h">অবস্থা</h2>
                <div class="adm-field"><label>অর্ডারের অবস্থা</label><select name="status">@foreach (\App\Models\Order::STATUSES as $k => $label)<option value="{{ $k }}" @selected($order->status === $k)>{{ $label }}</option>@endforeach</select>
                    <small class="adm-help">Refunded/Cancelled করলে ডাউনলোড বন্ধ হয়। আসল টাকা ফেরত গেটওয়ে বা ব্যাংক থেকে আলাদাভাবে দিতে হবে।</small></div>
                <div class="adm-field"><label>ইতিহাসে নোট যোগ করুন</label><input name="note"></div>
                <div class="adm-field"><label>নিজের নোট (গ্রাহক দেখে না)</label><textarea name="admin_note" rows="3">{{ $order->admin_note }}</textarea></div>
                <button class="adm-btn"><i class="fa-solid fa-check"></i> সেভ</button>
            </form>
            <div class="adm-card">
                <h2 class="adm-h">ইতিহাস</h2>
                <ul class="adm-timeline">@foreach ($order->events as $e)<li><small>{{ $e->created_at->format('d M H:i') }}</small> {{ $e->message }}</li>@endforeach</ul>
            </div>
        </div>
    </div>
@endsection
