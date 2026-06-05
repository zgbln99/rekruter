<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif;
            width: {{ $width }}px; height: {{ $height }}px; overflow: hidden;
            background: #f8fafc; color: #0f172a;
        }
        .wrap { width: 100%; height: 100%; display: flex; flex-direction: column; }

        .top { background: #0f172a; color: #fff; padding: 48px 56px 40px; position: relative; }
        .top::after { content: ''; position: absolute; left: 56px; bottom: 26px; width: 90px; height: 6px; background: #10b981; border-radius: 4px; }
        .agency { font-size: 26px; font-weight: 800; letter-spacing: .3px; }
        .agency .dot { color: #10b981; }
        .kicker { margin-top: 20px; font-size: 24px; font-weight: 700; letter-spacing: 6px; text-transform: uppercase; color: #10b981; }
        .headline { margin-top: 8px; font-size: 66px; font-weight: 900; line-height: 1.02; letter-spacing: -1px; }

        .body { flex: 1; padding: 44px 56px; display: flex; flex-direction: column; gap: 26px; }
        .meta { display: flex; flex-wrap: wrap; gap: 14px; }
        .pill { background: #fff; border: 2px solid #e2e8f0; border-radius: 999px; padding: 12px 22px; font-size: 26px; font-weight: 700; color: #0f172a; }
        .pill.mint { background: #10b981; border-color: #10b981; color: #fff; }

        .row { display: flex; align-items: baseline; gap: 16px; }
        .row .lab { font-size: 26px; color: #64748b; font-weight: 600; width: 230px; }
        .row .val { font-size: 30px; font-weight: 700; color: #0f172a; flex: 1; }

        .salary { background: #ecfdf5; border: 2px solid #6ee7b7; border-radius: 18px; padding: 26px 30px; }
        .salary .l { font-size: 24px; color: #059669; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; }
        .salary .v { font-size: 54px; font-weight: 900; color: #047857; line-height: 1.05; }

        .desc { font-size: 27px; line-height: 1.45; color: #334155; }

        .bottom { background: #0f172a; color: #fff; padding: 36px 56px; display: flex; justify-content: space-between; align-items: center; }
        .bottom .c-lab { font-size: 22px; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; }
        .bottom .c-val { font-size: 34px; font-weight: 800; }
        .bottom .c-val.mint { color: #10b981; }
        .bottom .right { text-align: right; }
    </style>
</head>
<body>
@php
    $loc = $offer->region_base ?: $offer->country;
    $salary = trim(($offer->salary_amount ?? '').' '.($offer->currency ?? ''));
    $cats = $offer->required_categories ?? [];
    $descSrc = $offer->public_description ?: $offer->description;
    $desc = $descSrc ? \Illuminate\Support\Str::limit(strip_tags($descSrc), 220) : null;
@endphp
<div class="wrap">
    <div class="top">
        <div class="agency"><span class="dot">●</span> {{ $agencyName }}</div>
        <div class="kicker">Oferta pracy{{ $loc ? ' · '.$loc : '' }}</div>
        <div class="headline">{{ $offer->title }}</div>
    </div>

    <div class="body">
        <div class="meta">
            @foreach ($cats as $cat)<span class="pill mint">{{ $cat }}</span>@endforeach
            @if ($offer->has_adr)<span class="pill">ADR</span>@endif
            @if ($offer->has_code_95)<span class="pill">Kod 95</span>@endif
        </div>

        @if ($offer->work_system)<div class="row"><div class="lab">System pracy</div><div class="val">{{ $offer->work_system }}</div></div>@endif
        @if ($vehicle = ($offer->vehicle_type ?: $offer->trailer_type))<div class="row"><div class="lab">Pojazd</div><div class="val">{{ $vehicle }}</div></div>@endif
        @if ($offer->contract_type)<div class="row"><div class="lab">Umowa</div><div class="val">{{ $offer->contract_type }}</div></div>@endif

        @if ($salary)
            <div class="salary">
                <div class="l">Wynagrodzenie</div>
                <div class="v">{{ $salary }}</div>
            </div>
        @endif

        @if ($desc)<div class="desc">{{ $desc }}</div>@endif
    </div>

    <div class="bottom">
        <div>
            <div class="c-lab">Kontakt</div>
            <div class="c-val mint">{{ $agencyPhone ?: $agencyEmail }}</div>
        </div>
        <div class="right">
            <div class="c-lab">Aplikuj</div>
            <div class="c-val">{{ $agencyWebsite ?: $agencyName }}</div>
        </div>
    </div>
</div>
</body>
</html>
