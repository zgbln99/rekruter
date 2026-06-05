<!DOCTYPE html>
<html lang="{{ $lang ?? 'pl' }}">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif; color: #0f172a; font-size: 11.5px; line-height: 1.45; }
        .page { padding: 40px 46px; }

        /* Nagłówek */
        .head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 14px; }
        .head .agency { font-size: 18px; font-weight: 800; color: #0f172a; }
        .head .agency .dot { color: #dc2626; }
        .head .agency small { display: block; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #94a3b8; margin-top: 3px; font-weight: 700; }
        .head .meta { text-align: right; font-size: 10px; color: #64748b; line-height: 1.7; }
        .head .meta b { color: #0f172a; }

        .title-bar { background: #dc2626; color: #fff; padding: 12px 16px; border-radius: 8px 8px 0 0; }
        .title-bar .t { font-size: 20px; font-weight: 900; letter-spacing: 0.4px; text-transform: uppercase; }
        .title-bar .for { font-size: 12px; margin-top: 3px; color: #fde2e2; }
        .title-bar .for b { color: #fff; }

        /* Tabela */
        table.doc { width: 100%; border-collapse: collapse; }
        table.doc td { border: 1px solid #cbd5e1; padding: 8px 13px; vertical-align: top; }
        tr.sec td { background: #0f172a; color: #fff; font-weight: 800; font-size: 10.5px; letter-spacing: 1.2px; text-transform: uppercase; border-color: #0f172a; padding: 7px 13px; }
        td.k { width: 34%; background: #f6f8fb; font-size: 10px; letter-spacing: 0.4px; text-transform: uppercase; color: #475569; font-weight: 700; }
        td.v { font-size: 12px; color: #0f172a; font-weight: 600; white-space: pre-line; }
        td.v .muted { color: #94a3b8; font-weight: 500; }
        td.v.salary { color: #b91c1c; font-weight: 800; font-size: 14px; }
        td.full { white-space: pre-line; font-weight: 500; color: #334155; }

        .footer { margin-top: 18px; color: #94a3b8; font-size: 9.5px; display: flex; justify-content: space-between; }
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
    $recruiter = trim(($recruiterName ?? '').($recruiterPhone ? ' · '.$recruiterPhone : '').($recruiterEmail ? "\n".$recruiterEmail : ''));

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
    <div class="head">
        <div class="agency">{{ $agencyName }} <span class="dot">●</span><small>{{ $t['brand_hr'] }}</small></div>
        <div class="meta"><div>{{ $t['sub'] }}</div><div><b>{{ $generatedAt }}</b></div></div>
    </div>

    {{-- PASEK TYTUŁOWY --}}
    <div class="title-bar">
        <div class="t">{{ $t['title_main'] }} {{ $t['title_accent'] }}</div>
        <div class="for">
            @if ($candidateName){{ $t['forwho'] }}: <b>{{ $candidateName }}</b> · @endif{{ $offer->title }}
        </div>
    </div>

    {{-- TABELA --}}
    <table class="doc">
        {{-- Pracodawca --}}
        <tr class="sec"><td colspan="2">{{ $t['employer'] }}</td></tr>
        <tr><td class="k">{{ $t['company_name'] }}</td><td class="v">{{ $company?->name ?? '—' }}@if ($company?->website) <span class="muted">· {{ $company->website }}</span>@endif</td></tr>
        @if ($region)<tr><td class="k">{{ $t['region'] }}</td><td class="v">{{ $region }}</td></tr>@endif
        @if ($company?->description)<tr><td class="k">{{ $t['about'] }}</td><td class="full">{{ $company->description }}</td></tr>@endif
        @if ($arrival)<tr><td class="k">{{ $t['f_arrival'] }}</td><td class="v">{{ $arrival }}</td></tr>@endif

        {{-- Stanowisko i warunki --}}
        <tr class="sec"><td colspan="2">{{ $t['sec_conditions'] }}</td></tr>
        <tr><td class="k">{{ $t['position'] }}</td><td class="v">{{ $offer->title }}</td></tr>
        @if ($salary)<tr><td class="k">{{ $t['f_salary'] }}</td><td class="v salary">{{ $salary }}</td></tr>@endif
        @foreach ($params as $p)
            <tr><td class="k">{{ $p[0] }}</td><td class="v">{{ $p[1] }}</td></tr>
        @endforeach

        {{-- Trasy / zakwaterowanie --}}
        @if ($routes || $offer->accommodation)
            <tr class="sec"><td colspan="2">{{ $t['sec_routes'] }}</td></tr>
            @if ($routes)<tr><td class="k">{{ $t['routes'] }}</td><td class="full">{{ $routes }}</td></tr>@endif
            @if ($offer->accommodation)<tr><td class="k">{{ $t['accommodation'] }}</td><td class="full">{{ $offer->accommodation }}</td></tr>@endif
        @endif

        {{-- Kontakt --}}
        <tr class="sec"><td colspan="2">{{ $t['sec_employer'] }}</td></tr>
        <tr><td class="k">{{ $t['contact_onsite'] }}</td><td class="full">{{ $onsite ?: '—' }}</td></tr>
        <tr><td class="k">{{ $t['contact_office'] }}</td><td class="full">{{ $recruiter ?: '—' }}</td></tr>

        {{-- Dodatkowe informacje --}}
        @if ($offer->public_description)
            <tr class="sec"><td colspan="2">{{ $t['sec_extra'] }}</td></tr>
            <tr><td class="full" colspan="2">{{ $offer->public_description }}</td></tr>
        @endif
    </table>

    <div class="footer">
        <span>{{ $t['footer_by'] }} <b>{{ $agencyName }}</b></span>
        <span>{{ $generatedAt }}</span>
    </div>
</div>
</body>
</html>
