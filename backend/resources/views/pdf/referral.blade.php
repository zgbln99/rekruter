<!DOCTYPE html>
<html lang="{{ $lang ?? 'pl' }}">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif; color: #0f172a; font-size: 9.5px; line-height: 1.3; }
        .page { padding: 22px 26px 36px; }

        /* Nagłówek */
        .head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 9px; }
        .head .agency { font-size: 15px; font-weight: 800; color: #0f172a; }
        .head .agency .dot { color: #dc2626; }
        .head .agency small { display: block; font-size: 8px; letter-spacing: 1.3px; text-transform: uppercase; color: #94a3b8; margin-top: 2px; font-weight: 700; }
        .head .meta { text-align: right; font-size: 8.5px; color: #64748b; line-height: 1.6; }
        .head .meta b { color: #0f172a; }

        .title-bar { background: #dc2626; color: #fff; padding: 8px 14px; border-radius: 7px 7px 0 0; }
        .title-bar .t { font-size: 16px; font-weight: 900; letter-spacing: 0.4px; text-transform: uppercase; }
        .title-bar .for { font-size: 10.5px; margin-top: 2px; color: #fde2e2; }
        .title-bar .for b { color: #fff; }

        /* Tabela */
        table.doc { width: 100%; border-collapse: collapse; }
        table.doc td { border: 1px solid #cbd5e1; padding: 4px 10px; vertical-align: top; }
        tr.sec td { background: #0f172a; color: #fff; font-weight: 800; font-size: 9px; letter-spacing: 1px; text-transform: uppercase; border-color: #0f172a; padding: 4px 10px; }
        td.k { width: 34%; background: #f6f8fb; font-size: 8.5px; letter-spacing: 0.3px; text-transform: uppercase; color: #475569; font-weight: 700; }
        td.v { font-size: 10px; color: #0f172a; font-weight: 600; white-space: pre-line; }
        td.v .muted { color: #94a3b8; font-weight: 500; }
        td.v.salary { color: #b91c1c; font-weight: 800; font-size: 12.5px; }
        td.full { white-space: pre-line; font-weight: 500; color: #334155; }
        td.html { font-weight: 500; color: #334155; }
        td.html ul { list-style: disc; padding-left: 16px; margin: 0; }
        td.html ol { list-style: decimal; padding-left: 16px; margin: 0; }
        td.html li { margin: 1px 0; }
        td.html p { margin: 0 0 3px; }

        /* Czyste łamanie na strony: wiersze nie pękają, nagłówek sekcji trzyma
           się treści, pasek tytułowy nie rozjeżdża się między stronami. */
        tr { break-inside: avoid; page-break-inside: avoid; }
        tr.sec { break-after: avoid; page-break-after: avoid; }
        .title-bar { break-inside: avoid; page-break-inside: avoid; break-after: avoid; page-break-after: avoid; }
        table.doc { page-break-inside: auto; }

        /* Stopka zawsze na dole strony (drukowana na każdej stronie). */
        .footer { position: fixed; left: 0; right: 0; bottom: 0; padding: 6px 26px;
            background: #fff; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 8.5px;
            display: flex; justify-content: space-between; }
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
            <tr><td class="html" colspan="2">{!! strip_tags($offer->public_description, '<b><strong><i><em><u><ul><ol><li><p><br><span>') !!}</td></tr>
        @endif
    </table>

    <div class="footer">
        <span>{{ $t['footer_by'] }} <b>{{ $agencyName }}</b></span>
        <span>{{ $generatedAt }}</span>
    </div>
</div>
</body>
</html>
