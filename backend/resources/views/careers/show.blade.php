@extends('careers.layout')

@php
    $loc = collect([$offer->region_base, $offer->country])->filter()->implode(', ') ?: ($offer->location ?: null);
    $salary = trim((string) $offer->salary_amount) !== '' ? trim($offer->salary_amount) : null;
    $barSalary = $salary ? trim($salary.' '.$offer->currency) : null;
    $barTitle = $offer->title;
    $cats = is_array($offer->required_categories) ? $offer->required_categories : [];
    $eyebrow = count($cats) ? 'Kat. '.implode(' / ', $cats) : 'Praca dla kierowcy';
    $desc = \App\Support\Html\SafeHtml::clean($offer->public_description);
    $faq = is_array($offer->faq) ? array_filter($offer->faq, fn ($f) => ! empty($f['q'])) : [];
    $agencyPhone = $tenant?->agencyPhone();
    $waPhone = $agencyPhone ? preg_replace('/\D+/', '', $agencyPhone) : null;
    $metaDesc = \Illuminate\Support\Str::limit(trim(strip_tags($desc)) ?: $offer->title.($loc ? ' — '.$loc : ''), 155);

    $ic = [
        'lic' => '<path d="M3 5h18v14H3zM7 9h4M7 13h6"/><circle cx="17" cy="10" r="2"/>',
        'pin' => '<path d="M12 21s-7-5.3-7-11a7 7 0 1 1 14 0c0 5.7-7 11-7 11Z"/><circle cx="12" cy="10" r="2.4"/>',
        'truck' => '<path d="M10 17h4V5H2v12h3M20 17h2v-3.3a4 4 0 0 0-1.2-2.9L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2"/><circle cx="17.5" cy="17.5" r="2"/>',
        'route' => '<circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M8 19h7a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'home' => '<path d="M3 11l9-8 9 8M5 10v10h14V10"/>',
        'doc' => '<path d="M14 3H6v18h12V7l-4-4Z"/><path d="M14 3v4h4"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 2.5 15 0 18M12 3c-2.5 2.5-2.5 15 0 18"/>',
        'cash' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
    ];
    $facts = [];
    if (count($cats)) $facts[] = ['Prawo jazdy', implode(', ', $cats), $ic['lic']];
    if ($loc) $facts[] = ['Lokalizacja', $loc, $ic['pin']];
    if ($offer->trailer_type || $offer->vehicle_type) $facts[] = ['Zestaw', $offer->trailer_type ?: $offer->vehicle_type, $ic['truck']];
    if ($offer->routes_info) $facts[] = ['Trasa', \Illuminate\Support\Str::limit($offer->routes_info, 70), $ic['route']];
    if ($offer->work_system) $facts[] = ['System pracy', $offer->work_system, $ic['clock']];
    if ($offer->accommodation) $facts[] = ['Zakwaterowanie', \Illuminate\Support\Str::limit($offer->accommodation, 90), $ic['home']];
    if ($offer->contract_type) $facts[] = ['Typ umowy', $offer->contract_type, $ic['doc']];
    if ($offer->required_language) $facts[] = ['Język obcy', $offer->required_language, $ic['globe']];
@endphp

@section('title', $offer->title.($loc ? ' — '.$loc : '').' | Praca dla kierowców')
@section('og_title', $offer->title.($loc ? ' — '.$loc : ''))
@section('description', $metaDesc)

