@extends('careers.layout')

@php
    $loc = collect([$offer->country, $offer->region_base])->filter()->implode(' · ') ?: ($offer->location ?: null);
    $salary = trim((string) $offer->salary_amount) !== '' ? trim($offer->salary_amount.' '.$offer->currency) : null;
    $cats = is_array($offer->required_categories) ? $offer->required_categories : [];
    $desc = \App\Support\Html\SafeHtml::clean($offer->public_description);
    $faq = is_array($offer->faq) ? array_filter($offer->faq, fn ($f) => ! empty($f['q'])) : [];
    $agencyPhone = $tenant?->agencyPhone();
    $waPhone = $agencyPhone ? preg_replace('/\D+/', '', $agencyPhone) : null;
    $metaDesc = \Illuminate\Support\Str::limit(trim(strip_tags($desc)) ?: $offer->title.($loc ? ' — '.$loc : ''), 155);
    $reqLabels = ['adr' => 'ADR', 'code_95' => 'Kod 95', 'experience' => 'Doświadczenie', 'tacho' => 'Karta kierowcy'];
    $reqs = is_array($offer->requirements) ? array_keys(array_filter($offer->requirements)) : [];
@endphp

@section('title', $offer->title.($loc ? ' — '.$loc : '').' | Praca dla kierowców')
@section('og_title', $offer->title.($loc ? ' — '.$loc : ''))
@section('description', $metaDesc)

@push('head')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    <div class="detail">
        <div class="wrap">
            <a href="{{ route('careers.index') }}" class="breadcrumb">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Wszystkie oferty
            </a>

            <div class="detail-grid">
                {{-- Lewa kolumna --}}
                <div>
                    <h1>{{ $offer->title }}</h1>
                    <div class="sub">
                        @if ($offer->company?->name)
                            <span class="row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-3"/></svg>{{ $offer->company->name }}</span>
                        @endif
                        @if ($loc)
                            <span class="row"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-5.3-7-11a7 7 0 1 1 14 0c0 5.7-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $loc }}</span>
                        @endif
                    </div>

                    <div class="facts">
                        @if ($salary)
                            <div class="fact salary"><div class="k">Wynagrodzenie</div><div class="v">{{ $salary }}</div></div>
                        @endif
                        @if ($offer->work_system)
                            <div class="fact"><div class="k">System pracy</div><div class="v">{{ $offer->work_system }}</div></div>
                        @endif
                        @if (count($cats))
                            <div class="fact"><div class="k">Kategorie</div><div class="v">{{ implode(', ', $cats) }}</div></div>
                        @endif
                        @if ($offer->start_date)
                            <div class="fact"><div class="k">Start</div><div class="v">{{ $offer->start_date->format('d.m.Y') }}</div></div>
                        @endif
                    </div>

                    @if ($desc)
                        <div class="block-title">Opis stanowiska</div>
                        <div class="prose">{!! $desc !!}</div>
                    @endif

                    @if (count($reqs))
                        <div class="block-title">Wymagania</div>
                        <div class="req-list">
                            @foreach ($reqs as $r)
                                <div class="item">
                                    <span class="tick"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                                    {{ $reqLabels[$r] ?? \Illuminate\Support\Str::headline($r) }}
                                </div>
                            @endforeach
                            @foreach ($cats as $c)
                                <div class="item"><span class="tick"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>Prawo jazdy kat. {{ $c }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if (count($faq))
                        <div class="block-title">Najczęstsze pytania</div>
                        <div class="faq">
                            @foreach ($faq as $f)
                                <details>
                                    <summary>{{ $f['q'] }}</summary>
                                    <p>{{ $f['a'] ?? '' }}</p>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Prawa kolumna: kontakt + aplikacja --}}
                <aside class="aside" id="aplikuj">
                    <div class="apply-card">
                        <div class="ac-head">
                            <h3>Aplikuj na to stanowisko</h3>
                            <p>Wypełnij formularz albo skontaktuj się od razu.</p>
                        </div>
                        <div class="ac-body">
                            @if ($agencyPhone)
                                <div class="contact-row">
                                    <a href="tel:{{ preg_replace('/\s+/', '', $agencyPhone) }}" class="btn btn-primary btn-block">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .8-.3 1l-2.1 2.2z"/></svg>
                                        Zadzwoń: {{ $agencyPhone }}
                                    </a>
                                    @if ($waPhone)
                                        <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode('Dzień dobry, piszę w sprawie oferty: '.$offer->title) }}" target="_blank" rel="noopener" class="btn btn-wa btn-block">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.7 4.8-1.3A10 10 0 1 0 12 2Zm5.3 14c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 .9-2.2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.4.6c-.2.2-.3.4-.1.7.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.2.1.4.1.6-.1l.7-.9c.2-.2.4-.2.6-.1l1.9.9c.3.1.4.2.5.3.1.3.1.7-.1 1.4Z"/></svg>
                                            WhatsApp
                                        </a>
                                    @endif
                                </div>
                            @endif

                            @if (session('applied'))
                                <div class="flash-ok">
                                    <span class="ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                                    <div><b>Dziękujemy!</b><br>Twoja aplikacja została wysłana. Skontaktujemy się wkrótce.</div>
                                </div>
                            @else
                                @if ($errors->any())
                                    <div class="err-box">
                                        @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('careers.apply', ['jobPosting' => $offer->id]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="text" name="company" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                                    <div class="form-grid">
                                        <div class="form-field"><label>Imię *</label><input name="first_name" value="{{ old('first_name') }}" required></div>
                                        <div class="form-field"><label>Nazwisko *</label><input name="last_name" value="{{ old('last_name') }}" required></div>
                                        <div class="form-field full"><label>Telefon *</label><input name="phone" type="tel" value="{{ old('phone') }}" required placeholder="np. +48 600 100 200"></div>
                                        <div class="form-field full"><label>E-mail</label><input name="email" type="email" value="{{ old('email') }}"></div>
                                        <div class="form-field full"><label>Miasto</label><input name="city" value="{{ old('city') }}"></div>
                                        <div class="form-field full">
                                            <label>Posiadane kategorie</label>
                                            <div class="cat-check">
                                                @foreach (['B','C','C+E','D'] as $c)
                                                    <label><input type="checkbox" name="categories[]" value="{{ $c }}"><span>{{ $c }}</span></label>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="form-field full"><label>Wiadomość / doświadczenie</label><textarea name="message" rows="3" placeholder="Krótko o sobie, dyspozycyjność…">{{ old('message') }}</textarea></div>
                                        <div class="form-field full">
                                            <label>CV (opcjonalnie)</label>
                                            <input type="file" name="cv" class="file-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        </div>
                                        <div class="form-field full">
                                            <label class="consent">
                                                <input type="checkbox" name="consent" value="1" required>
                                                <span>Wyrażam zgodę na przetwarzanie moich danych osobowych w celu przeprowadzenia procesu rekrutacji (RODO).</span>
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-brand btn-block" style="margin-top:14px">Wyślij aplikację</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>

            @if ($related->count())
                <div class="section-head" style="margin-top:54px">
                    <h2>Podobne oferty</h2>
                </div>
                <div class="grid">
                    @foreach ($related as $offer)
                        @include('careers.partials.offer-card', ['offer' => $offer])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
