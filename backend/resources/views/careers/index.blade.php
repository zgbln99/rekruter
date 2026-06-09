@extends('careers.layout')

@section('title', 'Praca dla kierowców zawodowych — '.($tenant?->agencyName() ?? 'edge recruiting'))
@section('description', 'Sprawdzone oferty pracy dla kierowców zawodowych (kat. C, C+E, ADR) w Niemczech i Polsce. Bezpośrednie zatrudnienie, kontakt w 24h, zero opłat. Aplikuj online.')

@section('content')
    {{-- Hero --}}
    <section class="hero">
        <div class="wrap">
            <span class="eyebrow"><span class="dot"></span> Agencja pracy dla kierowców</span>
            <h1>Dobra praca <span class="soft">za kierownicą.</span></h1>
            <p class="lead">Sprawdzone oferty u solidnych pracodawców w Niemczech i Polsce. Bezpośrednie zatrudnienie, jasne warunki, kontakt w 24 godziny.</p>
            <div class="cta">
                <a href="#oferty" class="btn btn-dark">Zobacz oferty</a>
                @if ($tenant?->agencyPhone())
                    <a href="tel:{{ preg_replace('/\s+/', '', $tenant->agencyPhone()) }}" class="btn btn-ghost">Zadzwoń: {{ $tenant->agencyPhone() }}</a>
                @endif
            </div>
            <div class="trust">
                <div class="t"><b>{{ $total }}</b><span>aktywnych ofert</span></div>
                <div class="t"><b>24h</b><span>czas kontaktu</span></div>
                <div class="t"><b>0 zł</b><span>kosztów dla kierowcy</span></div>
            </div>
        </div>
    </section>

    {{-- Oferty --}}
    <section class="section" id="oferty">
        <div class="wrap">
            @php $anyFilter = $filters['q'] || $filters['category'] || $filters['country'] || $filters['system']; @endphp

            <div class="section-head">
                <div>
                    <span class="eyebrow"><span class="dot"></span> Oferty pracy</span>
                    <h2 style="margin-top:14px">{{ $anyFilter ? 'Wyniki wyszukiwania' : 'Aktualne oferty' }}</h2>
                </div>
                <span class="count">{{ $offers->total() }} {{ \Illuminate\Support\Str::plural('oferta', $offers->total()) }}</span>
            </div>

            <form class="toolbar" method="GET" action="{{ route('careers.index') }}">
                <div class="search-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Szukaj: C+E, Niemcy, tandem, ADR…">
                </div>
                <select name="category" onchange="this.form.submit()">
                    <option value="">Kategoria</option>
                    @foreach ($categories as $c)<option value="{{ $c }}" @selected($filters['category'] === $c)>{{ $c }}</option>@endforeach
                </select>
                <select name="country" onchange="this.form.submit()">
                    <option value="">Kraj</option>
                    @foreach ($countries as $c)<option value="{{ $c }}" @selected($filters['country'] === $c)>{{ $c }}</option>@endforeach
                </select>
                <div class="go"><button type="submit" class="btn btn-dark">Szukaj</button></div>
            </form>

            @if ($anyFilter)
                <div class="active-filters">
                    @foreach (array_filter($filters) as $k => $v)
                        <span class="fpill">{{ $v }}
                            <a href="{{ route('careers.index', array_merge(array_filter($filters), [$k => null])) }}" aria-label="Usuń">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </a>
                        </span>
                    @endforeach
                    <a href="{{ route('careers.index') }}" class="fpill" style="color:var(--muted)">Wyczyść</a>
                </div>
            @endif

            @if ($offers->count())
                <div class="offers">
                    @foreach ($offers as $offer)
                        @include('careers.partials.offer-card', ['offer' => $offer])
                    @endforeach
                </div>
                {{ $offers->links('careers.partials.pagination') }}
            @else
                <div class="offers" style="border-top:0">
                    <div class="empty">
                        <b>Brak ofert dla wybranych kryteriów</b>
                        <p>Zmień filtry albo <a href="{{ route('careers.index') }}" style="color:var(--ink);text-decoration:underline">zobacz wszystkie oferty</a>.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Dlaczego my --}}
    <section class="section alt">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="eyebrow"><span class="dot"></span> Dlaczego my</span>
                    <h2 style="margin-top:14px">Konkretnie i bez ściemy</h2>
                </div>
            </div>
            <div class="values">
                <div class="value">
                    <div class="n">01</div>
                    <h3>Bezpośrednie zatrudnienie</h3>
                    <p>Kierujemy Cię prosto do pracodawcy. Jasna umowa, pewne i terminowe wynagrodzenie.</p>
                </div>
                <div class="value">
                    <div class="n">02</div>
                    <h3>Kontakt w 24 godziny</h3>
                    <p>Zostaw zgłoszenie lub zadzwoń — odzywamy się szybko i mówimy po polsku.</p>
                </div>
                <div class="value">
                    <div class="n">03</div>
                    <h3>Zero opłat dla kierowcy</h3>
                    <p>Rekrutacja jest dla Ciebie całkowicie bezpłatna. Płaci pracodawca, nie Ty.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="section">
        <div class="wrap">
            <div class="cta-band">
                <h2>Gotowy na nową trasę?</h2>
                <p>Wybierz ofertę i aplikuj w minutę albo zadzwoń — resztą zajmiemy się my.</p>
                @if ($tenant?->agencyPhone())
                    <a href="tel:{{ preg_replace('/\s+/', '', $tenant->agencyPhone()) }}" class="btn btn-light">Zadzwoń: {{ $tenant->agencyPhone() }}</a>
                @else
                    <a href="#oferty" class="btn btn-light">Przeglądaj oferty</a>
                @endif
            </div>
        </div>
    </section>
@endsection
