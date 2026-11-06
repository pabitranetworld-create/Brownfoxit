@extends('admin.layout')
@section('title', $g::name().' — সেটিংস')
@section('content')
    <div class="adm-formhead">
        <a href="{{ route('admin.payments.index') }}" class="adm-backlink"><i class="fa-solid fa-arrow-left"></i> সব গেটওয়ে</a>
        @if ($g->configured())<form method="POST" action="{{ route('admin.payments.test', $key) }}">@csrf<button class="adm-btn ghost sm"><i class="fa-solid fa-plug-circle-check"></i> সংযোগ টেস্ট</button></form>@endif
    </div>
    @if ($help)<div class="adm-alert warn"><i class="fa-solid fa-circle-info"></i> <div>{{ $help }}</div></div>@endif
    @unless ($g instanceof \App\Store\Gateways\Manual)
        <div class="adm-card">
            <h2 class="adm-h">গেটওয়েকে যে লিংকগুলো দিতে হতে পারে</h2>
            <dl class="adm-dl">
                <dt>Return / Callback URL</dt><dd><code>{{ url('/payment/'.$key.'/return/{reference}/success') }}</code><br><span class="adm-muted">প্রতিটা পেমেন্টে নিজে থেকে পাঠানো হয় — সাধারণত আলাদা করে বসাতে হয় না।</span></dd>
                <dt>IPN / Webhook URL</dt><dd><code>{{ route('payment.ipn', ['gateway' => $key]) }}</code></dd>
            </dl>
        </div>
    @endunless
    <form method="POST" action="{{ route('admin.payments.update', $key) }}" class="adm-card" data-unsaved>
        @csrf @method('PUT')
        <div class="adm-fields">
            @foreach ($fields as $f)
                @php($v = $g->config[$f['key']] ?? null)
                @php($t = $f['type'] ?? 'text')
                @if ($t === 'heading')
                    <div class="adm-field full adm-field-heading"><h3>{{ $f['label'] }}</h3></div>
                    @continue
                @endif
                <div @class(['adm-field', 'full' => in_array($t, ['textarea', 'secret_text'], true)])>
                    @if ($t === 'checkbox')
                        <input type="hidden" name="g[{{ $f['key'] }}]" value="0">
                        <label class="adm-switch"><input type="checkbox" name="g[{{ $f['key'] }}]" value="1" @checked($v)><span></span> {{ $f['label'] }}</label>
                    @else
                        <label for="g-{{ $f['key'] }}">{{ $f['label'] }}</label>
                        @switch($t)
                            @case('select')<select id="g-{{ $f['key'] }}" name="g[{{ $f['key'] }}]">@foreach ($f['options'] as $ov => $ol)<option value="{{ $ov }}" @selected((string) $v === (string) $ov)>{{ $ol }}</option>@endforeach</select>@break
                            @case('textarea')<textarea id="g-{{ $f['key'] }}" name="g[{{ $f['key'] }}]" rows="5" class="adm-mono">{{ $v }}</textarea>@break
                            @case('secret')<input id="g-{{ $f['key'] }}" type="password" name="g[{{ $f['key'] }}]" autocomplete="new-password" placeholder="{{ filled($v) ? '•••••••• (সেভ করা আছে — বদলাতে নতুন লিখুন)' : '' }}">@break
                            @case('secret_text')<textarea id="g-{{ $f['key'] }}" name="g[{{ $f['key'] }}]" rows="4" class="adm-mono" placeholder="{{ filled($v) ? 'সেভ করা আছে (এনক্রিপ্টেড) — বদলাতে সব লাইন নতুন করে লিখুন' : "store_id=...\napi_key=..." }}"></textarea>@break
                            @case('number')<input id="g-{{ $f['key'] }}" type="number" name="g[{{ $f['key'] }}]" value="{{ $v }}">@break
                            @default<input id="g-{{ $f['key'] }}" name="g[{{ $f['key'] }}]" value="{{ $v }}">
                        @endswitch
                    @endif
                    @if (! empty($f['help']))<small class="adm-help" style="white-space:pre-line">{{ $f['help'] }}</small>@endif
                </div>
            @endforeach
        </div>
        <div class="adm-actions sticky"><span class="spacer"></span><button class="adm-btn"><i class="fa-solid fa-floppy-disk"></i> সেভ করুন</button></div>
    </form>
@endsection
