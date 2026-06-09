@extends('careers.layout')

@section('title', 'Praca dla kierowców zawodowych — '.($tenant?->agencyName() ?? 'edge recruiting'))
@section('description', 'Sprawdzone oferty pracy dla kierowców zawodowych (kat. C, C+E, ADR) w Niemczech i Polsce. Bezpośrednie zatrudnienie, kontakt w 24h, zero opłat. Aplikuj online.')

@section('content')
    {{-- Hero ze zdjęciem --}}
    <section class="hero">
        <div class="hero-media cover">
            <img src="{{ config('rekruter.hero_image') }}" alt="" loading="eager" onerror="this.style.display='none'">
        </div>
        <div class="wrap">
            <span class="kicker light"><span class="mk"></span> Agencja pracy dla kierowców</span>
            <h1>Praca za kierownicą, na której można polegać.</h1>
            <p class="lead">Sprawdzone oferty u solidnych pracodawców w Niemczech i Polsce. Bezpośrednie zatrudnienie, jasne warunki, kontakt w 24 godziny — bez pośredników i bez opłat dla kierowcy.</p>
            <div class="cta">
                <a href="#oferty" class="btn btn-accent">Zobacz {{ $total }} ofert</a>
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

            <div class="section-head">
                <span class="kicker"><span class="mk"></span> Oferty pracy</span>
                <h2>{{ $anyFilter ? 'Wyniki wyszukiwania' : 'Aktualne oferty' }}</h2>
                <p>{{ $offers->total() }} {{ \Illuminate\Support\Str::plural('oferta', $offers->total()) }} dla kierowców zawodowych</p>
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

            <div class="offers-grid">
                @forelse ($offers as $offer)
                    @include('careers.partials.offer-card', ['offer' => $offer])
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

    {{-- Dlaczego my --}}
    <section class="section soft">
        <div class="wrap">
            <div class="section-head">
                <span class="kicker"><span class="mk"></span> Dlaczego my</span>
                <h2>Robimy to konkretnie</h2>
            </div>
            <div class="values">
                <div class="value">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg></span>
                    <div><h3>Bezpośrednie zatrudnienie</h3><p>Kierujemy Cię prosto do pracodawcy. Jasna umowa i pewne, terminowe wynagrodzenie.</p></div>
                </div>
                <div class="value">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <div><h3>Kontakt w 24 godziny</h3><p>Zostaw zgłoszenie lub zadzwoń — odzywamy się szybko i rozmawiamy po polsku.</p></div>
                </div>
                <div class="value">
                    <span class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                    <div><h3>Zero opłat dla kierowcy</h3><p>Rekrutacja jest dla Ciebie całkowicie bezpłatna. Płaci pracodawca, nie Ty.</p></div>
                </div>
            </div>
        </div>
    </section>
@endsection
