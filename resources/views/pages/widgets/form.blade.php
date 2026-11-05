@php
    $type = $d['form_type'] === 'quote' ? 'quote' : 'contact';
    $lines = fn ($v) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $v))));
    $services = $type === 'quote' ? \App\Models\Service::active()->where('show_in_catalog', true)->ordered()->get(['title', 'url']) : collect();
    $pre = (string) request('service');
    $selected = $services->first(fn ($s) => $pre !== '' && $s->url && basename($s->url) === $pre)?->title;
    $fid = 'f'.substr($wid ?? md5($type), 0, 6);
    $sent = session('form_sent');
@endphp
<div class="pb-form-wrap{{ $sent ? ' is-sent' : '' }}">
    @if (filled($d['title']))<h3 class="pb-form-title">{{ $d['title'] }}</h3>@endif
    <form class="pb-form" method="post" action="{{ route('forms.submit', $type) }}" novalidate data-pb-form>
        @csrf
        <input type="hidden" name="_t" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
        <div class="pb-hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        @if ($errors->has('form'))<p class="pb-form-alert">{{ $errors->first('form') }}</p>@endif

        <div class="pb-fgrid">
            <div class="pb-field"><label for="{{ $fid }}-name">Your name <span>*</span></label><input id="{{ $fid }}-name" name="name" required maxlength="120" autocomplete="name" value="{{ old('name') }}"><small class="pb-err"></small></div>
            <div class="pb-field"><label for="{{ $fid }}-email">Email <span>*</span></label><input id="{{ $fid }}-email" type="email" name="email" required maxlength="190" autocomplete="email" value="{{ old('email') }}"><small class="pb-err"></small></div>
            @if ($type === 'quote')
                <div class="pb-field"><label for="{{ $fid }}-company">Company / organisation</label><input id="{{ $fid }}-company" name="company" maxlength="190" autocomplete="organization" value="{{ old('company') }}"><small class="pb-err"></small></div>
                <div class="pb-field"><label for="{{ $fid }}-phone">Phone / WhatsApp</label><input id="{{ $fid }}-phone" name="phone" maxlength="60" autocomplete="tel" value="{{ old('phone') }}"><small class="pb-err"></small></div>
                <div class="pb-field pb-full"><label for="{{ $fid }}-service">Service you need <span>*</span></label>
                    <select id="{{ $fid }}-service" name="service" required>
                        <option value="">Choose a service…</option>
                        @foreach ($services as $s)<option @selected(old('service', $selected) === $s->title)>{{ $s->title }}</option>@endforeach
                        <option @selected(old('service') === 'Something else / not sure')>Something else / not sure</option>
                    </select><small class="pb-err"></small></div>
                <div class="pb-field"><label for="{{ $fid }}-budget">Budget</label>
                    <select id="{{ $fid }}-budget" name="budget">@foreach ($lines($d['budgets']) as $o)<option @selected(old('budget') === $o)>{{ $o }}</option>@endforeach</select><small class="pb-err"></small></div>
                <div class="pb-field"><label for="{{ $fid }}-timeline">Timeline</label>
                    <select id="{{ $fid }}-timeline" name="timeline">@foreach ($lines($d['timelines']) as $o)<option @selected(old('timeline') === $o)>{{ $o }}</option>@endforeach</select><small class="pb-err"></small></div>
                <div class="pb-field pb-full"><label for="{{ $fid }}-message">Tell us about the project <span>*</span></label><textarea id="{{ $fid }}-message" name="message" rows="6" required maxlength="8000" placeholder="What should be built or improved, who it is for, and anything that already exists (website, tools, files).">{{ old('message') }}</textarea><small class="pb-err"></small></div>
                <div class="pb-field pb-full"><label for="{{ $fid }}-reference">Reference link (optional)</label><input id="{{ $fid }}-reference" type="url" name="reference" maxlength="500" placeholder="https://… a website, document or design you like" value="{{ old('reference') }}"><small class="pb-err"></small></div>
            @else
                <div class="pb-field pb-full"><label for="{{ $fid }}-subject">Subject</label><input id="{{ $fid }}-subject" name="subject" maxlength="190" value="{{ old('subject') }}"><small class="pb-err"></small></div>
                <div class="pb-field pb-full"><label for="{{ $fid }}-message">Message <span>*</span></label><textarea id="{{ $fid }}-message" name="message" rows="6" required maxlength="8000">{{ old('message') }}</textarea><small class="pb-err"></small></div>
            @endif
            <div class="pb-field pb-full pb-check"><label><input type="checkbox" name="consent" value="1" required @checked(old('consent'))> <span>{{ $d['consent'] }} @if (filled($d['privacy_url']) && link_visible($d['privacy_url']))<a href="{{ link_to_url($d['privacy_url']) }}" target="_blank">Privacy Policy</a>@endif</span></label><small class="pb-err"></small></div>
        </div>
        <button type="submit" class="pb-btn pb-btn-primary pb-form-btn"><span class="t">{{ $d['button'] }}</span><i class="fa-solid fa-paper-plane"></i><span class="pb-spin" aria-hidden="true"></span></button>
        <p class="pb-form-status" role="status" aria-live="polite"></p>
    </form>
    <div class="pb-form-done" role="status" aria-live="polite" @unless ($sent) hidden @endunless>
        <div class="pb-done-ic"><svg viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24"/><path d="M15 27l7 7 15-16"/></svg></div>
        <h3>{{ $d['success_title'] }}</h3>
        <p>{{ $d['success_text'] }}</p>
    </div>
</div>
