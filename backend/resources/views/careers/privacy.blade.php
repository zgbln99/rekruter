@extends('careers.layout')

@php
    $agencyName = $tenant?->agencyName() ?? 'edge recruiting';
    $email = $tenant?->settings['agency_email'] ?? null;
    $phone = $tenant?->agencyPhone();
@endphp

@section('title', 'Polityka prywatności - '.$agencyName)
@section('description', 'Polityka prywatności i informacja o przetwarzaniu danych osobowych (RODO) oraz plikach cookie.')

@section('content')
    <section class="legal-hero">
        <div class="wrap">
            <span class="kicker light"><span class="mk"></span> Informacja prawna</span>
            <h1>Polityka prywatności</h1>
            <p>Zasady przetwarzania danych osobowych (RODO) i wykorzystania plików cookie.</p>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="legal-content prose">
                <h2>1. Administrator danych</h2>
                <p>Administratorem Twoich danych osobowych jest <strong>{{ $agencyName }}</strong>.
                @if ($phone) Telefon kontaktowy: {{ $phone }}.@endif
                @if ($email) Adres e-mail: <a href="mailto:{{ $email }}">{{ $email }}</a>.@endif
                </p>

                <h2>2. Jakie dane zbieramy</h2>
                <p>Przetwarzamy dane, które podajesz w formularzu aplikacyjnym: imię i nazwisko, numer telefonu, adres e-mail, miasto, posiadane kategorie prawa jazdy, treść wiadomości oraz - jeśli je załączysz - dokumenty (np. CV).</p>

                <h2>3. Cel i podstawa prawna</h2>
                <ul>
                    <li>Przeprowadzenie procesu rekrutacji na wybrane stanowisko - na podstawie Twojej zgody (art. 6 ust. 1 lit. a RODO) oraz podjęcia działań przed zawarciem umowy (art. 6 ust. 1 lit. b RODO).</li>
                    <li>Kontakt zwrotny w sprawie oferty pracy.</li>
                </ul>

                <h2>4. Odbiorcy danych</h2>
                <p>Twoje dane mogą zostać przekazane pracodawcy (klientowi), do którego prowadzona jest rekrutacja, oraz podmiotom wspierającym nas technicznie (np. hosting), wyłącznie w zakresie niezbędnym do realizacji procesu rekrutacji.</p>

                <h2>5. Okres przechowywania</h2>
                <p>Dane przechowujemy przez czas trwania rekrutacji, a po jej zakończeniu - do czasu wycofania zgody lub przez okres uzasadniony prawnie. Możesz w każdej chwili poprosić o ich usunięcie.</p>

                <h2>6. Twoje prawa</h2>
                <p>Masz prawo do: dostępu do swoich danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, przenoszenia danych oraz wniesienia sprzeciwu. Masz też prawo wycofać zgodę w dowolnym momencie oraz wnieść skargę do Prezesa Urzędu Ochrony Danych Osobowych.</p>

                <h2>7. Pliki cookie</h2>
                <p>Strona korzysta wyłącznie z <strong>niezbędnych plików cookie</strong>, które są konieczne do jej prawidłowego działania (m.in. bezpieczna obsługa formularza aplikacyjnego). Nie używamy cookies marketingowych ani śledzących. Pliki cookie możesz w każdej chwili wyłączyć w ustawieniach swojej przeglądarki - może to jednak ograniczyć działanie niektórych funkcji.</p>

                <h2>8. Kontakt</h2>
                <p>W sprawach dotyczących danych osobowych skontaktuj się z nami.
                @if ($phone) Telefon: {{ $phone }}.@endif
                @if ($email) E-mail: <a href="mailto:{{ $email }}">{{ $email }}</a>.@endif
                </p>

                <p style="margin-top:32px;color:var(--muted);font-size:14px">Ostatnia aktualizacja: {{ now()->format('d.m.Y') }}</p>
            </div>
        </div>
    </section>
@endsection
