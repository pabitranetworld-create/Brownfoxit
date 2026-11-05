@extends('admin.layout')

@section('title', 'Message from '.$m->name)

@section('content')
    <p><a href="{{ route('admin.messages.index') }}" class="adm-muted"><i class="fa-solid fa-arrow-left"></i> সব বার্তা</a></p>
    <div class="adm-grid-2">
        <div class="adm-card">
            <h2 class="adm-h">{{ \App\Models\FormSubmission::TYPES[$m->type] ?? $m->type }}: {{ $m->name }}</h2>
            <dl class="adm-dl">
                <dt>ইমেইল</dt><dd><a href="mailto:{{ $m->email }}">{{ $m->email }}</a></dd>
                @foreach (['phone' => 'ফোন', 'company' => 'কোম্পানি', 'subject' => 'বিষয়', 'service' => 'Service', 'budget' => 'বাজেট', 'timeline' => 'সময়সীমা'] as $f => $label)
                    @if (filled($m->{$f}))<dt>{{ $label }}</dt><dd>{{ $m->{$f} }}</dd>@endif
                @endforeach
                @if (! empty($m->data['reference']))<dt>Reference</dt><dd><a href="{{ $m->data['reference'] }}" target="_blank" rel="noopener nofollow">{{ $m->data['reference'] }}</a></dd>@endif
                <dt>সময়</dt><dd>{{ $m->created_at->format('d M Y, H:i') }}</dd>
                <dt>যে পেজ থেকে</dt><dd class="adm-muted">{{ $m->page_url ?: '—' }}</dd>
                <dt>ইমেইলে পাঠানো</dt><dd>{{ $m->emailed ? 'হ্যাঁ' : 'না (SMTP সেট করা নেই বা পাঠানো যায়নি)' }}</dd>
            </dl>
            <div class="adm-message">{!! nl2br(e($m->message)) !!}</div>
            <p style="margin-top:18px"><a class="adm-btn" href="mailto:{{ $m->email }}?subject={{ rawurlencode('Re: '.($m->subject ?: ($m->service ?: 'your message'))) }}"><i class="fa-solid fa-reply"></i> ইমেইলে উত্তর দিন</a></p>
        </div>
        <div class="adm-card">
            <form method="POST" action="{{ route('admin.messages.update', $m) }}">
                @csrf @method('PUT')
                <div class="adm-field"><label>অবস্থা</label>
                    <select name="status">@foreach (\App\Models\FormSubmission::STATUSES as $k => $label)<option value="{{ $k }}" @selected($m->status === $k)>{{ $label }}</option>@endforeach</select></div>
                <div class="adm-field"><label>নিজের নোট (শুধু Admin দেখে)</label><textarea name="admin_note" rows="5">{{ $m->admin_note }}</textarea></div>
                <button class="adm-btn"><i class="fa-solid fa-check"></i> সেভ</button>
            </form>
            <form method="POST" action="{{ route('admin.messages.destroy', $m) }}" data-confirm="বার্তাটা মুছে ফেলবেন?" style="margin-top:14px">
                @csrf @method('DELETE')
                <button class="adm-btn ghost danger"><i class="fa-solid fa-trash"></i> মুছুন</button>
            </form>
        </div>
    </div>
@endsection
