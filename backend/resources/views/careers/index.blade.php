@extends('careers.layout')

@section('title', 'Praca dla kierowców — '.($tenant?->agencyName() ?? 'edge recruiting'))
@section('description', 'Aktualne oferty pracy dla kierowców zawodowych (kat. C, C+E, ADR) w Niemczech i Polsce. Aplikuj online lub zadzwoń.')

@section('content')
    <section class="hero">
        <div class="wrap">
            <h1>Praca <span class="hl">bezpośrednio</span> dla kierowców zawodowych</h1>
            <p class="lead">Sprawdzone oferty u sprawdzonego pracodawcy. Aplikuj w minutę albo zadzwoń — resztą zajmiemy się my.</p>
            <div class="stats">
                <div class="stat"><b>{{ $total }}</b><span>aktywnych ofert</span></div>
                <div class="stat"><b>24h</b><span>czas kontaktu</span></div>
                <div class="stat"><b>0 zł</b><span>dla kierowcy</span></div>
            </div>
        </div>
    </section>

    {{-- Wyszukiwarka + filtry --}}
    <div class="searchbar">
        <form method="GET" action="{{ route('careers.index') }}">
            <div class="field search">
                <label for="q">Szukaj oferty</label>
                <div class="search-input-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="np. C+E, Niemcy, tandem, ADR…">
                </div>
            </div>
            <div class="field">
                <label for="category">Kategoria</label>
                <select id="category" name="category">
                    <option value="">Wszystkie</option>
                    @foreach ($categories as $c)<option value="{{ $c }}" @selected($filters['category'] === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="country">Kraj</label>
                <select id="country" name="country">
                    <option value="">Wszystkie</option>
                    @foreach ($countries as $c)<option value="{{ $c }}" @selected($filters['country'] === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="system">System</label>
                <select id="system" name="system">
                    <option value="">Dowolny</option>
                    @foreach ($systems as $s)<option value="{{ $s }}" @selected($filters['system'] === $s)>{{ $s }}</option>@endforeach
                </select>
            </div>
            <div class="field submit">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-brand">Szukaj</button>
            </div>
        </form>
    </div>

    <section class="section">
        <div class="wrap">
            @php $anyFilter = $filters['q'] || $filters['category'] || $filters['country'] || $filters['system']; @endphp
            @if ($anyFilter)
                <div class="active-filters">
                    @foreach (array_filter($filters) as $k => $v)
                        <span class="fpill">{{ $v }}
                            <a href="{{ route('careers.index', array_merge(array_filter($filters), [$k => null])) }}" aria-label="Usuń filtr">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </a>
                        </span>
                    @endforeach
                    <a href="{{ route('careers.index') }}" class="fpill" style="background:var(--surface);color:var(--slate)">Wyczyść</a>
                </div>
            @endif

            <div class="section-head">
                <h2>{{ $anyFilter ? 'Wyniki wyszukiwania' : 'Aktualne oferty pracy' }}</h2>
                <span class="count">{{ $offers->total() }} {{ \Illuminate\Support\Str::plural('oferta', $offers->total()) }}</span>
            </div>

            @if ($offers->count())
                <div class="offers">
                    @foreach ($offers as $offer)
                        @include('careers.partials.offer-card', ['offer' => $offer])
                    @endforeach
                </div>
                <div>{{ $offers->links('careers.partials.pagination') }}</div>
            @else
                <div class="empty">
                    <span class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg></span>
                    <b>Brak ofert dla wybranych kryteriów</b>
                    <p>Zmień filtry albo <a href="{{ route('careers.index') }}" style="color:var(--brand-deep);font-weight:600">zobacz wszystkie</a>.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
