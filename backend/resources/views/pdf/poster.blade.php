<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    @php $hasBg = ! empty($backgroundImage ?? null); @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        :root { --navy: #071A33; --label: #51607A; --red: #C91414; --line: rgba(7,26,51,.10); }

        body {
            font-family: 'Helvetica Neue', Arial, 'DejaVu Sans', sans-serif;
            width: {{ $width }}px; height: {{ $height }}px; overflow: hidden;
            background: #ffffff; color: var(--navy); position: relative; font-weight: 700;
        }

        /* ---- Tło ---- */
        .bg { position: absolute; inset: 0; background-image: url('{{ $backgroundImage ?? '' }}'); background-size: cover; background-position: right center; }
        .bg-tint { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(7,26,51,.06), rgba(7,26,51,.02)); }
        .fallback-bg { position: absolute; inset: 0; background: linear-gradient(150deg, #f5f8fc 0%, #e9eef6 55%, #dde5f0 100%); }
        .accent-shape { position: absolute; top: -160px; right: -150px; width: 520px; height: 520px; border-radius: 50%; background: radial-gradient(circle at 34% 34%, rgba(201,20,20,.14), rgba(201,20,20,0) 70%); }
        .truck-watermark { position: absolute; right: -40px; bottom: 90px; width: 640px; opacity: .07; color: var(--navy); }

        /* ---- Panel (karta z treścią) ---- */
        .panel {
            position: absolute; top: 60px; left: 60px; bottom: 60px; width: 62%; z-index: 5;
            background: rgba(255,255,255,.975);
            border: 1px solid rgba(7,26,51,.06);
            border-radius: 30px;
            box-shadow: 0 50px 90px -34px rgba(7,26,51,.30);
            padding: 52px 50px;
            display: flex; flex-direction: column;
        }

        .topbar { display: flex; justify-content: space-between; align-items: center; }
        .agency { display: flex; align-items: center; gap: 11px; font-size: 25px; font-weight: 800; color: var(--navy); }
        .agency .dot { width: 14px; height: 14px; border-radius: 50%; background: var(--red); }
        .pill { font-size: 20px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--red); border: 2px solid var(--red); border-radius: 999px; padding: 7px 18px; }

        .kicker { margin-top: 38px; font-size: 25px; font-weight: 800; letter-spacing: 4px; text-transform: uppercase; color: var(--red); }
        .hero { margin-top: 6px; font-weight: 900; line-height: .98; letter-spacing: -1.5px; color: var(--navy); }
        .subtitle { margin-top: 16px; font-size: 31px; font-weight: 700; color: var(--label); line-height: 1.18; }

        .specs { margin-top: 36px; }
        .spec { display: flex; justify-content: space-between; align-items: baseline; gap: 24px; padding: 17px 0; border-top: 1px solid var(--line); }
        .spec:last-child { border-bottom: 1px solid var(--line); }
        .spec .k { font-size: 22px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--label); white-space: nowrap; }
        .spec .v { font-size: 34px; font-weight: 800; color: var(--navy); text-align: right; line-height: 1.08; }
        .spec .v small { display: block; font-size: 24px; font-weight: 600; color: var(--label); margin-top: 2px; }

        .bottom { margin-top: auto; padding-top: 32px; }
        .salary { border-left: 8px solid var(--red); padding-left: 22px; }
        .salary .l { font-size: 23px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--label); }
        .salary .v { font-size: 64px; font-weight: 900; color: var(--red); line-height: 1.04; letter-spacing: -1px; }
        .salary .v .suffix { font-size: 27px; font-weight: 700; color: var(--label); margin-left: 10px; letter-spacing: 0; }

        .cta {
            margin-top: 28px; height: 86px;
            display: flex; align-items: center; justify-content: center; gap: 16px;
            background: var(--red); color: #fff;
            font-size: 40px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;
            border-radius: 16px; box-shadow: 0 24px 50px -22px rgba(201,20,20,.6);
        }
        .cta .arrow { font-size: 38px; line-height: 1; }

        /* ---- Wariant reels (1080x1920) ---- */
        body.reels .panel { top: 90px; left: 70px; bottom: 90px; width: 66%; padding: 66px 60px; border-radius: 36px; }
        body.reels .agency { font-size: 30px; }
        body.reels .pill { font-size: 24px; }
        body.reels .kicker { margin-top: 56px; font-size: 30px; }
        body.reels .subtitle { font-size: 38px; }
        body.reels .specs { margin-top: 50px; }
        body.reels .spec { padding: 24px 0; }
        body.reels .spec .k { font-size: 27px; }
        body.reels .spec .v { font-size: 44px; }
        body.reels .spec .v small { font-size: 30px; }
        body.reels .salary .l { font-size: 28px; }
        body.reels .salary .v { font-size: 84px; }
        body.reels .salary .v .suffix { font-size: 33px; }
        body.reels .cta { height: 108px; font-size: 50px; }
    </style>
</head>
<body class="{{ $format }}">
    @if ($hasBg)
        <div class="bg"></div>
        <div class="bg-tint"></div>
    @else
        <div class="fallback-bg"></div>
        <div class="accent-shape"></div>
        <svg class="truck-watermark" viewBox="0 0 640 256" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M392 40H40C26 40 16 50 16 64v112c0 8 6 14 14 14h18c6 28 31 48 60 48s54-20 60-48h140c6 28 31 48 60 48s54-20 60-48h26c14 0 24-10 24-24v-58c0-10-4-19-11-26l-58-58c-7-7-16-11-26-11h-49V64c0-14-10-24-24-24zM108 224c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm260 0c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm56-128h-32V72h25l7 7v17z"/>
        </svg>
    @endif

    <div class="panel">
        <div class="topbar">
            <div class="agency"><span class="dot"></span> {{ $agencyName }}</div>
            <div class="pill">Oferta pracy</div>
        </div>

        <div class="kicker">Praca dla kierowcy</div>
        <div class="hero" style="font-size: {{ $heroFontSize }}px">{{ $headline }}</div>
        @if ($subtitle)
            <div class="subtitle">{{ $subtitle }}</div>
        @endif

        <div class="specs">
            @if ($locationLine1)
                <div class="spec">
                    <div class="k">Lokalizacja</div>
                    <div class="v">{{ $locationLine1 }}@if ($locationLine2)<small>{{ $locationLine2 }}</small>@endif</div>
                </div>
            @endif
            @if ($category)
                <div class="spec">
                    <div class="k">Kategoria</div>
                    <div class="v">{{ $category }}</div>
                </div>
            @endif
            <div class="spec">
                <div class="k">System pracy</div>
                <div class="v">{{ $workSystem }}</div>
            </div>
        </div>

        <div class="bottom">
            @if ($salary)
                <div class="salary">
                    <div class="l">Wynagrodzenie</div>
                    <div class="v">{{ $salary }}<span class="suffix">{{ $salarySuffix }}</span></div>
                </div>
            @endif
            <div class="cta">Aplikuj teraz <span class="arrow">→</span></div>
        </div>
    </div>
</body>
</html>
