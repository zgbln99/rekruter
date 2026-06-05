<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }

        /* Górny pasek brandowy */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #18181b;
            color: #fff;
            padding: 14px 28px;
        }
        .topbar .agency { font-size: 15px; font-weight: 700; letter-spacing: .2px; }
        .topbar .agency .dot { color: #10b981; }
        .topbar .doc {
            font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
            color: rgba(255,255,255,.6);
        }

        .layout { display: flex; min-height: 1040px; }

        /* Lewy panel (ciemny) */
        .sidebar {
            width: 230px;
            background: #18181b;
            color: #fff;
            padding: 24px 22px;
        }
        .photo, .photo-ph {
            width: 150px; height: 150px; border-radius: 14px;
            object-fit: cover; display: block; margin: 0 auto 16px;
            border: 3px solid #10b981;
        }
        .photo-ph {
            background: #10b981; color: #fff; font-size: 56px; font-weight: 700;
            text-align: center; line-height: 150px;
        }
        .s-name { font-size: 20px; font-weight: 700; text-align: center; line-height: 1.2; }
        .s-role { text-align: center; color: #10b981; font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
        .s-cats { text-align: center; margin: 14px 0 4px; }
        .s-cat {
            display: inline-block; background: rgba(255,255,255,.12); color: #fff;
            border-radius: 6px; padding: 3px 9px; font-size: 11px; font-weight: 600;
            margin: 2px;
        }
        .s-cat.mint { background: #10b981; }
        .s-section { margin-top: 22px; }
        .s-title {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px;
            color: #10b981; font-weight: 700; padding-bottom: 6px;
            border-bottom: 1px solid rgba(255,255,255,.15); margin-bottom: 10px;
        }
        .s-row { margin-bottom: 8px; }
        .s-label { color: rgba(255,255,255,.5); font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
        .s-value { color: #fff; font-size: 12px; }

        /* Prawa kolumna */
        .main { flex: 1; padding: 28px 30px; }
        .m-section { margin-bottom: 24px; }
        .m-title {
            font-size: 13px; font-weight: 700; color: #18181b; text-transform: uppercase;
            letter-spacing: .8px; margin-bottom: 12px; padding-bottom: 6px;
            border-bottom: 2px solid #10b981; display: inline-block;
        }
        .quals { width: 100%; border-collapse: collapse; }
        .quals td { padding: 5px 0; font-size: 12px; vertical-align: top; }
        .quals .q-label { color: #6b7280; width: 180px; }
        .quals .q-val { color: #111827; font-weight: 600; }
        .check { color: #10b981; font-weight: 700; }
        .cross { color: #d1d5db; }

        .tags span {
            display: inline-block; background: #ecfdf5; color: #059669;
            border-radius: 999px; padding: 3px 11px; font-size: 11px; font-weight: 600;
            margin: 0 5px 5px 0;
        }

        /* Historia pracy — timeline */
        .job { padding-left: 16px; border-left: 2px solid #e5e7eb; margin-bottom: 14px; position: relative; }
        .job::before {
            content: ''; position: absolute; left: -5px; top: 4px;
            width: 8px; height: 8px; border-radius: 50%; background: #10b981;
        }
        .job-title { font-weight: 700; color: #111827; font-size: 12.5px; }
        .job-meta { color: #6b7280; font-size: 11px; }
        .job-desc { color: #4b5563; font-size: 11px; margin-top: 2px; }

        .offer-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; }
        .offer-title { font-weight: 700; color: #18181b; font-size: 13px; }
        .offer-sub { color: #6b7280; font-size: 11px; margin-top: 2px; }
        .offer-desc { color: #374151; font-size: 11px; margin-top: 8px; white-space: pre-line; }

        .footer { text-align: center; color: #9ca3af; font-size: 10px; padding: 14px; border-top: 1px solid #e5e7eb; }
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
        $candidate->lang_de ? 'Niemiecki' : null,
        $candidate->lang_en ? 'Angielski' : null,
    ])->filter()->values();
@endphp

<div class="topbar">
    <span class="agency"><span class="dot">●</span> {{ $agencyName }}</span>
    <span class="doc">Profil kandydata</span>
</div>

<div class="layout">
    <aside class="sidebar">
        @if ($photoDataUri)
            <img class="photo" src="{{ $photoDataUri }}" alt="">
        @else
            <div class="photo-ph">{{ mb_substr($candidate->first_name, 0, 1) }}</div>
        @endif

        <div class="s-name">{{ $candidate->fullName() }}</div>
        <div class="s-role">Kierowca zawodowy</div>

        <div class="s-cats">
            @foreach ($cats as $cat)
                <span class="s-cat mint">{{ $cat }}</span>
            @endforeach
            @if ($candidate->has_adr)<span class="s-cat">ADR</span>@endif
            @if ($candidate->has_code_95)<span class="s-cat">Kod 95</span>@endif
        </div>

        <div class="s-section">
            <div class="s-title">Kontakt</div>
            <div class="s-row"><div class="s-label">Telefon</div><div class="s-value">{{ $candidate->phone }}</div></div>
            @if ($candidate->email)
                <div class="s-row"><div class="s-label">E-mail</div><div class="s-value">{{ $candidate->email }}</div></div>
            @endif
            @if ($candidate->city)
                <div class="s-row"><div class="s-label">Miejscowość</div><div class="s-value">{{ $candidate->city }}{{ $candidate->country ? ', '.$candidate->country : '' }}</div></div>
            @endif
            @if ($candidate->address)
                <div class="s-row"><div class="s-label">Adres</div><div class="s-value">{{ $candidate->address }}</div></div>
            @endif
        </div>

        <div class="s-section">
            <div class="s-title">Dane osobowe</div>
            @if ($candidate->date_of_birth)
                <div class="s-row"><div class="s-label">Data urodzenia</div><div class="s-value">{{ $candidate->date_of_birth->format('d.m.Y') }} ({{ $candidate->date_of_birth->age }} l.)</div></div>
            @endif
            @if ($candidate->nationality)
                <div class="s-row"><div class="s-label">Narodowość</div><div class="s-value">{{ $candidate->nationality }}</div></div>
            @endif
            @if ($candidate->availability_from)
                <div class="s-row"><div class="s-label">Dostępność od</div><div class="s-value">{{ $candidate->availability_from->format('d.m.Y') }}</div></div>
            @endif
        </div>

        @if ($langs->count())
            <div class="s-section">
                <div class="s-title">Języki</div>
                @foreach ($langs as $lang)
                    <div class="s-value" style="margin-bottom:3px;">{{ $lang }}</div>
                @endforeach
            </div>
        @endif
    </aside>

    <main class="main">
        <div class="m-section">
            <div class="m-title">Kwalifikacje</div>
            <table class="quals">
                <tr>
                    <td class="q-label">Kategorie prawa jazdy</td>
                    <td class="q-val">{{ implode(', ', $cats) ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="q-label">ADR</td>
                    <td class="q-val">
                        @if ($candidate->has_adr)<span class="check">✓ Tak</span>@if ($candidate->adr_expiry) <span style="color:#6b7280;font-weight:400;">(do {{ $candidate->adr_expiry->format('m.Y') }})</span>@endif @else <span class="cross">— Brak</span>@endif
                    </td>
                </tr>
                <tr>
                    <td class="q-label">Kod 95</td>
                    <td class="q-val">
                        @if ($candidate->has_code_95)<span class="check">✓ Tak</span>@if ($candidate->code_95_expiry) <span style="color:#6b7280;font-weight:400;">(do {{ $candidate->code_95_expiry->format('m.Y') }})</span>@endif @else <span class="cross">— Brak</span>@endif
                    </td>
                </tr>
                <tr>
                    <td class="q-label">Karta kierowcy</td>
                    <td class="q-val">
                        @if ($candidate->driver_card_expiry)<span class="check">✓ Tak</span> <span style="color:#6b7280;font-weight:400;">(do {{ $candidate->driver_card_expiry->format('m.Y') }})</span>@else <span class="cross">— Brak</span>@endif
                    </td>
                </tr>
            </table>
        </div>

        @if ($exp->count() || ! empty($candidate->experience_notes))
            <div class="m-section">
                <div class="m-title">Doświadczenie</div>
                @if ($exp->count())
                    <div class="tags">
                        @foreach ($exp as $e)<span>{{ $e }}</span>@endforeach
                    </div>
                @endif
                @if (! empty($candidate->experience_notes))
                    <p style="color:#374151; margin-top:8px;">{{ $candidate->experience_notes }}</p>
                @endif
            </div>
        @endif

        @if (! empty($candidate->work_history))
            <div class="m-section">
                <div class="m-title">Historia pracy</div>
                @foreach ($candidate->work_history as $job)
                    <div class="job">
                        <div class="job-title">{{ $job['employer'] ?? '' }}@if (! empty($job['position'])) — {{ $job['position'] }}@endif</div>
                        @if (! empty($job['period']))<div class="job-meta">{{ $job['period'] }}</div>@endif
                        @if (! empty($job['description']))<div class="job-desc">{{ $job['description'] }}</div>@endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($offer)
            <div class="m-section">
                <div class="m-title">Aplikuje na</div>
                <div class="offer-box">
                    <div class="offer-title">{{ $offer->title }}</div>
                    <div class="offer-sub">
                        @if ($company){{ $company->name }}@endif
                        @if ($offer->country) &middot; {{ $offer->country }}@endif
                    </div>
                    @if (! empty($offer->public_description))
                        <div class="offer-desc">{{ $offer->public_description }}</div>
                    @endif
                </div>
            </div>
        @endif
    </main>
</div>

<div class="footer">
    Profil przygotowany przez {{ $agencyName }} &middot; {{ $generatedAt }}
</div>
</body>
</html>
