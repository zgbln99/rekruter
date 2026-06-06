<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: 'Helvetica Neue', 'Segoe UI', Arial, sans-serif;
            color: #0f172a;
            font-size: 11.5px;
            line-height: 1.55;
        }
        .page { padding: 46px 52px 60px; }

        /* Nagłówek */
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .h-left { padding-right: 24px; }
        .name { font-size: 30px; font-weight: 700; letter-spacing: -0.6px; color: #0f172a; line-height: 1.05; }
        .role { margin-top: 6px; font-size: 11px; font-weight: 600; letter-spacing: 2px;
            text-transform: uppercase; color: #64748b; }
        .cats { margin-top: 12px; }
        .cats span {
            display: inline-block; border: 1px solid #cbd5e1; color: #334155;
            border-radius: 5px; padding: 2px 9px; font-size: 11px; font-weight: 600; margin: 0 4px 4px 0;
        }
        .photo { width: 104px; height: 124px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
        .photo-ph {
            width: 104px; height: 124px; border-radius: 8px; background: #f1f5f9; color: #94a3b8;
            text-align: center; line-height: 124px; font-size: 44px; font-weight: 700;
        }

        .rule { height: 2px; background: #0f172a; margin: 18px 0 0; }
        .rule-accent { height: 2px; width: 64px; background: #dc2626; }

        /* Pasek kontaktu */
        .contact { display: flex; flex-wrap: wrap; gap: 6px 22px; margin: 16px 0 6px; }
        .contact .item { font-size: 11px; color: #475569; }
        .contact .item b { color: #0f172a; font-weight: 600; }

        /* Sekcje */
        .section { margin-top: 24px; }
        .s-head { font-size: 11px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase;
            color: #0f172a; padding-bottom: 7px; border-bottom: 1px solid #e2e8f0; margin-bottom: 12px; }

        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 5px 0; font-size: 11.5px; vertical-align: top; }
        .grid .k { color: #64748b; width: 200px; }
        .grid .v { color: #0f172a; font-weight: 600; }
        .ok { color: #b91c1c; font-weight: 700; }
        .no { color: #94a3b8; }
        .muted { color: #64748b; font-weight: 400; }

        .tags span {
            display: inline-block; background: #f1f5f9; color: #334155; border-radius: 999px;
            padding: 3px 12px; font-size: 11px; font-weight: 600; margin: 0 5px 5px 0;
        }

        .job { padding-left: 18px; border-left: 1px solid #e2e8f0; margin-bottom: 15px; position: relative; }
        .job::before { content: ''; position: absolute; left: -4px; top: 5px; width: 7px; height: 7px;
            border-radius: 50%; background: #dc2626; }
        .job-title { font-weight: 700; color: #0f172a; font-size: 12px; }
        .job-meta { color: #64748b; font-size: 10.5px; }
        .job-desc { color: #475569; font-size: 11px; margin-top: 2px; }

        .offer { border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; }
        .offer-t { font-weight: 700; font-size: 12.5px; color: #0f172a; }
        .offer-s { color: #64748b; font-size: 11px; margin-top: 1px; }
        .offer-d { color: #475569; font-size: 11px; margin-top: 8px; white-space: pre-line; }

        /* Stopka zawsze na dole strony (drukowana na każdej stronie). */
        .footer { position: fixed; left: 0; right: 0; bottom: 0; padding: 8px 52px;
            background: #fff; border-top: 1px solid #e2e8f0;
            color: #94a3b8; font-size: 10px; display: flex; justify-content: space-between; }
        .footer b { color: #0f172a; font-weight: 600; }

        /* Czyste łamanie na strony — wiersze i wpisy nie pękają w połowie,
           nagłówek sekcji nie zostaje sam na końcu strony. */
        .s-head { break-after: avoid; page-break-after: avoid; }
        .header { break-inside: avoid; page-break-inside: avoid; }
        .grid tr { break-inside: avoid; page-break-inside: avoid; }
        .job, .offer { break-inside: avoid; page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $cats = $candidate->license_categories ?? [];
    $exp = collect([
        $candidate->exp_reefer ? 'Chłodnia' : null,
        $candidate->exp_tilt ? 'Plandeka' : null,
        $candidate->exp_international ? 'Transport międzynarodowy' : null,
        $candidate->has_hds ? 'HDS' : null,
    ])->filter()->values();
    $langs = collect([
        $candidate->lang_de ? 'niemiecki' : null,
        $candidate->lang_en ? 'angielski' : null,
    ])->filter()->values();
@endphp

<div class="page">
    <!-- Nagłówek -->
    <div class="header">
        <div class="h-left">
            <div class="name">{{ $candidate->fullName() }}</div>
            <div class="role">Kierowca zawodowy</div>
            <div class="cats">
                @foreach ($cats as $cat)<span>{{ $cat }}</span>@endforeach
                @if ($candidate->has_adr)<span>ADR</span>@endif
                @if ($candidate->has_code_95) <span>Kod 95</span>@endif
            </div>
        </div>
        @if ($photoDataUri)
            <img class="photo" src="{{ $photoDataUri }}" alt="">
        @else
            <div class="photo-ph">{{ mb_substr($candidate->first_name, 0, 1) }}</div>
        @endif
    </div>
    <div class="rule"></div>
    <div class="rule-accent"></div>

    <!-- Kontakt -->
    <div class="contact">
        <span class="item"><b>Tel.</b> {{ $candidate->phone }}</span>
        @if ($candidate->email)<span class="item"><b>E-mail</b> {{ $candidate->email }}</span>@endif
        @if ($candidate->city)<span class="item"><b>Lokalizacja</b> {{ $candidate->city }}{{ $candidate->country ? ', '.$candidate->country : '' }}</span>@endif
        @if ($candidate->availability_from)<span class="item"><b>Dostępność</b> od {{ $candidate->availability_from->format('d.m.Y') }}</span>@endif
    </div>

    <!-- Dane osobowe -->
    @if ($candidate->date_of_birth || $candidate->nationality || $candidate->address || $langs->count())
        <div class="section">
            <div class="s-head">Dane osobowe</div>
            <table class="grid">
                @if ($candidate->date_of_birth)
                    <tr><td class="k">Data urodzenia</td><td class="v">{{ $candidate->date_of_birth->format('d.m.Y') }} <span class="muted">({{ $candidate->date_of_birth->age }} lat)</span></td></tr>
                @endif
                @if ($candidate->nationality)
                    <tr><td class="k">Narodowość</td><td class="v">{{ $candidate->nationality }}</td></tr>
                @endif
                @if ($candidate->address)
                    <tr><td class="k">Adres</td><td class="v">{{ $candidate->address }}</td></tr>
                @endif
                @if ($langs->count())
                    <tr><td class="k">Języki</td><td class="v">{{ $langs->implode(', ') }}</td></tr>
                @endif
            </table>
        </div>
    @endif

    <!-- Kwalifikacje -->
    <div class="section">
        <div class="s-head">Kwalifikacje</div>
        <table class="grid">
            <tr><td class="k">Kategorie prawa jazdy</td><td class="v">{{ implode(', ', $cats) ?: '—' }}</td></tr>
            <tr><td class="k">ADR</td><td class="v">@if ($candidate->has_adr)<span class="ok">Tak</span>@if ($candidate->adr_expiry) <span class="muted">(ważne do {{ $candidate->adr_expiry->format('m.Y') }})</span>@endif @else<span class="no">Brak</span>@endif</td></tr>
            <tr><td class="k">Kod 95</td><td class="v">@if ($candidate->has_code_95)<span class="ok">Tak</span>@if ($candidate->code_95_expiry) <span class="muted">(ważne do {{ $candidate->code_95_expiry->format('m.Y') }})</span>@endif @else<span class="no">Brak</span>@endif</td></tr>
            <tr><td class="k">Karta kierowcy</td><td class="v">@if ($candidate->driver_card_expiry)<span class="ok">Tak</span> <span class="muted">(ważna do {{ $candidate->driver_card_expiry->format('m.Y') }})</span>@else<span class="no">Brak</span>@endif</td></tr>
        </table>
    </div>

    <!-- Doświadczenie -->
    @if ($exp->count() || ! empty($candidate->experience_notes))
        <div class="section">
            <div class="s-head">Doświadczenie</div>
            @if ($exp->count())
                <div class="tags">@foreach ($exp as $e)<span>{{ $e }}</span>@endforeach</div>
            @endif
            @if (! empty($candidate->experience_notes))
                <p style="color:#475569; margin-top:8px;">{{ $candidate->experience_notes }}</p>
            @endif
        </div>
    @endif

    <!-- Historia pracy -->
    @if (! empty($candidate->work_history))
        <div class="section">
            <div class="s-head">Historia pracy</div>
            @foreach ($candidate->work_history as $job)
                <div class="job">
                    <div class="job-title">{{ $job['employer'] ?? '' }}@if (! empty($job['position'])) — {{ $job['position'] }}@endif</div>
                    @if (! empty($job['period']))<div class="job-meta">{{ $job['period'] }}</div>@endif
                    @if (! empty($job['description']))<div class="job-desc">{{ $job['description'] }}</div>@endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Oferta -->
    @if ($offer)
        <div class="section">
            <div class="s-head">Aplikuje na</div>
            <div class="offer">
                <div class="offer-t">{{ $offer->title }}</div>
                <div class="offer-s">@if ($company){{ $company->name }}@endif @if ($offer->country)&middot; {{ $offer->country }}@endif</div>
                @if (! empty($offer->public_description))<div class="offer-d">{{ $offer->public_description }}</div>@endif
            </div>
        </div>
    @endif

    <div class="footer">
        <span>Profil przygotowany przez <b>{{ $agencyName }}</b></span>
        <span>{{ $generatedAt }}</span>
    </div>
</div>
</body>
</html>
