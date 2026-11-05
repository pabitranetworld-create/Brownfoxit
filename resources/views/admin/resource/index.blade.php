@extends('admin.layout')

@section('title', $def['title'])

@section('content')
    <div class="adm-card">
        <div class="adm-toolbar">
            <form method="GET" class="adm-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input name="q" value="{{ $search }}" placeholder="খুঁজুন…">
            </form>
            <a href="{{ route('admin.resource.create', $key) }}" class="adm-btn"><i class="fa-solid fa-plus"></i> নতুন {{ $def['singular'] }}</a>
        </div>

        @if ($items->isEmpty())
            <div class="adm-empty"><i class="fa-regular fa-folder-open"></i><p>এখনো কিছু নেই।</p></div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            @foreach ($def['columns'] as $col)<th>{{ $col['label'] ?? '' }}</th>@endforeach
                            <th class="r">কাজ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                @foreach ($def['columns'] as $col)
                                    @php($v = data_get($item, $col['key']))
                                    <td data-label="{{ $col['label'] ?? '' }}">
                                        @switch($col['type'] ?? 'text')
                                            @case('image')
                                                @if ($v)<img class="adm-thumb" src="{{ media($v) }}" alt="">@else<span class="adm-thumb none"><i class="fa-regular fa-image"></i></span>@endif
                                                @break
                                            @case('icon')
                                                <span class="adm-ic"><i class="{{ $v }}"></i></span>
                                                @break
                                            @case('bool')
                                                <span @class(['adm-badge', 'ok' => $v])>{{ $v ? 'হ্যাঁ' : 'না' }}</span>
                                                @break
                                            @case('date')
                                                {{ $v?->format('d M Y') }}
                                                @break
                                            @default
                                                @if ($col['key'] === $def['fields'][0]['key'])
                                                    <a href="{{ route('admin.resource.edit', [$key, $item->id]) }}" class="adm-strong">{{ \Illuminate\Support\Str::limit((string) $v, 70) }}</a>
                                                @else
                                                    {{ \Illuminate\Support\Str::limit((string) $v, 50) }}
                                                @endif
                                        @endswitch
                                    </td>
                                @endforeach
                                <td class="r adm-row-actions">
                                    <a href="{{ route('admin.resource.edit', [$key, $item->id]) }}" class="adm-icon-btn" title="এডিট"><i class="fa-solid fa-pen"></i></a>
                                    <form method="POST" action="{{ route('admin.resource.destroy', [$key, $item->id]) }}" data-confirm="সত্যিই মুছে ফেলবেন? এটা আর ফেরত আনা যাবে না।">
                                        @csrf
                                        @method('DELETE')
                                        <button class="adm-icon-btn danger" title="মুছুন"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="adm-pager">{{ $items->links('admin.pagination') }}</div>
        @endif
    </div>
@endsection
