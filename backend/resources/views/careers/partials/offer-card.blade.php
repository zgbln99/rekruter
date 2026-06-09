@php
    $loc = collect([$offer->region_base, $offer->country])->filter()->implode(', ') ?: ($offer->location ?: null);
    $salary = trim((string) $offer->salary_amount) !== '' ? trim($offer->salary_amount.' '.$offer->currency) : null;
    $cats = is_array($offer->required_categories) ? $offer->required_categories : [];
    $eyebrow = count($cats) ? 'Kat. '.implode(' / ', $cats) : 'Kierowca';
    $zestaw = $offer->trailer_type ?: $offer->vehicle_type;
@endphp
<a href="{{ $offer->publicPath() }}" class="offer-row">
    <div class="o-main">
        <div class="o-cat">{{ $eyebrow }}</div>
        <h3>{{ $offer->title }}</h3>
        <div class="o-meta">
            @if ($loc)
                <span class="m"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.3-7-11a7 7 0 1 1 14 0c0 5.7-7 11-7 11Z"/><circle cx="12" cy="10" r="2.4"/></svg>{{ $loc }}</span>
            @endif
            @if ($offer->work_system)
                <span class="m"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $offer->work_system }}</span>
            @endif
            @if ($zestaw)
                <span class="m"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3M20 17h2v-3.3a4 4 0 0 0-1.2-2.9L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2"/><circle cx="17.5" cy="17.5" r="2"/></svg>{{ $zestaw }}</span>
            @endif
        </div>
    </div>
    <div class="o-side">
        @if ($salary)
            <div class="o-salary"><b>{{ $salary }}</b><span>na rękę</span></div>
        @endif
        <span class="o-arrow">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
    </div>
</a>
