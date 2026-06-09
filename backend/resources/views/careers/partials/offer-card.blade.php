@php
    $loc = collect([$offer->region_base, $offer->country])->filter()->implode(', ') ?: ($offer->location ?: null);
    $salary = trim((string) $offer->salary_amount) !== '' ? trim($offer->salary_amount.' '.$offer->currency) : null;
    $cats = is_array($offer->required_categories) ? $offer->required_categories : [];
    // Pigułki: lokalizacja, zestaw, system, typ umowy, trasy.
    $pills = collect([
        $offer->trailer_type,
        $offer->vehicle_type,
        $offer->work_system ? 'System '.$offer->work_system : null,
        $offer->contract_type,
        $offer->routes_info ? \Illuminate\Support\Str::limit($offer->routes_info, 28) : null,
    ])->filter()->unique()->take(5);
@endphp
<a href="{{ $offer->publicPath() }}" class="offer-card">
    <div class="thumb">
        <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 17h4V5H2v12h3M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>
        </svg>
    </div>
    <div class="body">
        <h3>{{ $offer->title }}</h3>
        @if ($salary)
            <div class="salary">{{ $salary }} <small>na rękę</small></div>
        @endif
        <div class="chips">
            @if ($loc)<span class="chip solid">{{ $loc }}</span>@endif
            @foreach ($cats as $c)<span class="chip cat">{{ $c }}</span>@endforeach
            @foreach ($pills as $p)<span class="chip">{{ $p }}</span>@endforeach
        </div>
    </div>
</a>
