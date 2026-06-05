<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    @php $hasBg = ! empty($backgroundUri ?? null); @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif;
            width: {{ $width }}px; height: {{ $height }}px; overflow: hidden;
            background: #ffffff; color: #0f172a; position: relative;
        }

        /* Etap A: tło wygenerowane przez AI (bez tekstu) */
        .bg {
            position: absolute; inset: 0;
            background-image: url('{{ $backgroundUri ?? '' }}');
            background-size: cover; background-position: center;
        }
        /* Scrim — utrzymuje czytelność tekstu (jasno po lewej/górze i na dole) */
        .scrim {
            position: absolute; inset: 0;
            background:
                linear-gradient(101deg, rgba(255,255,255,.97) 0%, rgba(255,255,255,.93) 40%, rgba(255,255,255,.58) 62%, rgba(255,255,255,.06) 100%),
                linear-gradient(to top, rgba(255,255,255,.96) 0%, rgba(255,255,255,.62) 18%, rgba(255,255,255,0) 40%),
                linear-gradient(to bottom, rgba(255,255,255,.85) 0%, rgba(255,255,255,0) 24%);
        }

        .accentbar { position: absolute; top: 0; left: 0; right: 0; height: 14px; background: #dc2626; z-index: 5; }
        .truck { position: absolute; right: -50px; bottom: 180px; width: 720px; opacity: .05; }

        .wrap { position: relative; z-index: 4; width: 100%; height: 100%; display: flex; flex-direction: column; padding: 76px 64px 64px; }

        .brandbar { display: flex; justify-content: space-between; align-items: center; }
        .brand { font-size: 30px; font-weight: 800; color: #0f172a; }
        .brand .dot { color: #dc2626; }
        .kicker { font-size: 22px; font-weight: 900; letter-spacing: 4px; text-transform: uppercase; color: #fff; background: #dc2626; padding: 9px 20px; border-radius: 8px; }

        .hero { margin-top: 54px; }
        .pre { font-size: 30px; font-weight: 800; color: #dc2626; letter-spacing: 1px; text-transform: uppercase; }
        .title { font-size: 94px; font-weight: 900; line-height: .96; letter-spacing: -2px; color: #0f172a; margin-top: 6px; text-shadow: 0 2px 18px rgba(255,255,255,.7); }
        .underline { width: 120px; height: 8px; background: #dc2626; border-radius: 4px; margin-top: 20px; }
        .loc { margin-top: 26px; font-size: 34px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 12px; }
        .loc .pin { width: 16px; height: 16px; border-radius: 50%; background: #dc2626; box-shadow: 0 0 0 6px rgba(220,38,38,.15); }

        .tags { margin-top: 26px; display: flex; flex-wrap: wrap; gap: 12px; }
        .tag { font-size: 26px; font-weight: 800; padding: 11px 22px; border-radius: 10px; background: #fff; border: 2px solid #e2e8f0; color: #0f172a; }
        .tag.red { background: #dc2626; border-color: #dc2626; color: #fff; }

        .spacer { flex: 1; }

        .salary { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; background: #dc2626; color: #fff; border-radius: 22px; padding: 30px 36px; box-shadow: 0 24px 60px -22px rgba(220,38,38,.55); }
        .salary .l { font-size: 26px; font-weight: 800; text-transform: uppercase; letter-spacing: 3px; opacity: .9; }
        .salary .v { font-size: 78px; font-weight: 900; line-height: .95; }
        .salary .u { font-size: 26px; font-weight: 700; opacity: .9; }

        .benefits { margin-top: 26px; display: flex; flex-direction: column; gap: 13px; }
        .benefit { display: flex; align-items: center; gap: 16px; font-size: 30px; font-weight: 600; color: #1e293b; }
        .benefit .chk { width: 38px; height: 38px; border-radius: 10px; background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 900; }

        .cta { margin-top: 32px; display: flex; align-items: center; justify-content: space-between; border-top: 2px solid rgba(241,245,249,.9); padding-top: 26px; }
        .cta .label { font-size: 22px; letter-spacing: 2px; text-transform: uppercase; color: #94a3b8; }
        .cta .val { font-size: 42px; font-weight: 900; color: #0f172a; }
        .cta .apply { font-size: 30px; font-weight: 900; color: #fff; background: #dc2626; padding: 16px 32px; border-radius: 12px; }
    </style>
</head>
<body>
@php
    $loc = $offer->region_base ?: $offer->country;
    $cats = $offer->required_categories ?? [];
    $benefits = collect([
        $offer->contract_type ?: null,
        $offer->accommodation ? 'Zakwaterowanie zapewnione' : null,
        $offer->work_system ? 'System pracy: '.$offer->work_system : null,
        $offer->daily_km ? 'Przebieg: '.$offer->daily_km : null,
        $offer->loading_info ? 'Załadunek: '.$offer->loading_info : null,
    ])->filter()->take(4)->values();
@endphp

@if ($hasBg)
    <div class="bg"></div>
    <div class="scrim"></div>
@else
    <svg class="truck" viewBox="0 0 640 256" fill="#dc2626" xmlns="http://www.w3.org/2000/svg">
        <path d="M392 40H40C26 40 16 50 16 64v112c0 8 6 14 14 14h18c6 28 31 48 60 48s54-20 60-48h140c6 28 31 48 60 48s54-20 60-48h26c14 0 24-10 24-24v-58c0-10-4-19-11-26l-58-58c-7-7-16-11-26-11h-49V64c0-14-10-24-24-24zM108 224c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm260 0c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm56-128h-32V72h25l7 7v17z"/>
    </svg>
@endif

<div class="accentbar"></div>

<div class="wrap">
    <div class="brandbar">
        <div class="brand"><span class="dot">●</span> {{ $agencyName }}</div>
        <div class="kicker">Oferta pracy</div>
    </div>

    <div class="hero">
        <div class="pre">Praca dla kierowcy{{ $offer->country ? ' · '.$offer->country : '' }}</div>
        <div class="title">{{ $offer->title }}</div>
        <div class="underline"></div>
        @if ($loc)<div class="loc"><span class="pin"></span> {{ $loc }}</div>@endif
        <div class="tags">
            @foreach ($cats as $cat)<span class="tag red">{{ $cat }}</span>@endforeach
            @if ($offer->has_code_95)<span class="tag">Kod 95</span>@endif
            @if ($offer->has_adr)<span class="tag">ADR</span>@endif
        </div>
    </div>

    <div class="spacer"></div>

    @if ($offer->salary_amount)
        <div class="salary">
            <div><div class="l">Wynagrodzenie</div><div class="v">{{ $offer->salary_amount }}</div></div>
            <div class="u">{{ $offer->currency }}</div>
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
            <div class="val">{{ $agencyPhone ?: $agencyEmail }}</div>
        </div>
        <div class="apply">APLIKUJ</div>
    </div>
</div>
</body>
</html>
