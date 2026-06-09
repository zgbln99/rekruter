@extends('careers.layout')

@section('title', 'Praca dla kierowców zawodowych — '.($tenant?->agencyName() ?? 'edge recruiting'))
@section('description', 'Sprawdzone oferty pracy dla kierowców zawodowych (kat. C, C+E, ADR) w Niemczech i Polsce. Bezpośrednie zatrudnienie, kontakt w 24h, zero opłat. Aplikuj online.')

@section('content')
    {{-- Hero --}}
    <section class="hero">
        <div class="wrap">
            <span class="kicker"><span class="mk">●</span>&nbsp;&nbsp;Agencja pracy dla kierowców</span>
            <h1>Praca za kierownicą<br>w Niemczech <span class="accent">i Polsce.</span></h1>
            <p class="lead">Sprawdzone oferty u solidnych pracodawców. Bezpośrednie zatrudnienie, jasne warunki, kontakt w ciągu 24 godzin — bez pośredników i bez opłat dla kierowcy.</p>
            <div class="cta">
                <a href="#oferty" class="btn btn-dark">Zobacz oferty</a>
                @if ($tenant?->agencyPhone())
                    <a href="tel:{{ preg_replace('/\s+/', '', $tenant->agencyPhone()) }}" class="btn btn-ghost">Zadzwoń</a>
                @endif
            </div>
            <div class="hero-meta">
                <span class="hm"><span class="d"></span> <b>{{ $total }}</b>&nbsp;aktywnych ofert</span>
                <span class="hm">Bezpośrednie zatrudnienie</span>
                <span class="hm">Kontakt w 24h</span>
                <span class="hm">0 zł dla kierowcy</span>
            </div>
        </div>
    </section>

    {{-- Oferty --}}
    <section class="section tight" id="oferty">
        <div class="wrap">
            @php $anyFilter = $filters['q'] || $filters['category'] || $filters['country'] || $filters['system']; @endphp

            <div class="sec-label">
                <span class="kicker">{{ $anyFilter ? 'Wyniki' : 'Aktualne oferty' }}</span>
                <span class="rule"></span>
                <span class="count">{{ sprintf('%02d', $offers->total()) }}</span>
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
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </a>
                        </span>
                    @endforeach
                    <a href="{{ route('careers.index') }}" class="fpill" style="color:var(--muted)">Wyczyść</a>
                </div>
            @endif

            <div class="offers">
                @forelse ($offers as $offer)
                    @include('careers.partials.offer-card', ['offer' => $offer, 'no' => $offers->firstItem() + $loop->index])
                @empty
                    <div class="empty">
                        <b>Brak ofert dla wybranych kryteriów</b>
                        <p>Zmień filtry albo <a href="{{ route('careers.index') }}" style="color:var(--ink);text-decoration:underline">zobacz wszystkie oferty</a>.</p>
                    </div>
                @endforelse
            </div>

            {{ $offers->links('careers.partials.pagination') }}
        </div>
    </section>

    {{-- Kontakt --}}
    <section class="section tight">
        <div class="wrap">
            <div class="contact-strip">
                <h2>Wolisz porozmawiać? Zadzwoń.</h2>
                <div class="cs-side">
                    <span class="kicker">Kontakt bezpośredni</span>
                    @if ($tenant?->agencyPhone())
                        <a href="tel:{{ preg_replace('/\s+/', '', $tenant->agencyPhone()) }}" class="btn btn-accent">{{ $tenant->agencyPhone() }}</a>
                    @else
                        <a href="#oferty" class="btn btn-dark">Przeglądaj oferty</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
