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
            background: #0b1220; color: #fff; position: relative;
        }
        /* Tło: gradient + dekoracyjne kształty */
        .bg { position: absolute; inset: 0; background: radial-gradient(1200px 700px at 80% -10%, #14302a 0%, #0b1220 55%), linear-gradient(160deg, #0b1220 0%, #0e1627 100%); }
        .blob { position: absolute; border-radius: 50%; filter: blur(2px); opacity: .9; }
        .blob1 { width: 520px; height: 520px; right: -160px; top: -160px; background: radial-gradient(circle at 30% 30%, rgba(16,185,129,.35), rgba(16,185,129,0) 70%); }
        .truck { position: absolute; right: -40px; bottom: 150px; width: 760px; opacity: .07; }

        .wrap { position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; padding: 70px 64px; }

        .brandbar { display: flex; justify-content: space-between; align-items: center; }
        .brand { font-size: 30px; font-weight: 800; letter-spacing: .3px; }
        .brand .dot { color: #10b981; }
        .kicker { font-size: 22px; font-weight: 800; letter-spacing: 5px; text-transform: uppercase; color: #10b981; background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.4); padding: 8px 18px; border-radius: 999px; }

        .hero { margin-top: 56px; }
        .hero .pre { font-size: 30px; font-weight: 700; color: #93c5b6; letter-spacing: 1px; }
        .hero .title { font-size: 92px; font-weight: 900; line-height: .98; letter-spacing: -2px; margin-top: 6px; }
        .hero .loc { margin-top: 22px; font-size: 34px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 12px; }
        .hero .loc .pin { width: 16px; height: 16px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 6px rgba(16,185,129,.25); }

        .tags { margin-top: 26px; display: flex; flex-wrap: wrap; gap: 12px; }
        .tag { font-size: 26px; font-weight: 800; padding: 11px 22px; border-radius: 12px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14); }
        .tag.mint { background: #10b981; border-color: #10b981; color: #06281e; }

        .spacer { flex: 1; }

        .salary { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; background: linear-gradient(135deg, #10b981, #059669); color: #06281e; border-radius: 24px; padding: 30px 34px; box-shadow: 0 20px 60px -20px rgba(16,185,129,.6); }
        .salary .l { font-size: 26px; font-weight: 800; text-transform: uppercase; letter-spacing: 3px; opacity: .85; }
        .salary .v { font-size: 76px; font-weight: 900; line-height: .95; }
        .salary .u { font-size: 26px; font-weight: 700; opacity: .85; }

        .benefits { margin-top: 28px; display: flex; flex-direction: column; gap: 14px; }
        .benefit { display: flex; align-items: center; gap: 16px; font-size: 30px; font-weight: 600; color: #e8edf5; }
        .benefit .chk { width: 38px; height: 38px; border-radius: 10px; background: rgba(16,185,129,.18); border: 1px solid rgba(16,185,129,.5); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 900; }

        .cta { margin-top: 34px; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255,255,255,.12); padding-top: 26px; }
        .cta .label { font-size: 22px; letter-spacing: 2px; text-transform: uppercase; color: #94a3b8; }
        .cta .val { font-size: 40px; font-weight: 900; color: #fff; }
        .cta .val .mint { color: #10b981; }
        .cta .apply { font-size: 30px; font-weight: 900; color: #06281e; background: #10b981; padding: 16px 30px; border-radius: 14px; }
    </style>
</head>
<body>
@php
    $loc = $offer->region_base ?: $offer->country;
    $salary = $offer->salary_amount;
    $unit = $offer->currency ?: '';
    $cats = $offer->required_categories ?? [];
    $benefits = collect([
        $offer->contract_type ? $offer->contract_type : null,
        $offer->accommodation ? 'Zakwaterowanie zapewnione' : null,
        $offer->work_system ? 'System pracy: '.$offer->work_system : null,
        $offer->daily_km ? 'Przebieg: '.$offer->daily_km : null,
        $offer->loading_info ? 'Załadunek: '.$offer->loading_info : null,
    ])->filter()->take(4)->values();
@endphp
<div class="bg"></div>
<div class="blob blob1"></div>
<svg class="truck" viewBox="0 0 640 256" fill="#10b981" xmlns="http://www.w3.org/2000/svg">
    <path d="M392 40H40C26 40 16 50 16 64v112c0 8 6 14 14 14h18c6 28 31 48 60 48s54-20 60-48h140c6 28 31 48 60 48s54-20 60-48h26c14 0 24-10 24-24v-58c0-10-4-19-11-26l-58-58c-7-7-16-11-26-11h-49V64c0-14-10-24-24-24zM108 224c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm260 0c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm56-128h-32V72h25l7 7v17z"/>
</svg>

<div class="wrap">
    <div class="brandbar">
        <div class="brand"><span class="dot">●</span> {{ $agencyName }}</div>
        <div class="kicker">Oferta pracy</div>
    </div>

    <div class="hero">
        <div class="pre">Praca dla kierowcy{{ $offer->country ? ' · '.$offer->country : '' }}</div>
        <div class="title">{{ $offer->title }}</div>
        @if ($loc)<div class="loc"><span class="pin"></span> {{ $loc }}</div>@endif
        <div class="tags">
            @foreach ($cats as $cat)<span class="tag mint">{{ $cat }}</span>@endforeach
            @if ($offer->has_code_95)<span class="tag">Kod 95</span>@endif
            @if ($offer->has_adr)<span class="tag">ADR</span>@endif
        </div>
    </div>

    <div class="spacer"></div>

    @if ($salary)
        <div class="salary">
            <div><div class="l">Wynagrodzenie</div><div class="v">{{ $salary }}</div></div>
            <div class="u">{{ $unit }}</div>
        </div>
    @endif

    @if ($benefits->count())
        <div class="benefits">
            @foreach ($benefits as $b)<div class="benefit"><span class="chk">✓</span> {{ $b }}</div>@endforeach
        </div>
    @endif

    <div class="cta">
        <div>
            <div class="label">Zadzwoń / napisz</div>
            <div class="val"><span class="mint">{{ $agencyPhone ?: $agencyEmail }}</span></div>
        </div>
        <div class="apply">APLIKUJ</div>
    </div>
</div>
</body>
</html>
