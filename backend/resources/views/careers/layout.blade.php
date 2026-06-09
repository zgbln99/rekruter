@php
    $agencyName = $tenant?->agencyName() ?? 'edge recruiting';
    $agencyPhone = $tenant?->agencyPhone();
    $branding = $tenant?->branding() ?? [];
    $hasLogo = ! empty($branding['logo']['path']);
    $bv = $branding['v'] ?? 0;
    $logoUrl = url('/api/v1/branding/logo?v='.$bv);
    $faviconUrl = $hasLogo || ! empty($branding['favicon']['path']) ? url('/api/v1/branding/favicon?v='.$bv) : null;
    $email = $tenant?->settings['agency_email'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Praca dla kierowców — '.$agencyName)</title>
    <meta name="description" content="@yield('description', 'Aktualne oferty pracy dla kierowców zawodowych. Aplikuj online lub zadzwoń.')">
    <link rel="canonical" href="{{ url()->current() }}">

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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('careers/careers.css') }}?v=6">
    @stack('head')
</head>
<body>
<div class="page">
    <div class="topbar">
        <div class="wrap">
            <span>Agencja pracy <span class="sep">/</span> Kierowcy zawodowi</span>
            @if ($agencyPhone)<a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}">{{ $agencyPhone }}</a>@endif
        </div>
    </div>

    <header class="site-header">
        <div class="wrap bar">
            <a href="{{ route('careers.index') }}" class="brand">
                @if ($hasLogo)
                    <img src="{{ $logoUrl }}" alt="{{ $agencyName }}">
                @else
                    {{ $agencyName }}
                @endif
            </a>
            <div class="header-actions">
                <a href="{{ route('careers.index') }}" class="header-link">Oferty</a>
                @if ($agencyPhone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}" class="btn btn-dark btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .8-.3 1l-2.1 2.2z"/></svg>
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
                    <p class="fdesc">Rekrutacja kierowców zawodowych. Sprawdzone oferty, bezpośredni kontakt, realne zatrudnienie.</p>
                </div>
                <div class="fcol">
                    <h4>Kontakt</h4>
                    @if ($agencyPhone)<a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}">{{ $agencyPhone }}</a>@endif
                    @if ($email)<a href="mailto:{{ $email }}">{{ $email }}</a>@endif
                </div>
                <div class="fcol">
                    <h4>Nawigacja</h4>
                    <a href="{{ route('careers.index') }}">Wszystkie oferty</a>
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
