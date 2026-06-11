@extends('careers.layout')

@section('title', 'Praca dla kierowców zawodowych - '.($tenant?->agencyName() ?? 'edge recruiting'))
@section('description', 'Sprawdzone oferty pracy dla kierowców zawodowych (kat. C, C+E, ADR) w Niemczech i Polsce. Bezpośrednie zatrudnienie, kontakt w 24h, zero opłat. Aplikuj online.')

@section('content')
    {{-- Hero ze zdjęciem --}}
    <section class="hero">
        <div class="hero-media cover">
            <img src="{{ $tenant?->careersHeroImage() ?? config('rekruter.hero_image') }}" alt="" loading="eager" onerror="this.style.display='none'">
        </div>
        <div class="wrap">
            <span class="kicker light"><span class="mk"></span> {{ $tenant?->careersText('hero_kicker') }}</span>
            <h1>{{ $tenant?->careersText('hero_title') }}</h1>
            <p class="lead">{{ $tenant?->careersText('hero_lead') }}</p>
            <div class="cta">
                <a href="#oferty" class="btn btn-accent">{{ $tenant?->careersText('hero_cta') }}</a>
                @if ($tenant?->agencyPhone())
                    <a href="tel:{{ preg_replace('/\s+/', '', $tenant->agencyPhone()) }}" class="btn btn-glass">Zadzwoń: {{ $tenant->agencyPhone() }}</a>
                @endif
            </div>
        </div>
    </section>

    {{-- Wyszukiwarka --}}
    <div class="searchbar">
        <div class="wrap">
            <form method="GET" action="{{ route('careers.index') }}#oferty">
                <div class="field has-ic">
                    <svg class="lead-ic" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Szukaj: C+E, Niemcy, ADR, tandem…">
                </div>
                <div class="field">
                    <select name="category">
                        <option value="">Kategoria</option>
                        @foreach ($categories as $c)<option value="{{ $c }}" @selected($filters['category'] === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <select name="country">
                        <option value="">Kraj</option>
                        @foreach ($countries as $c)<option value="{{ $c }}" @selected($filters['country'] === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div class="go"><button type="submit" class="btn btn-dark">Szukaj</button></div>
            </form>
        </div>
    </div>

    {{-- Oferty --}}
    <section class="section" id="oferty">
        <div class="wrap">
            @php $anyFilter = $filters['q'] || $filters['category'] || $filters['country'] || $filters['system']; @endphp

            <div class="section-head" data-reveal>
                <span class="kicker"><span class="mk"></span> Oferty pracy</span>
                <h2>{{ $anyFilter ? 'Wyniki wyszukiwania' : 'Aktualne oferty' }}</h2>
                <p>{{ $offers->total() }} {{ \App\Support\Plural::pl($offers->total(), 'oferta', 'oferty', 'ofert') }} dla kierowców zawodowych</p>
            </div>

            @if ($anyFilter)
                <div class="active-filters">
                    @foreach (array_filter($filters) as $k => $v)
                        <span class="fpill">{{ $v }}
                            <a href="{{ route('careers.index', array_merge(array_filter($filters), [$k => null])) }}#oferty" aria-label="Usuń"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg></a>
                        </span>
                    @endforeach
                    <a href="{{ route('careers.index') }}#oferty" class="fpill" style="color:var(--muted)">Wyczyść</a>
                </div>
            @endif

            @if ($offers->count())
                <div class="offers-table" data-reveal>
                    <div class="ot-head">
                        <div>Stanowisko</div>
                        <div>Lokalizacja</div>
                        <div>System</div>
                        <div class="ot-r">Wynagrodzenie</div>
                        <div></div>
                    </div>
                    @foreach ($offers as $offer)
                        @php
                            $loc = collect([$offer->region_base, $offer->country])->filter()->implode(', ') ?: ($offer->location ?: '-');
                            $cats = is_array($offer->required_categories) ? $offer->required_categories : [];
                            $cat = count($cats) ? 'Kat. '.implode(' / ', $cats) : 'Kierowca';
                            $salary = trim((string) $offer->salary_amount) !== '' ? trim($offer->salary_amount.' '.$offer->currency) : null;
                        @endphp
                        <a href="{{ $offer->publicPath() }}" class="ot-row">
                            <div class="ot-title">
                                <div class="ot-cat">{{ $cat }}</div>
                                <div class="ot-name">{{ $offer->title }}</div>
                                <div class="ot-sub">{{ $loc }}@if ($offer->work_system) · System {{ $offer->work_system }}@endif</div>
                            </div>
                            <div class="ot-cell">{{ $loc }}</div>
                            <div class="ot-cell">{{ $offer->work_system ?: '-' }}</div>
                            <div class="ot-cell ot-sal">
                                @if ($salary)<b>{{ $salary }}</b><span>na rękę</span>@else<span>do uzgodnienia</span>@endif
                            </div>
                            <span class="ot-arrow"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                        </a>
                    @endforeach
                </div>
                {{ $offers->links('careers.partials.pagination') }}
            @else
                <div class="empty">
                    <b>Brak ofert dla wybranych kryteriów</b>
                    <p>Zmień filtry albo <a href="{{ route('careers.index') }}" style="color:var(--ink);text-decoration:underline">zobacz wszystkie oferty</a>.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Zostaw numer - oddzwonimy --}}
    <section class="section" id="oddzwonimy" style="padding-top:0">
        <div class="wrap">
            <div class="callback" data-reveal>
                <div class="cb-text">
                    <span class="kicker light"><span class="mk"></span> Szybki kontakt</span>
                    <h2>Nie znalazłeś oferty dla siebie?</h2>
                    <p>Zostaw numer - oddzwonimy i dopasujemy pracę do Twoich uprawnień, doświadczenia i oczekiwań. Bez zobowiązań.</p>
                </div>
                <div class="cb-form">
                    @if (session('callback_ok'))
                        <div class="cb-ok">
                            <span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                            <div><b>Dziękujemy!</b><br>Mamy Twój numer - oddzwonimy najszybciej, jak to możliwe.</div>
                        </div>
                    @else
                        @if ($errors->any())
                            <div class="cb-err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                        @endif
                        <form method="POST" action="{{ route('careers.callback') }}">
                            @csrf
                            <input type="text" name="company" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Imię (opcjonalnie)" aria-label="Imię">
                            <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="+48 600 100 200" aria-label="Numer telefonu">
                            <label class="consent">
                                <input type="checkbox" name="consent" value="1" required>
                                <span>Wyrażam zgodę na kontakt telefoniczny i przetwarzanie danych w celach rekrutacyjnych (RODO).</span>
                            </label>
                            <button type="submit" class="btn btn-accent btn-block">Poproś o telefon</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Dlaczego my --}}
    <section class="section soft">
        <div class="wrap">
            <div class="section-head" data-reveal>
                <span class="kicker"><span class="mk"></span> {{ $tenant?->careersText('values_kicker') }}</span>
                <h2>{{ $tenant?->careersText('values_title') }}</h2>
            </div>
            <div class="values">
                <div class="value" data-reveal>
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg></span>
                    <div><h3>{{ $tenant?->careersText('value1_title') }}</h3><p>{{ $tenant?->careersText('value1_text') }}</p></div>
                </div>
                <div class="value" data-reveal>
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <div><h3>{{ $tenant?->careersText('value2_title') }}</h3><p>{{ $tenant?->careersText('value2_text') }}</p></div>
                </div>
                <div class="value" data-reveal>
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    <div><h3>{{ $tenant?->careersText('value3_title') }}</h3><p>{{ $tenant?->careersText('value3_text') }}</p></div>
                </div>
            </div>
        </div>
    </section>
@endsection
