<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif; color: #0f172a; font-size: 11.5px; line-height: 1.55; }
        .page { padding: 44px 50px; }

        .head { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 16px; }
        .head .title { font-size: 26px; font-weight: 900; letter-spacing: -0.4px; color: #0f172a; text-transform: uppercase; line-height: 1.05; }
        .head .title .accent { color: #dc2626; }
        .head .sub { font-size: 10.5px; color: #64748b; margin-top: 4px; letter-spacing: 1.5px; text-transform: uppercase; }
        .head .brand { text-align: right; }
        .head .brand .logo { font-size: 20px; font-weight: 800; color: #0f172a; }
        .head .brand .logo .dot { color: #dc2626; }
        .head .brand .hr { font-size: 10px; color: #94a3b8; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 2px; }
        .topline { height: 3px; background: #dc2626; border-radius: 2px; }

        .section { margin-top: 24px; }
        .s-head { font-size: 12px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: #dc2626; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .s-head::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .card { border: 1px solid #e8edf3; border-radius: 12px; overflow: hidden; }
        .row { display: flex; border-bottom: 1px solid #f1f5f9; }
        .row:last-child { border-bottom: 0; }
        .row .k { width: 210px; padding: 11px 16px; background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px; }
        .row .v { flex: 1; padding: 11px 16px; color: #0f172a; }
        .row .v b { font-weight: 800; }
        .muted { color: #64748b; font-weight: 400; }
        .pos { font-size: 17px; font-weight: 800; color: #0f172a; }
        .salary-v { font-size: 17px; font-weight: 900; color: #b91c1c; }
        .desc { white-space: pre-line; color: #334155; padding: 12px 16px; background: #fff; }

        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 10px; display: flex; justify-content: space-between; }
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
    $arrival = $offer->arrival_info ?: ($offer->start_date ? $offer->start_date->format('d.m.Y') : null);
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

    <div class="section">
        <div class="s-head">Pracodawca</div>
        <div class="card">
            <div class="row"><div class="k">Nazwa firmy</div><div class="v"><b>{{ $company?->name ?? '—' }}</b>@if ($company?->website) <span class="muted">· {{ $company->website }}</span>@endif</div></div>
            @if ($region)<div class="row"><div class="k">Region</div><div class="v">{{ $region }}</div></div>@endif
            @if ($company?->description)<div class="row"><div class="k">Opis firmy</div><div class="v">{{ $company->description }}</div></div>@endif
            @if ($arrival)<div class="row"><div class="k">Data przyjazdu</div><div class="v"><b>{{ $arrival }}</b></div></div>@endif
            @if ($onsite)<div class="row"><div class="k">Kontakt na miejscu</div><div class="v" style="white-space:pre-line;">{{ $onsite }}</div></div>@endif
            <div class="row"><div class="k">Kontakt w Polsce</div><div class="v">{{ $recruiterName }}@if ($recruiterPhone) · {{ $recruiterPhone }}@endif @if ($recruiterEmail)<br><span class="muted">{{ $recruiterEmail }}</span>@endif</div></div>
        </div>
    </div>

    <div class="section">
        <div class="s-head">Stanowisko i warunki</div>
        <div class="card">
            <div class="row"><div class="k">Stanowisko</div><div class="v"><span class="pos">{{ $offer->title }}</span></div></div>
            @if ($offer->work_system)<div class="row"><div class="k">System pracy</div><div class="v">{{ $offer->work_system }}</div></div>@endif
            @if ($vehicle)<div class="row"><div class="k">Typ auta</div><div class="v">{{ $vehicle }}</div></div>@endif
            @if ($routes)<div class="row"><div class="k">Trasy</div><div class="v" style="white-space:pre-line;">{{ $routes }}</div></div>@endif
            @if ($offer->cargo)<div class="row"><div class="k">Przewożony towar</div><div class="v">{{ $offer->cargo }}</div></div>@endif
            @if ($offer->points_per_day)<div class="row"><div class="k">Punktów dziennie</div><div class="v">{{ $offer->points_per_day }}</div></div>@endif
            @if ($offer->loading_info)<div class="row"><div class="k">Załadunek / rozładunek</div><div class="v">{{ $offer->loading_info }}</div></div>@endif
            @if ($offer->daily_km)<div class="row"><div class="k">Średni przebieg</div><div class="v">{{ $offer->daily_km }}</div></div>@endif
            @if ($offer->accommodation)<div class="row"><div class="k">Zakwaterowanie</div><div class="v" style="white-space:pre-line;">{{ $offer->accommodation }}</div></div>@endif
            @if ($offer->contract_type)<div class="row"><div class="k">Rodzaj umowy</div><div class="v">{{ $offer->contract_type }}</div></div>@endif
            @if ($salary)<div class="row"><div class="k">Wynagrodzenie</div><div class="v"><span class="salary-v">{{ $salary }}</span></div></div>@endif
            @if ($offer->required_language)<div class="row"><div class="k">Wymagany język</div><div class="v">{{ $offer->required_language }}</div></div>@endif
        </div>
    </div>

    @if ($offer->public_description)
        <div class="section">
            <div class="s-head">Dodatkowe informacje</div>
            <div class="card"><div class="desc">{{ $offer->public_description }}</div></div>
        </div>
    @endif

    <div class="footer">
        <span>Dokument przygotowany przez <b>{{ $agencyName }}</b></span>
        <span>{{ $generatedAt }}</span>
    </div>
</div>
</body>
</html>
