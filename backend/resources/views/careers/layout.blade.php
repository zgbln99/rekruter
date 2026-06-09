@php
    $agencyName = $tenant?->agencyName() ?? 'edge recruiting';
    $agencyPhone = $tenant?->agencyPhone();
    $branding = $tenant?->branding() ?? [];
    $hasLogo = ! empty($branding['logo']['path']);
    $bv = $branding['v'] ?? 0;
    $logoUrl = url('/api/v1/branding/logo?v='.$bv);
    $faviconUrl = $hasLogo || ! empty($branding['favicon']['path']) ? url('/api/v1/branding/favicon?v='.$bv) : null;
    $waPhone = $agencyPhone ? preg_replace('/\D+/', '', $agencyPhone) : null;
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Praca dla kierowców — '.$agencyName)</title>
    <meta name="description" content="@yield('description', 'Aktualne oferty pracy dla kierowców zawodowych. Aplikuj online lub zadzwoń.')">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $agencyName }}">
    <meta property="og:title" content="@yield('og_title', 'Praca dla kierowców — '.$agencyName)">
    <meta property="og:description" content="@yield('description', 'Aktualne oferty pracy dla kierowców zawodowych.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($hasLogo)<meta property="og:image" content="{{ $logoUrl }}">@endif
    <meta name="twitter:card" content="summary">

    @if ($faviconUrl)<link rel="icon" href="{{ $faviconUrl }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('careers/careers.css') }}?v=3">
    @stack('head')
</head>
<body>
<div class="page">
    <header class="site-header">
        <div class="wrap bar">
            <a href="{{ route('careers.index') }}" class="brand">
                @if ($hasLogo)
                    <img src="{{ $logoUrl }}" alt="{{ $agencyName }}">
                @else
                    <span>{{ $agencyName }}</span>
                @endif
            </a>
            <div class="header-actions">
                <a href="{{ route('careers.index') }}" class="btn btn-ghost btn-sm hide-sm">Oferty pracy</a>
                @if ($agencyPhone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}" class="btn btn-brand btn-sm">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .8-.3 1l-2.1 2.2z"/></svg>
                        {{ $agencyPhone }}
                    </a>
                @endif
            </div>
        </div>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="wrap">
            <div class="cols">
                <div>
                    <div class="brand">{{ $agencyName }}</div>
                    <p style="max-width:320px;font-size:14px;">Rekrutacja kierowców zawodowych. Sprawdzone oferty, szybki kontakt, realne zatrudnienie.</p>
                </div>
                <div>
                    <div style="color:#fff;font-weight:700;margin-bottom:10px;">Kontakt</div>
                    @if ($agencyPhone)<p><a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}">{{ $agencyPhone }}</a></p>@endif
                    @php $email = $tenant?->settings['agency_email'] ?? null; @endphp
                    @if ($email)<p><a href="mailto:{{ $email }}">{{ $email }}</a></p>@endif
                    <p><a href="{{ route('careers.index') }}">Wszystkie oferty</a></p>
                </div>
            </div>
            <div class="legal">
                <span>© {{ date('Y') }} {{ $agencyName }}</span>
                <span>Aplikując wyrażasz zgodę na przetwarzanie danych w celach rekrutacyjnych (RODO).</span>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
