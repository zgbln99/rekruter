<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            font-size: 13px;
            line-height: 1.5;
        }
        .page { padding: 32px 36px; }
        .header {
            display: flex;
            align-items: center;
            border-bottom: 3px solid #0f766e;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .photo {
            width: 96px; height: 96px;
            border-radius: 12px;
            object-fit: cover;
            background: #e5e7eb;
            margin-right: 20px;
            border: 1px solid #d1d5db;
        }
        .photo-placeholder {
            width: 96px; height: 96px;
            border-radius: 12px;
            background: #0f766e;
            color: #fff;
            margin-right: 20px;
            text-align: center;
            line-height: 96px;
            font-size: 36px;
            font-weight: 700;
        }
        .name { font-size: 26px; font-weight: 700; color: #111827; }
        .subtitle { color: #6b7280; margin-top: 2px; }
        .badges { margin-top: 10px; }
        .badge {
            display: inline-block;
            background: #0f766e;
            color: #fff;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 6px;
        }
        .badge.alt { background: #b45309; }
        .badge.green { background: #047857; }
        .section { margin-bottom: 22px; }
        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #0f766e;
            font-weight: 700;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .grid { width: 100%; }
        .row { display: flex; padding: 5px 0; }
        .label { width: 180px; color: #6b7280; }
        .value { font-weight: 600; color: #111827; }
        .footer {
            margin-top: 36px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        @if ($photoDataUri)
            <img class="photo" src="{{ $photoDataUri }}" alt="">
        @else
            <div class="photo-placeholder">{{ mb_substr($candidate->first_name, 0, 1) }}</div>
        @endif
        <div>
            <div class="name">{{ $candidate->fullName() }}</div>
            <div class="subtitle">Kierowca zawodowy @if ($candidate->city)&middot; {{ $candidate->city }}@endif</div>
            <div class="badges">
                @foreach ($candidate->license_categories ?? [] as $cat)
                    <span class="badge">{{ $cat }}</span>
                @endforeach
                @if ($candidate->has_adr)<span class="badge alt">ADR</span>@endif
                @if ($candidate->has_code_95)<span class="badge green">Kod 95</span>@endif
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Dane kontaktowe</div>
        <div class="grid">
            <div class="row"><div class="label">Telefon</div><div class="value">{{ $candidate->phone }}</div></div>
            @if ($candidate->email)
                <div class="row"><div class="label">E-mail</div><div class="value">{{ $candidate->email }}</div></div>
            @endif
            @if ($candidate->city)
                <div class="row"><div class="label">Miejscowość</div><div class="value">{{ $candidate->city }}{{ $candidate->country ? ', '.$candidate->country : '' }}</div></div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Uprawnienia i kwalifikacje</div>
        <div class="grid">
            <div class="row">
                <div class="label">Kategorie prawa jazdy</div>
                <div class="value">{{ implode(', ', $candidate->license_categories ?? []) ?: '—' }}</div>
            </div>
            <div class="row"><div class="label">ADR</div><div class="value">{{ $candidate->has_adr ? 'Tak' : 'Nie' }}@if ($candidate->adr_expiry) (ważne do {{ $candidate->adr_expiry->format('d.m.Y') }})@endif</div></div>
            <div class="row"><div class="label">Kod 95</div><div class="value">{{ $candidate->has_code_95 ? 'Tak' : 'Nie' }}@if ($candidate->code_95_expiry) (ważne do {{ $candidate->code_95_expiry->format('d.m.Y') }})@endif</div></div>
            @if ($candidate->driver_card_expiry)
                <div class="row"><div class="label">Karta kierowcy</div><div class="value">ważna do {{ $candidate->driver_card_expiry->format('d.m.Y') }}</div></div>
            @endif
        </div>
    </div>

    <div class="footer">
        Profil wygenerowany przez {{ $agencyName }} &middot; {{ $generatedAt }}
    </div>
</div>
</body>
</html>
