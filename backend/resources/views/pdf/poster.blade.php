<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    @php $hasBg = ! empty($backgroundImage ?? null); @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        :root {
            --navy: #071A33;
            --label: #3A4656;
            --red: #C91414;
        }

        body {
            font-family: 'Helvetica Neue', Arial, 'DejaVu Sans', sans-serif;
            width: {{ $width }}px; height: {{ $height }}px; overflow: hidden;
            background: #ffffff; color: var(--navy); position: relative;
            font-weight: 700;
        }

        /* ---- Tło: AI (reużywane) lub zaprojektowany fallback ---- */
        .bg {
            position: absolute; inset: 0;
            background-image: url('{{ $backgroundImage ?? '' }}');
            background-size: cover; background-position: right center;
        }
        .scrim {
            position: absolute; inset: 0;
            background:
                linear-gradient(96deg, rgba(255,255,255,.98) 0%, rgba(255,255,255,.95) 44%, rgba(255,255,255,.56) 66%, rgba(255,255,255,.12) 100%),
                linear-gradient(to top, rgba(255,255,255,.96) 0%, rgba(255,255,255,.72) 14%, rgba(255,255,255,0) 32%);
        }
        .fallback-bg {
            position: absolute; inset: 0;
            background: linear-gradient(155deg, #ffffff 0%, #f4f7fb 52%, #e8eef6 100%);
        }
        .accent-shape {
            position: absolute; top: -160px; right: -140px; width: 480px; height: 480px; border-radius: 50%;
            background: radial-gradient(circle at 32% 32%, rgba(201,20,20,.15), rgba(201,20,20,0) 70%);
        }
        .truck-watermark {
            position: absolute; right: -30px; bottom: 70px; width: 640px; opacity: .06; color: var(--navy);
        }

        /* ---- Treść ---- */
        .poster {
            position: relative; z-index: 4;
            width: 100%; height: 100%;
            display: flex; flex-direction: column;
            padding: 76px 72px;
        }

        .topmark { width: 76px; height: 9px; background: var(--red); border-radius: 5px; margin-bottom: 22px; }

        .headline { font-weight: 800; line-height: .92; letter-spacing: -2px; color: var(--navy); text-transform: uppercase; }
        .headline div { font-size: 84px; }
        .headline .accent { color: var(--red); }

        /* Nagłówek + dane razem u góry, wynagrodzenie + CTA przyklejone do dołu */
        .details { display: flex; flex-direction: column; max-width: 720px; margin-top: 50px; }
        .details > div { padding-top: 24px; margin-top: 24px; border-top: 1px solid rgba(7,26,51,.12); }
        .details > div:first-child { padding-top: 0; margin-top: 0; border-top: none; }
        .label { font-size: 26px; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; color: var(--label); margin-bottom: 7px; }
        .value { font-size: 40px; font-weight: 800; color: var(--navy); line-height: 1.06; }
        .value-main { font-weight: 900; line-height: 1.02; overflow-wrap: break-word; }
        .subvalue { font-size: 30px; font-weight: 700; color: var(--label); margin-top: 6px; line-height: 1.12; }

        .field-row { display: flex; gap: 64px; }
        .field.compact .value { font-size: 36px; }

        .bottom { margin-top: auto; }
        .salary { border-left: 7px solid var(--red); padding-left: 22px; }
        .salary-label { font-size: 26px; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; color: var(--label); margin-bottom: 4px; }
        .salary-value { font-size: 66px; font-weight: 900; color: var(--red); line-height: 1; letter-spacing: -1px; }
        .salary-value .suffix { font-size: 29px; font-weight: 700; color: var(--label); letter-spacing: 0; margin-left: 12px; }

        .apply-button {
            margin-top: 30px; height: 82px; width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 16px;
            background: var(--red); color: #fff;
            font-size: 41px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase;
            border-radius: 16px; box-shadow: 0 22px 50px -24px rgba(201,20,20,.6);
        }
        .apply-button .arrow { font-size: 38px; line-height: 1; }

        .agency { margin-top: 24px; font-size: 28px; font-weight: 700; color: var(--label); display: flex; align-items: center; gap: 12px; }
        .agency .dot { width: 14px; height: 14px; border-radius: 50%; background: var(--red); }

        /* ---- Wariant reels (1080x1920) ---- */
        body.reels .poster { padding: 104px 80px; }
        body.reels .headline div { font-size: 108px; }
        body.reels .details { margin-top: 66px; }
        body.reels .details > div { padding-top: 30px; margin-top: 30px; }
        body.reels .label, body.reels .salary-label { font-size: 32px; }
        body.reels .value { font-size: 50px; }
        body.reels .field.compact .value { font-size: 44px; }
        body.reels .subvalue { font-size: 36px; }
        body.reels .salary-value { font-size: 82px; }
        body.reels .salary-value .suffix { font-size: 36px; }
        body.reels .apply-button { height: 96px; font-size: 48px; }
        body.reels .agency { font-size: 34px; }
    </style>
</head>
<body class="{{ $format }}">
    @if ($hasBg)
        <div class="bg"></div>
        <div class="scrim"></div>
    @else
        <div class="fallback-bg"></div>
        <div class="accent-shape"></div>
        <svg class="truck-watermark" viewBox="0 0 640 256" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M392 40H40C26 40 16 50 16 64v112c0 8 6 14 14 14h18c6 28 31 48 60 48s54-20 60-48h140c6 28 31 48 60 48s54-20 60-48h26c14 0 24-10 24-24v-58c0-10-4-19-11-26l-58-58c-7-7-16-11-26-11h-49V64c0-14-10-24-24-24zM108 224c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm260 0c-18 0-32-14-32-32s14-32 32-32 32 14 32 32-14 32-32 32zm56-128h-32V72h25l7 7v17z"/>
        </svg>
    @endif

    <main class="poster">
        <section>
            <div class="topmark"></div>
            <div class="headline">
                <div>Praca dla</div>
                <div class="accent">Kierowcy</div>
            </div>
        </section>

        <section class="details">
            <div class="field">
                <div class="label">Stanowisko</div>
                <div class="value value-main" style="font-size: {{ $headlineFontSize }}px">{{ $headline }}</div>
                @if ($subtitle)
                    <div class="subvalue">{{ $subtitle }}</div>
                @endif
            </div>

            @if ($locationLine1)
                <div class="field">
                    <div class="label">Lokalizacja</div>
                    <div class="value">{{ $locationLine1 }}</div>
                    @if ($locationLine2)
                        <div class="subvalue">{{ $locationLine2 }}</div>
                    @endif
                </div>
            @endif

            <div class="field-row">
                @if ($category)
                    <div class="field compact">
                        <div class="label">Kategoria</div>
                        <div class="value">{{ $category }}</div>
                    </div>
                @endif
                <div class="field compact">
                    <div class="label">System pracy</div>
                    <div class="value">{{ $workSystem }}</div>
                </div>
            </div>
        </section>

        <section class="bottom">
            @if ($salary)
                <div class="salary">
                    <div class="salary-label">Wynagrodzenie</div>
                    <div class="salary-value">{{ $salary }}<span class="suffix">{{ $salarySuffix }}</span></div>
                </div>
            @endif

            <div class="apply-button">Aplikuj teraz <span class="arrow">→</span></div>

            <div class="agency"><span class="dot"></span> {{ $agencyName }}</div>
        </section>
    </main>
</body>
</html>
