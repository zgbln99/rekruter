<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif; color: #0f172a; font-size: 11px; line-height: 1.5; }
        .page { padding: 38px 46px; }

        /* Nagłówek */
        .head { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 14px; }
        .head .title { font-size: 24px; font-weight: 900; letter-spacing: -0.4px; color: #0f172a; text-transform: uppercase; line-height: 1.05; }
        .head .title .accent { color: #dc2626; }
        .head .sub { font-size: 10px; color: #64748b; margin-top: 4px; letter-spacing: 1.5px; text-transform: uppercase; }
        .head .brand { text-align: right; }
        .head .brand .logo { font-size: 18px; font-weight: 800; color: #0f172a; }
        .head .brand .logo .dot { color: #dc2626; }
        .head .brand .hr { font-size: 9px; color: #94a3b8; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 2px; }
        .topline { height: 3px; background: #dc2626; border-radius: 2px; }

        /* Pasek-hero z najważniejszymi faktami */
        .hero { margin-top: 18px; border: 1px solid #e8edf3; border-radius: 14px; overflow: hidden; }
        .hero-top { background: #0f172a; padding: 16px 20px; color: #fff; }
        .hero-top .forwho { font-size: 10px; color: #fca5a5; letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 6px; font-weight: 700; }
        .hero-top .forwho b { color: #fff; }
        .hero-top .pos { font-size: 19px; font-weight: 800; letter-spacing: -0.2px; }
        .hero-top .co { font-size: 11px; color: #cbd5e1; margin-top: 3px; }
        .hero-top .co b { color: #fff; }
        .hero-facts { display: table; width: 100%; table-layout: fixed; background: #fff; }
        .hero-facts .f { display: table-cell; padding: 12px 18px; border-right: 1px solid #f1f5f9; vertical-align: top; }
        .hero-facts .f:last-child { border-right: 0; }
        .hero-facts .f .lbl { font-size: 8.5px; letter-spacing: 0.8px; text-transform: uppercase; color: #94a3b8; font-weight: 700; }
        .hero-facts .f .val { font-size: 13px; font-weight: 800; color: #0f172a; margin-top: 3px; }
        .hero-facts .f .val.salary { color: #b91c1c; }

        /* Sekcje */
        .section { margin-top: 20px; }
        .s-head { font-size: 11px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: #dc2626; margin-bottom: 9px; display: flex; align-items: center; gap: 10px; }
        .s-head::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

        /* Siatka parametrów (2 kolumny) */
        .grid { display: table; width: 100%; table-layout: fixed; border-collapse: separate; }
        .grid .col { display: table-cell; width: 50%; vertical-align: top; }
        .grid .col:first-child { padding-right: 7px; }
        .grid .col:last-child { padding-left: 7px; }
        .item { border: 1px solid #e8edf3; border-radius: 10px; padding: 9px 13px; margin-bottom: 10px; background: #fff; }
        .item .k { font-size: 8.5px; letter-spacing: 0.7px; text-transform: uppercase; color: #94a3b8; font-weight: 700; }
        .item .v { font-size: 11.5px; color: #0f172a; margin-top: 2px; white-space: pre-line; }
        .item .v b { font-weight: 800; }

        /* Bloki tekstowe / kontaktowe */
        .panel { border: 1px solid #e8edf3; border-radius: 12px; overflow: hidden; margin-bottom: 10px; }
        .panel .ph { background: #f8fafc; padding: 8px 14px; font-size: 8.5px; letter-spacing: 0.7px; text-transform: uppercase; color: #475569; font-weight: 700; border-bottom: 1px solid #eef2f7; }
        .panel .pb { padding: 11px 14px; color: #334155; white-space: pre-line; }
        .panel .pb b { color: #0f172a; font-weight: 800; }

        .two { display: table; width: 100%; table-layout: fixed; }
        .two .c { display: table-cell; width: 50%; vertical-align: top; }
        .two .c:first-child { padding-right: 7px; }
        .two .c:last-child { padding-left: 7px; }

        .footer { margin-top: 22px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 9.5px; display: flex; justify-content: space-between; }
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

    // Parametry pracy → wyświetlane w równej siatce 2-kolumnowej.
    $params = [];
    if ($offer->work_system) $params[] = ['System pracy', $offer->work_system];
    if ($vehicle) $params[] = ['Typ auta', $vehicle];
    if ($offer->cargo) $params[] = ['Przewożony towar', $offer->cargo];
    if ($offer->contract_type) $params[] = ['Rodzaj umowy', $offer->contract_type];
    if ($offer->points_per_day) $params[] = ['Punktów dziennie', $offer->points_per_day];
    if ($offer->daily_km) $params[] = ['Średni przebieg', $offer->daily_km];
    if ($offer->loading_info) $params[] = ['Załadunek / rozładunek', $offer->loading_info];
    if ($offer->required_language) $params[] = ['Wymagany język', $offer->required_language];
    $left = []; $right = [];
    foreach ($params as $i => $p) { if ($i % 2 === 0) { $left[] = $p; } else { $right[] = $p; } }
@endphp

<div class="page">
    <div class="head">
        <div>
            <div class="title">Skierowanie do <span class="accent">pracy</span></div>
            <div class="sub">Informacje dla kierowcy</div>
        </div>
        <div class="brand">
            <div class="logo">{{ $agencyName }} <span class="dot">●</span></div>
            <div class="hr">Rekrutacja kierowców</div>
        </div>
    </div>
    <div class="topline"></div>

    {{-- HERO: stanowisko + firma + 3 kluczowe fakty --}}
    <div class="hero">
        <div class="hero-top">
            @if ($candidateName)<div class="forwho">Kierowca: <b>{{ $candidateName }}</b></div>@endif
            <div class="pos">{{ $offer->title }}</div>
            <div class="co">Pracodawca: <b>{{ $company?->name ?? '—' }}</b>@if ($region) · {{ $region }} @endif</div>
        </div>
        <div class="hero-facts">
            @if ($salary)
                <div class="f"><div class="lbl">Wynagrodzenie</div><div class="val salary">{{ $salary }}</div></div>
            @endif
            @if ($offer->work_system)
                <div class="f"><div class="lbl">System pracy</div><div class="val">{{ $offer->work_system }}</div></div>
            @endif
            @if ($arrival)
                <div class="f"><div class="lbl">Data przyjazdu</div><div class="val">{{ $arrival }}</div></div>
            @endif
        </div>
    </div>

    {{-- WARUNKI PRACY: równa siatka --}}
    @if (count($params))
        <div class="section">
            <div class="s-head">Warunki pracy</div>
            <div class="grid">
                <div class="col">
                    @foreach ($left as $p)
                        <div class="item"><div class="k">{{ $p[0] }}</div><div class="v">{{ $p[1] }}</div></div>
                    @endforeach
                </div>
                <div class="col">
                    @foreach ($right as $p)
                        <div class="item"><div class="k">{{ $p[0] }}</div><div class="v">{{ $p[1] }}</div></div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- TRASY / ZAKWATEROWANIE: dłuższe opisy w panelach obok siebie --}}
    @if ($routes || $offer->accommodation)
        <div class="section">
            <div class="s-head">Trasy i zakwaterowanie</div>
            <div class="two">
                <div class="c">
                    @if ($routes)
                        <div class="panel"><div class="ph">Trasy</div><div class="pb">{{ $routes }}</div></div>
                    @endif
                </div>
                <div class="c">
                    @if ($offer->accommodation)
                        <div class="panel"><div class="ph">Zakwaterowanie</div><div class="pb">{{ $offer->accommodation }}</div></div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- PRACODAWCA I KONTAKTY --}}
    <div class="section">
        <div class="s-head">Pracodawca i kontakt</div>
        @if ($company?->description)
            <div class="panel"><div class="ph">O firmie {{ $company?->name }}</div><div class="pb">{{ $company->description }}</div></div>
        @endif
        <div class="two">
            <div class="c">
                <div class="panel">
                    <div class="ph">Kontakt na miejscu</div>
                    <div class="pb">{{ $onsite ?: '—' }}</div>
                </div>
            </div>
            <div class="c">
                <div class="panel">
                    <div class="ph">Kontakt w Polsce (rekrutacja)</div>
                    <div class="pb"><b>{{ $recruiterName }}</b>@if ($recruiterPhone) · {{ $recruiterPhone }} @endif
@if ($recruiterEmail){{ $recruiterEmail }} @endif</div>
                </div>
            </div>
        </div>
    </div>

    {{-- DODATKOWE INFORMACJE --}}
    @if ($offer->public_description)
        <div class="section">
            <div class="s-head">Dodatkowe informacje</div>
            <div class="panel"><div class="pb">{{ $offer->public_description }}</div></div>
        </div>
    @endif

    <div class="footer">
        <span>Dokument przygotowany przez <b>{{ $agencyName }}</b></span>
        <span>{{ $generatedAt }}</span>
    </div>
</div>
</body>
</html>
