<!DOCTYPE html>
<html lang="{{ $lang ?? 'pl' }}">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif; color: #0f172a; font-size: 11.5px; line-height: 1.5; }
        .page { padding: 46px 52px; }

        /* Nagłówek dokumentu */
        .top { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #0f172a; padding-bottom: 12px; }
        .top .agency { font-size: 18px; font-weight: 800; color: #0f172a; }
        .top .agency .dot { color: #dc2626; }
        .top .agency small { display: block; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #94a3b8; margin-top: 3px; font-weight: 700; }
        .top .meta { text-align: right; font-size: 10px; color: #64748b; line-height: 1.6; }
        .top .meta b { color: #0f172a; }

        /* Tytuł + adresat */
        .title { margin-top: 22px; font-size: 27px; font-weight: 900; letter-spacing: -0.6px; text-transform: uppercase; color: #0f172a; }
        .title .accent { color: #dc2626; }
        .rule { width: 64px; height: 4px; background: #dc2626; border-radius: 2px; margin-top: 10px; }
        .lead { margin-top: 16px; font-size: 13px; color: #334155; }
        .lead .who { font-size: 16px; font-weight: 800; color: #0f172a; }
        .lead .pos { color: #b91c1c; font-weight: 700; }

        /* Sekcje */
        .section { margin-top: 22px; }
        .s-head { font-size: 11px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: #dc2626; padding-bottom: 5px; border-bottom: 1px solid #e5e9f0; margin-bottom: 6px; }

        /* Wiersze label : wartość */
        .row { display: table; width: 100%; padding: 7px 0; border-bottom: 1px solid #f1f5f9; }
        .row:last-child { border-bottom: 0; }
        .row .k { display: table-cell; width: 210px; padding-right: 16px; vertical-align: top; font-size: 10px; letter-spacing: 0.4px; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .row .v { display: table-cell; vertical-align: top; font-size: 12px; color: #0f172a; font-weight: 600; white-space: pre-line; }
        .row .v .muted { color: #94a3b8; font-weight: 500; }
        .row .v.salary { color: #b91c1c; font-weight: 800; font-size: 15px; }
        .row .v.big { font-size: 14px; font-weight: 800; }

        .block { margin-top: 7px; font-size: 11.5px; color: #334155; white-space: pre-line; line-height: 1.6; }

        .two { display: table; width: 100%; table-layout: fixed; margin-top: 2px; }
        .two .c { display: table-cell; width: 50%; vertical-align: top; }
        .two .c:first-child { padding-right: 18px; }

        .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 9.5px; display: flex; justify-content: space-between; }
        .footer b { color: #0f172a; }
    </style>
</head>
<body>
@php
    $region = $offer->region_base ?: $offer->country;
    $vehicle = $offer->vehicle_type ?: $offer->trailer_type;
    $routes = $offer->routes_info ?: $offer->description;
    $salary = trim(($offer->salary_amount ?? '').' '.($offer->currency ?? ''));
    $onsite = $offer->onsite_contact ?: trim(($company?->contact_person ?? '')."\n".($company?->contact_phone ?? '')."\n".($company?->contact_email ?? ''));
    $candidateName = $candidateName ?? null;
    $arrival = ($arrivalOverride ?? null) ?: ($offer->arrival_info ?: ($offer->start_date ? $offer->start_date->format('d.m.Y') : null));

    // Parametry warunków pracy (etykiety z $t).
    $params = [];
    if ($offer->work_system) $params[] = [$t['f_system'], $offer->work_system];
    if ($vehicle) $params[] = [$t['p_vehicle'], $vehicle];
    if ($offer->cargo) $params[] = [$t['p_cargo'], $offer->cargo];
    if ($offer->contract_type) $params[] = [$t['p_contract'], $offer->contract_type];
    if ($offer->points_per_day) $params[] = [$t['p_points'], $offer->points_per_day];
    if ($offer->daily_km) $params[] = [$t['p_km'], $offer->daily_km];
    if ($offer->loading_info) $params[] = [$t['p_loading'], $offer->loading_info];
    if ($offer->required_language) $params[] = [$t['p_language'], $offer->required_language];
@endphp

<div class="page">
    {{-- NAGŁÓWEK --}}
    <div class="top">
        <div class="agency">{{ $agencyName }} <span class="dot">●</span><small>{{ $t['brand_hr'] }}</small></div>
        <div class="meta">
            <div>{{ $t['sub'] }}</div>
            <div><b>{{ $generatedAt }}</b></div>
        </div>
    </div>

    {{-- TYTUŁ + ADRESAT --}}
    <div class="title">{{ $t['title_main'] }} <span class="accent">{{ $t['title_accent'] }}</span></div>
    <div class="rule"></div>
    <div class="lead">
        @if ($candidateName)<span class="who">{{ $candidateName }}</span> — @endif{{ $offer->title }}
        @if ($company?->name)<br>{{ $t['employer'] }}: <b>{{ $company->name }}</b>@if ($region) · {{ $region }}@endif @endif
    </div>

    {{-- STANOWISKO I WARUNKI --}}
    <div class="section">
        <div class="s-head">{{ $t['sec_conditions'] }}</div>
        <div class="row"><div class="k">{{ $t['position'] }}</div><div class="v big">{{ $offer->title }}</div></div>
        @if ($salary)<div class="row"><div class="k">{{ $t['f_salary'] }}</div><div class="v salary">{{ $salary }}</div></div>@endif
        @if ($arrival)<div class="row"><div class="k">{{ $t['f_arrival'] }}</div><div class="v big">{{ $arrival }}</div></div>@endif
        @foreach ($params as $p)
            <div class="row"><div class="k">{{ $p[0] }}</div><div class="v">{{ $p[1] }}</div></div>
        @endforeach
    </div>

    {{-- PRACODAWCA --}}
    @if ($company?->name || $company?->description)
        <div class="section">
            <div class="s-head">{{ $t['employer'] }}</div>
            <div class="row"><div class="k">{{ $t['company_name'] }}</div><div class="v">{{ $company?->name ?? '—' }}@if ($company?->website) <span class="muted">· {{ $company->website }}</span>@endif</div></div>
            @if ($region)<div class="row"><div class="k">{{ $t['region'] }}</div><div class="v">{{ $region }}</div></div>@endif
            @if ($company?->description)<div class="row"><div class="k">{{ $t['about'] }}</div><div class="v" style="font-weight:500;">{{ $company->description }}</div></div>@endif
        </div>
    @endif

    {{-- TRASY / ZAKWATEROWANIE --}}
    @if ($routes || $offer->accommodation)
        <div class="section">
            <div class="s-head">{{ $t['sec_routes'] }}</div>
            @if ($routes)<div class="row"><div class="k">{{ $t['routes'] }}</div><div class="v" style="font-weight:500;">{{ $routes }}</div></div>@endif
            @if ($offer->accommodation)<div class="row"><div class="k">{{ $t['accommodation'] }}</div><div class="v" style="font-weight:500;">{{ $offer->accommodation }}</div></div>@endif
        </div>
    @endif

    {{-- KONTAKT --}}
    <div class="section">
        <div class="s-head">{{ $t['sec_employer'] }}</div>
        <div class="two">
            <div class="c">
                <div class="row" style="border-bottom:0;"><div class="k">{{ $t['contact_onsite'] }}</div></div>
                <div class="block">{{ $onsite ?: '—' }}</div>
            </div>
            <div class="c">
                <div class="row" style="border-bottom:0;"><div class="k">{{ $t['contact_office'] }}</div></div>
                <div class="block"><b>{{ $recruiterName }}</b>@if ($recruiterPhone) · {{ $recruiterPhone }}@endif @if ($recruiterEmail)
{{ $recruiterEmail }}@endif</div>
            </div>
        </div>
    </div>

    {{-- DODATKOWE INFORMACJE --}}
    @if ($offer->public_description)
        <div class="section">
            <div class="s-head">{{ $t['sec_extra'] }}</div>
            <div class="block">{{ $offer->public_description }}</div>
        </div>
    @endif

    <div class="footer">
        <span>{{ $t['footer_by'] }} <b>{{ $agencyName }}</b></span>
        <span>{{ $generatedAt }}</span>
    </div>
</div>
</body>
</html>
