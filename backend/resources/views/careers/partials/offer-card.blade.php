@php
    $loc = collect([$offer->country, $offer->region_base])->filter()->implode(' · ') ?: ($offer->location ?: '—');
    $salary = trim((string) $offer->salary_amount) !== '' ? trim($offer->salary_amount.' '.$offer->currency) : null;
    $cats = is_array($offer->required_categories) ? $offer->required_categories : [];
@endphp
<a href="{{ $offer->publicPath() }}" class="offer-card">
    <div class="oc-top">
        <div style="min-width:0">
            <h3>{{ $offer->title }}</h3>
            @if ($offer->company?->name)
                <div class="company">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-3"/></svg>
                    {{ $offer->company->name }}
                </div>
            @endif
        </div>
        <span class="badge-open">Nabór</span>
    </div>

    <div class="offer-meta">
        <div class="row">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.3-7-11a7 7 0 1 1 14 0c0 5.7-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            {{ $loc }}
        </div>
        @if ($offer->work_system)
            <div class="row">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                {{ $offer->work_system }}
            </div>
        @endif
        @if ($salary)
            <div class="row salary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
                {{ $salary }}
            </div>
        @endif
    </div>

    @if (count($cats))
        <div class="cats">
            @foreach ($cats as $c)<span class="cat">{{ $c }}</span>@endforeach
            @if ($offer->requirements['adr'] ?? false)<span class="cat adr">ADR</span>@endif
        </div>
    @endif

    <div class="oc-foot">
        <span class="more">Zobacz ofertę
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </span>
    </div>
</a>
