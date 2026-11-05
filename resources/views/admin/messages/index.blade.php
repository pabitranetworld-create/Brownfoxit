@extends('admin.layout')

@section('title', 'Messages')

@section('content')
    @unless ($mailReady)
        <div class="adm-alert warn"><i class="fa-solid fa-envelope-circle-check"></i> বার্তাগুলো এখানে জমা হচ্ছে। ইমেইলেও পেতে চাইলে <a href="{{ route('admin.settings.edit', 'mail') }}">Settings → ইমেইল (SMTP)</a> সেট করুন।</div>
    @endunless
    <div class="adm-card">
        <div class="adm-toolbar">
            <form method="GET" class="adm-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input name="q" value="{{ $q }}" placeholder="নাম, ইমেইল বা লেখা খুঁজুন…">
                @if ($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
            </form>
            <div class="adm-tabs-inline">
                <a href="{{ route('admin.messages.index') }}" @class(['on' => $status === '' && $type === ''])>Inbox</a>
                <a href="{{ route('admin.messages.index', ['type' => 'quote']) }}" @class(['on' => $type === 'quote'])>Quote requests</a>
                <a href="{{ route('admin.messages.index', ['type' => 'contact']) }}" @class(['on' => $type === 'contact'])>Contact</a>
                @foreach (\App\Models\FormSubmission::STATUSES as $k => $label)
                    <a href="{{ route('admin.messages.index', ['status' => $k]) }}" @class(['on' => $status === $k])>{{ $label }} <small>{{ $counts[$k] ?? 0 }}</small></a>
                @endforeach
            </div>
        </div>

        @if ($items->isEmpty())
            <div class="adm-empty"><i class="fa-regular fa-envelope-open"></i><p>কোনো বার্তা নেই।</p></div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead><tr><th>নাম</th><th>ধরন</th><th>বিষয় / Service</th><th>অবস্থা</th><th>সময়</th></tr></thead>
                    <tbody>
                        @foreach ($items as $m)
                            <tr @class(['adm-unread' => $m->status === 'new'])>
                                <td data-label="নাম"><a class="adm-strong" href="{{ route('admin.messages.show', $m) }}">{{ $m->name }}</a><br><span class="adm-muted">{{ $m->email }}</span></td>
                                <td data-label="ধরন">{{ \App\Models\FormSubmission::TYPES[$m->type] ?? $m->type }}</td>
                                <td data-label="বিষয়">{{ \Illuminate\Support\Str::limit($m->service ?: $m->subject ?: $m->message, 60) }}</td>
                                <td data-label="অবস্থা"><span @class(['adm-badge', 'ok' => $m->status === 'replied', 'new' => $m->status === 'new'])>{{ \App\Models\FormSubmission::STATUSES[$m->status] ?? $m->status }}</span></td>
                                <td data-label="সময়" class="adm-muted">{{ $m->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $items->links('admin.pagination') }}
        @endif
    </div>
@endsection