@push('head')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    {{-- Hero oferty ze zdjęciem --}}
    <section class="detail-hero">
        <div class="hero-media cover">
            <img src="{{ $offer->coverImage() }}" alt="" loading="eager" onerror="this.style.display='none'">
        </div>
        <div class="wrap">
            <a href="{{ route('careers.index') }}" class="breadcrumb">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Wszystkie oferty
            </a>
            <div class="dh-grid">
                <div class="dh-main">
                    <span class="kicker light"><span class="mk"></span> {{ $eyebrow }}</span>
                    <h1>{{ $offer->title }}</h1>
                    <div class="d-sub">
                        @if ($offer->company?->name)<span class="m">{{ $offer->company->name }}</span>@endif
                        @if ($loc)<span class="m"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.3-7-11a7 7 0 1 1 14 0c0 5.7-7 11-7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg>{{ $loc }}</span>@endif
                    </div>
                    <div class="cta">
                        <a href="#aplikuj" class="btn btn-light">Aplikuj na tę ofertę</a>
                        @if ($agencyPhone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}" class="btn btn-glass">Zadzwoń: {{ $agencyPhone }}</a>
                        @endif
                    </div>
                </div>
                @if ($barSalary)
                    <div class="dh-salary">
                        <span class="dhs-label">Wynagrodzenie</span>
                        <span class="dhs-amt">{{ $barSalary }}</span>
                        <span class="dhs-suf">na rękę / miesiąc</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="detail-body">
        <div class="wrap">
            <div class="detail-content">
                @if (count($facts))
                    <div class="facts">
                        @foreach ($facts as [$k, $v, $icon])
                            <div class="fact">
                                <span class="ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></span>
                                <span><span class="k">{{ $k }}</span><span class="v" style="display:block">{{ $v }}</span></span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="prose-wrap">
                    @if ($desc)
                        <div class="block-title">Opis stanowiska</div>
                        <div class="prose">{!! $desc !!}</div>
                    @endif

                    @if (count($faq))
                        <div class="block-title">Najczęstsze pytania</div>
                        <div class="faq">
                            @foreach ($faq as $f)
                                <details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] ?? '' }}</p></details>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- APLIKACJA — pełnoszeroka sekcja --}}
            <section class="apply" id="aplikuj">
                <div class="apply-inner">
                    <div class="apply-head">
                        <span class="kicker"><span class="mk"></span> Aplikacja</span>
                        <h2>Zainteresowany? Aplikuj w minutę.</h2>
                        <p>Wypełnij krótki formularz albo skontaktuj się z nami od razu — oddzwonimy.</p>
                    </div>

                    @if ($agencyPhone)
                        <div class="contacts">
                            <a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}" class="btn btn-dark">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .8-.3 1l-2.1 2.2z"/></svg>
                                {{ $agencyPhone }}
                            </a>
                            @if ($waPhone)
                                <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode('Dzień dobry, piszę w sprawie oferty: '.$offer->title) }}" target="_blank" rel="noopener" class="btn btn-wa">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2Zm5.3 14c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 .9-2.2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.4.6c-.2.2-.3.4-.1.7.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.9.9c.3.1.4.2.5.3.1.3.1.7-.1 1.4Z"/></svg>
                                    Napisz na WhatsApp
                                </a>
                            @endif
                        </div>
                        <div class="divider">albo wyślij zgłoszenie online</div>
                    @endif

                    @if (session('applied'))
                        <div class="flash-ok">
                            <span class="ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                            <div><b>Dziękujemy!</b><br>Twoja aplikacja została wysłana. Odezwiemy się wkrótce.</div>
                        </div>
                    @else
                        @if ($errors->any())
                            <div class="err-box">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                        @endif
                        <form method="POST" action="{{ route('careers.apply', ['jobPosting' => $offer->id]) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="text" name="company" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                            <div class="form-grid">
                                <div class="form-field"><label>Imię *</label><input name="first_name" value="{{ old('first_name') }}" required></div>
                                <div class="form-field"><label>Nazwisko *</label><input name="last_name" value="{{ old('last_name') }}" required></div>
                                <div class="form-field"><label>Telefon *</label><input name="phone" type="tel" value="{{ old('phone') }}" required placeholder="+48 600 100 200"></div>
                                <div class="form-field"><label>E-mail</label><input name="email" type="email" value="{{ old('email') }}"></div>
                                <div class="form-field full">
                                    <label>Posiadane kategorie</label>
                                    <div class="cat-check">
                                        @foreach (['B','C','C+E','D'] as $c)
                                            <label><input type="checkbox" name="categories[]" value="{{ $c }}"><span>{{ $c }}</span></label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="form-field full"><label>Wiadomość / doświadczenie</label><textarea name="message" rows="3" placeholder="Krótko o sobie, dyspozycyjność…">{{ old('message') }}</textarea></div>
                                <div class="form-field full"><label>CV (opcjonalnie)</label><input type="file" name="cv" class="file-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
                                <div class="form-field full">
                                    <label class="consent">
                                        <input type="checkbox" name="consent" value="1" required>
                                        <span>Wyrażam zgodę na przetwarzanie moich danych osobowych w celu przeprowadzenia procesu rekrutacji (RODO).</span>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-accent btn-block" style="margin-top:8px">Wyślij aplikację</button>
                        </form>
                    @endif
                </div>
            </section>

            @if ($related->count())
                <div class="block-title" style="margin-top:64px">Podobne oferty</div>
                <div class="offers-grid">
                    @foreach ($related as $offer)
                        @include('careers.partials.offer-card', ['offer' => $offer])
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Pływający pasek aplikowania --}}
    @unless (session('applied'))
        <div class="apply-bar">
            <div class="ab-info">
                @if ($barSalary)
                    <b>{{ $barSalary }}</b><span>na rękę / miesiąc</span>
                @else
                    <b>{{ \Illuminate\Support\Str::limit($barTitle, 24) }}</b><span>{{ $loc }}</span>
                @endif
            </div>
            <a href="#aplikuj" class="btn btn-accent">Aplikuj teraz</a>
        </div>
    @endunless
@endsection
