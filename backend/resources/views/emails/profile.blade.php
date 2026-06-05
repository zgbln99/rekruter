<x-mail::message>
# Profil kandydata

W załączeniu przesyłamy profil kandydata **{{ $candidate->fullName() }}**.

@if ($candidate->license_categories)
Kategorie: {{ implode(', ', $candidate->license_categories) }}
@endif

W razie zainteresowania prosimy o kontakt.

Pozdrawiamy,<br>
{{ config('app.name') }}
</x-mail::message>
