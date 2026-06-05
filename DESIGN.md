# Rekruter — Recruitment Operating System

> **Status dokumentu:** Wersja 0.2 (ZATWIERDZONY — start Fazy 0)
> **Data:** 2026-06-05
> **Właściciel:** Zespół produktowy / CTO
> **Charakter:** Ten plik (`DESIGN.md`) jest **głównym i jedynym źródłem prawdy** dla projektu.
> Każda decyzja architektoniczna i każda większa funkcjonalność musi być najpierw
> opisana tutaj, zanim trafi do kodu. Każda zmiana architektury aktualizuje ten plik.

> **Historia pliku:** Wcześniejsza zawartość `DESIGN.md` była wyekstrahowaną specyfikacją
> systemu wizualnego marki *Mintlify* (niezwiązaną z projektem). Decyzją właściciela plik
> został przeznaczony na **główny dokument projektowy Rekrutera** i jego treść zastąpiono
> niniejszą specyfikacją. Wybrane zasady dyscypliny tokenów (skala odstępów, skala promieni,
> typografia Inter) mogą zostać zaadaptowane w systemie wizualnym (sekcja 11).

---

## Spis treści

1. [Analiza biznesowa](#1-analiza-biznesowa)
2. [Wizja produktu i KPI](#2-wizja-produktu-i-kpi)
3. [Persony i scenariusze](#3-persony-i-scenariusze)
4. [Architektura systemu](#4-architektura-systemu)
5. [Model domenowy](#5-model-domenowy)
6. [ERD — schemat bazy danych](#6-erd--schemat-bazy-danych)
7. [Architektura backendu (Laravel 11)](#7-architektura-backendu-laravel-11)
8. [Architektura frontendu (Nuxt 3)](#8-architektura-frontendu-nuxt-3)
9. [API REST](#9-api-rest)
10. [UX mobilny](#10-ux-mobilny)
11. [System wizualny / Design System](#11-system-wizualny--design-system)
12. [Bezpieczeństwo i RODO](#12-bezpieczeństwo-i-rodo)
13. [Struktura katalogów](#13-struktura-katalogów)
14. [Deployment](#14-deployment)
15. [Rozszerzalność na przyszłość](#15-rozszerzalność-na-przyszłość)
16. [Ryzyka i mitygacje](#16-ryzyka-i-mitygacje)
17. [Roadmapa wdrożeniowa](#17-roadmapa-wdrożeniowa)
18. [Otwarte pytania do decyzji](#18-otwarte-pytania-do-decyzji)

---

## 1. Analiza biznesowa

### 1.1 Kontekst

Agencja rekrutacyjna specjalizująca się w **zatrudnianiu kierowców zawodowych** (kategorie
C/C+E/D, ADR, Kod 95, karta kierowcy). Obecny proces opiera się na Excelu, papierowych
notatkach, ręcznie składanych profilach PDF i rozproszonych dokumentach. To generuje:

- utratę kandydatów (brak follow-upu, zapomniane terminy oddzwonienia),
- powielanie danych i duplikaty kandydatów,
- wolne tworzenie profili dla klientów (ręczne PDF),
- brak audytu (kto, kiedy, co zrobił z danymi kandydata),
- ryzyko RODO przy przechowywaniu skanów dowodów/paszportów w mailu i na dyskach.

### 1.2 Czym to jest (a czym nie jest)

To **nie jest** kolejny klasyczny ATS dla działu HR korporacji. To **Recruitment
Operating System** — system operacyjny do codziennej pracy małego zespołu rekruterskiego,
zoptymalizowany pod **jeden krytyczny moment: rozmowę telefoniczną z kandydatem**.

System zastępuje: Excel, papierowe notatki, ręczne profile, rozproszone dokumenty,
ręczną wysyłkę PDF, ręczne zarządzanie rekrutacjami.

### 1.3 Główni użytkownicy

| Rola | Opis | Główne środowisko |
|---|---|---|
| **Rekruterka** | Odbiera telefony, szybko wpisuje dane, prowadzi follow-up, buduje profile | **Telefon (mobile)** |
| **Administrator** | Zarządza firmami, ogłoszeniami, użytkownikami, konfiguracją, wgląd w audyt | Telefon + desktop |

System projektujemy **pod rekruterkę przy telefonie**. Wszystko inne jest dodatkiem.

---

## 2. Wizja produktu i KPI

### 2.1 Główny KPI

> **Dodanie nowego kandydata podczas rozmowy telefonicznej < 60 sekund.**

Każda decyzja projektowa jest oceniana przez pryzmat tego celu. Jeżeli rozwiązanie
wydłuża „quick add", jest odrzucane lub przeprojektowywane.

### 2.2 KPI wspierające

| KPI | Cel | Dlaczego |
|---|---|---|
| Czas od telefonu do wpisu w bazie | < 60 s | Główny cel |
| % kontaktów z zapisanym follow-upem | > 90% | Brak utraty kandydatów |
| Czas wygenerowania profilu PDF | < 10 s | Szybka obsługa klienta |
| Liczba kliknięć do „Nowy kandydat" | 1 (FAB) | Minimalny tarcie |
| Duplikaty kandydatów (po telefonie) | ~0% | Deduplikacja po numerze |

### 2.3 Zasada projektowa „60 sekund"

Formularz szybkiego dodania kandydata podczas rozmowy ma **tylko pola krytyczne**:
- numer telefonu (klucz deduplikacji),
- imię (lub samo imię, nazwisko opcjonalne),
- kategoria prawa jazdy (chip-y, jeden tap),
- notatka głosowa/tekstowa,
- wynik kontaktu + opcjonalny termin następnego kontaktu (auto-task).

Wszystko inne uzupełniane jest później (progresywne wzbogacanie profilu).

---

## 3. Persony i scenariusze

### 3.1 Persona: „Ania" — Rekruterka

- Pracuje głównie z telefonem w ręku, często w ruchu.
- Odbiera 30–60 telefonów dziennie.
- Potrzebuje: jednego kciuka, dużych przycisków, zero przewijania w formularzu quick-add.
- Frustracje: wolne formularze, dużo pól, przełączanie ekranów, gubienie terminów.

**Scenariusz krytyczny (happy path < 60 s):**
1. Dzwoni nieznany numer → Ania odbiera.
2. Otwiera apkę, tapie **FAB „Nowy Kandydat"**.
3. Numer telefonu może być wstępnie wklejony / wpisuje go.
4. System sprawdza duplikat „w locie" → jeśli istnieje, otwiera istniejący profil.
5. Wpisuje imię, tapuje chip „C+E", „ADR".
6. Dyktuje/wpisuje krótką notatkę.
7. Wybiera wynik kontaktu „Zainteresowany", ustawia „oddzwonić jutro 10:00".
8. **Zapis** → kandydat w bazie, automatyczny task follow-up, wpis w call log.

### 3.2 Persona: „Marek" — Administrator

- Zarządza firmami-klientami i ogłoszeniami.
- Generuje i wysyła profile kandydatów do klientów.
- Przegląda pipeline (kanban) i audyt aktywności.
- Korzysta z telefonu i czasem desktopu (większe operacje).

---

## 4. Architektura systemu

### 4.1 Styl architektury

- **Headless / API-first**: Laravel 11 jako REST API (backend), Nuxt 3 jako klient PWA (frontend). Rozdzielenie pozwala na przyszłe klienty (np. natywna apka, integracje).
- **Modular Monolith** w backendzie: jeden deployment, ale wyraźny podział na moduły domenowe (Companies, JobPostings, Candidates, Documents, Contacts, Tasks, Pipeline, Profiles/PDF, Activity). Granice modułów umożliwiają przyszłe wydzielenie do usług, jeśli zajdzie potrzeba — bez przedwczesnej mikroserwisowości.
- **Asynchroniczność**: ciężkie operacje (generowanie PDF, wysyłka maili, przyszły OCR/AI) idą przez kolejki Redis (Laravel Queue + Horizon).
- **Multi-tenant ready**: od początku kolumna `tenant_id` na kluczowych tabelach (jeden tenant domyślny teraz), aby przyszłe wdrożenie SaaS multi-tenant nie wymagało migracji rozrywającej dane.

### 4.2 Diagram wysokiego poziomu

```
┌──────────────────────────────────────────────────────────────┐
│                      KLIENT (PWA)                              │
│   Nuxt 3 / Vue 3 / TS / Pinia / Vue Query / Tailwind          │
│   - Mobile-first UI, offline-aware, installable               │
└───────────────┬──────────────────────────────────────────────┘
                │ HTTPS / REST (JSON) + Bearer (Sanctum)
                ▼
┌──────────────────────────────────────────────────────────────┐
│                    LARAVEL 11 API                              │
│  Controllers → Form Requests → Actions/Services → Models      │
│  Policies (autoryzacja) · Resources (serializacja)            │
│  Moduły: Companies · JobPostings · Candidates · Documents ·   │
│          Contacts(CallLog) · Tasks · Pipeline · Profiles/PDF· │
│          Activity(audit)                                       │
└───┬───────────────┬───────────────┬──────────────┬────────────┘
    │               │               │              │
    ▼               ▼               ▼              ▼
┌────────┐   ┌────────────┐   ┌──────────┐   ┌──────────────┐
│Postgres│   │   Redis    │   │  S3 /    │   │   SMTP       │
│  (DB)  │   │ queue+cache│   │  MEGA S3 │   │  (mail)      │
└────────┘   └─────┬──────┘   └──────────┘   └──────────────┘
                   │
                   ▼
           ┌────────────────┐
           │ Queue Workers  │  PDF gen · Mail · (future) OCR/AI
           │ (Horizon)      │
           └────────────────┘
```

### 4.3 Kluczowe decyzje architektoniczne (ADR-skrót)

| # | Decyzja | Uzasadnienie |
|---|---|---|
| ADR-1 | Modular monolith zamiast mikroserwisów | Mały zespół, szybkość dostarczania, prostota deploymentu |
| ADR-2 | API-first (Nuxt SPA/PWA + Laravel API) | Mobile-first, przyszłe klienty, czysty rozdział |
| ADR-3 | `tenant_id` od startu | Tanie przygotowanie pod SaaS multi-tenant |
| ADR-4 | Kolejki Redis dla PDF/mail/OCR | Responsywność UI, KPI 60 s nie blokowany przez I/O |
| ADR-5 | Audit log jako osobny moduł `activities` | Wymóg RODO + biznesowy „kto co zrobił" |
| ADR-6 | Soft delete + retencja danych | RODO „prawo do bycia zapomnianym" i wymogi prawne |
| ADR-7 | Sanctum (token) zamiast OAuth na start | Prostsze, wystarczające dla SPA/PWA jednej organizacji |
| ADR-8 | Dokumenty: S3 z presigned URLs, nigdy publiczne | Bezpieczeństwo danych wrażliwych (dowody, paszporty) |

---

## 5. Model domenowy

### 5.1 Encje główne

- **Tenant** — organizacja (na przyszłość multi-tenant; teraz jeden rekord).
- **User** — użytkownik systemu (rola: `admin`, `recruiter`).
- **Company** — firma-klient agencji.
- **JobPosting** — ogłoszenie/oferta pracy przypisana do firmy.
- **Candidate** — kandydat (kierowca) z danymi sterownika (kategorie, ADR, Kod95, karta kierowcy).
- **Document** — plik w S3 powiązany z kandydatem (CV, dowód, paszport, prawo jazdy, karta kierowcy, ADR, Kod95, zdjęcia).
- **ContactLog** — wpis historii kontaktu (telefon/WhatsApp/SMS/email) — **moduł krytyczny**.
- **Task** — zadanie follow-up (często auto-tworzone z ContactLog).
- **Application** — udział kandydata w pipeline danego ogłoszenia (encja łącząca Candidate ↔ JobPosting + etap kanban).
- **PipelineStage** — definicja etapów kanban (konfigurowalna).
- **CandidateProfile / ProfileSend** — wygenerowany profil PDF i log wysyłki do klienta.
- **Activity** — wpis audit logu (polimorficzny, pełna historia zmian).

### 5.2 Słowniki / enumy (przykładowe wartości)

- `User.role`: `admin`, `recruiter`
- `Candidate.status`: `new`, `active`, `placed`, `unavailable`, `blacklisted`, `archived`
- `Candidate.license_categories` (wiele): `B`, `C`, `C+E`, `D`, `D+E`, ...
- `Document.type`: `cv`, `id_card`, `passport`, `driving_license`, `driver_card`, `adr`, `code_95`, `photo`, `other`
- `ContactLog.channel`: `phone`, `whatsapp`, `sms`, `email`
- `ContactLog.outcome`: `interested`, `not_interested`, `no_answer`, `callback`, `wrong_number`, `hired_elsewhere`, `documents_requested`, ...
- `Task.status`: `open`, `done`, `cancelled`
- `Task.type`: `follow_up`, `document_collect`, `interview`, `custom`
- `ProfileSend.status`: `queued`, `sent`, `failed`, `viewed`

### 5.3 Reguła domenowa: ContactLog → Task

> Jeżeli przy zapisie `ContactLog` ustawiono `next_contact_at`, system **automatycznie**
> tworzy `Task` typu `follow_up` z `due_at = next_contact_at`, przypisany do tego samego
> użytkownika i kandydata. To realizacja wymogu „nie gubimy kandydatów".

### 5.4 Reguła domenowa: zdjęcie profilowe z dokumentu (Cropper)

> Po dodaniu zdjęcia dokumentu użytkownik może wyciąć fragment (CropperJS) jako zdjęcie
> profilowe kandydata. Oryginalny dokument pozostaje w S3; wycięte zdjęcie zapisywane jest
> jako osobny `Document` typu `photo` i ustawiane jako `Candidate.profile_photo_id`.

---

## 6. ERD — schemat bazy danych

> Notacja uproszczona. PK = primary key, FK = foreign key. Wszystkie tabele główne mają
> `id (uuid)`, `tenant_id`, `created_at`, `updated_at`, większość `deleted_at` (soft delete).

```
tenants
  id (PK, uuid)
  name
  settings (jsonb)

users
  id (PK, uuid)
  tenant_id (FK → tenants)
  name, email (unique per tenant), password
  role (enum: admin, recruiter)
  phone, avatar_path
  last_login_at

companies
  id (PK, uuid)
  tenant_id (FK)
  name, nip, address, city, country
  contact_person, contact_email, contact_phone
  notes
  status (active/inactive)

job_postings
  id (PK, uuid)
  tenant_id (FK)
  company_id (FK → companies)
  title, description
  required_categories (jsonb: ["C+E","ADR"])
  location, salary_range
  status (open/closed/paused)
  external_ref (na przyszłość: id z portalu pracy)

candidates
  id (PK, uuid)
  tenant_id (FK)
  first_name, last_name (nullable)
  phone (indexed, klucz deduplikacji)
  phone_normalized (E.164, unique index per tenant)
  email (nullable)
  city, country
  status (enum)
  license_categories (jsonb)
  has_adr (bool), adr_expiry (date)
  has_code_95 (bool), code_95_expiry (date)
  driver_card_expiry (date)
  profile_photo_id (FK → documents, nullable)
  source (np. "phone", "portal", "referral")
  consent_rodo_at (timestamp, nullable)   ← zgoda RODO
  internal_notes (text)                    ← NIE trafia do PDF dla klienta
  created_by (FK → users)

documents
  id (PK, uuid)
  tenant_id (FK)
  candidate_id (FK → candidates)
  type (enum)
  disk, path (S3 key), original_name, mime, size
  is_profile_photo (bool)
  uploaded_by (FK → users)

contact_logs
  id (PK, uuid)
  tenant_id (FK)
  candidate_id (FK → candidates)
  user_id (FK → users)
  channel (enum: phone/whatsapp/sms/email)
  outcome (enum)
  note (text)
  contacted_at (timestamp)
  next_contact_at (timestamp, nullable)    ← jeśli ustawione → auto Task
  task_id (FK → tasks, nullable)           ← powiązanie z utworzonym follow-up

tasks
  id (PK, uuid)
  tenant_id (FK)
  candidate_id (FK → candidates, nullable)
  assigned_to (FK → users)
  type (enum), status (enum)
  title, description
  due_at (timestamp)
  completed_at (timestamp, nullable)
  created_by (FK → users)

pipeline_stages
  id (PK, uuid)
  tenant_id (FK)
  name, color, position (int)
  is_terminal (bool)   ← np. "Zatrudniony", "Odrzucony"

applications                ← Candidate w pipeline danego ogłoszenia
  id (PK, uuid)
  tenant_id (FK)
  candidate_id (FK → candidates)
  job_posting_id (FK → job_postings)
  stage_id (FK → pipeline_stages)
  position (int)            ← kolejność w kolumnie kanban
  notes
  UNIQUE(candidate_id, job_posting_id)

profile_sends              ← wysyłka profilu PDF do klienta
  id (PK, uuid)
  tenant_id (FK)
  candidate_id (FK → candidates)
  company_id (FK → companies, nullable)
  job_posting_id (FK → job_postings, nullable)
  pdf_path (S3 key)
  recipient_email
  status (enum: queued/sent/failed/viewed)
  sent_by (FK → users)
  sent_at, viewed_at

activities                 ← audit log (polimorficzny)
  id (PK, uuid)
  tenant_id (FK)
  user_id (FK → users, nullable)
  subject_type, subject_id (morph: dowolna encja)
  event (created/updated/deleted/sent/viewed/...)
  changes (jsonb: before/after)
  ip, user_agent
  created_at
```

### 6.1 Relacje (skrót)

```
tenants 1───* users, companies, candidates, ...
companies 1───* job_postings
companies 1───* profile_sends
job_postings 1───* applications
candidates 1───* documents
candidates 1───* contact_logs
candidates 1───* tasks
candidates 1───* applications
candidates 1───* profile_sends
candidates  1───1 documents (profile_photo)
users 1───* contact_logs, tasks (assigned), activities
pipeline_stages 1───* applications
contact_logs 1───0..1 tasks (auto follow-up)
activities *───1 (morph) any subject
```

### 6.2 Indeksy krytyczne

- `candidates.phone_normalized` — unikalny (per tenant), deduplikacja w czasie rzeczywistym.
- `candidates(tenant_id, status)` — listy/filtrowanie.
- `tasks(assigned_to, status, due_at)` — widok „moje zadania na dziś".
- `contact_logs(candidate_id, contacted_at desc)` — historia kontaktów.
- `applications(job_posting_id, stage_id, position)` — render kanban.
- Pełnotekstowy indeks (Postgres `tsvector` / `pg_trgm`) na `candidates(first_name, last_name, phone, city)` — szybkie wyszukiwanie.

---

## 7. Architektura backendu (Laravel 11)

### 7.1 Warstwy

```
HTTP Request
  → Route (routes/api.php, wersjonowane /api/v1)
  → Middleware (auth:sanctum, tenant scope, throttle)
  → FormRequest (walidacja + autoryzacja wstępna)
  → Controller (cienki, deleguje)
  → Action / Service (logika domenowa, jedna odpowiedzialność)
  → Model (Eloquent) + Policy (autoryzacja)
  → Resource (serializacja JSON)
HTTP Response
```

### 7.2 Wzorce

- **Single-purpose Actions** (np. `CreateCandidateAction`, `LogContactAction`, `GenerateProfilePdfAction`) — testowalne, reużywalne z kontrolera i z kolejki.
- **Form Requests** — cała walidacja w jednym miejscu (krytyczne dla szybkiego quick-add: minimalne wymagane pola).
- **Policies** — autoryzacja per-rola i per-tenant.
- **Observers / Events** — `ContactLogCreated` → listener tworzy Task; każda zmiana encji → `Activity`.
- **Jobs (Queue)** — `GenerateProfilePdfJob`, `SendProfileEmailJob`, (future) `RunOcrJob`, `MatchCandidatesJob`.
- **Global Scope** — `TenantScope` automatycznie filtruje po `tenant_id`.
- **Filesystem** — dysk `s3` (konfiguracja zgodna z MEGA S3 / dowolnym S3-compatible), presigned URLs do pobierania, brak publicznego dostępu.

### 7.3 Generowanie PDF (HTML → PDF)

- Szablon Blade `profile.blade.php` renderowany do HTML, konwertowany do PDF.
- **Decyzja (zatwierdzona): Gotenberg** (kontener, Chromium headless) jako usługa w docker-compose — najwyższa jakość „premium" i pełne wsparcie CSS. Backend renderuje Blade → HTML, wysyła do Gotenberg, odbiera PDF, zapisuje w S3.
- PDF **nie zawiera** `internal_notes` ani innych pól oznaczonych jako wewnętrzne.

---

## 8. Architektura frontendu (Nuxt 3)

### 8.1 Zasady

- **PWA, installable, offline-aware** (Workbox / `@vite-pwa/nuxt`). Cache shell + ostatnie dane; kolejka zapisów offline dla quick-add (na przyszłość — patrz ryzyka).
- **Decyzja (zatwierdzona): SPA (`ssr: false`)** dla aplikacji za loginem — prostsze PWA, brak SSR dla danych wrażliwych, cały interfejs renderowany po stronie klienta po autoryzacji.
- **Pinia** — stan globalny (auth, bieżący użytkownik, draft quick-add).
- **Vue Query (TanStack Query)** — cache i synchronizacja danych serwerowych, optymistyczne aktualizacje (kanban drag&drop, quick-add).
- **TailwindCSS** — system wizualny mobile-first.
- **TypeScript** — typy generowane/utrzymywane zgodnie z kontraktem API.

### 8.2 Struktura nawigacji (mobile)

- **Dolny pasek nawigacji (bottom tab bar)** — 4–5 ikon: `Dziś` (zadania), `Kandydaci`, `Pipeline`, `Firmy`, `Więcej`.
- **FAB „Nowy Kandydat"** — pływający przycisk obecny na głównych ekranach.
- **Stos ekranów** kandydata: szczegóły → zakładki (Info / Dokumenty / Kontakty / Zadania).

---

## 9. API REST

> Bazowy prefix: `/api/v1`. Format: JSON. Autoryzacja: `Authorization: Bearer <token>` (Sanctum).
> Paginacja: cursor/limit. Filtrowanie: query params. Wszystkie odpowiedzi przez API Resources.

### 9.1 Najważniejsze endpointy (MVP)

```
POST   /auth/login                      logowanie (token)
POST   /auth/logout
GET    /auth/me

GET    /candidates                      lista + wyszukiwanie/filtry
POST   /candidates                      quick-add (minimalne pola)  ← KPI 60s
GET    /candidates/{id}
PATCH  /candidates/{id}                 progresywne wzbogacanie
DELETE /candidates/{id}                 soft delete
GET    /candidates/lookup?phone=...     deduplikacja w locie  ← KPI

POST   /candidates/{id}/contacts        zapis kontaktu (auto-task jeśli next_contact_at)
GET    /candidates/{id}/contacts

POST   /candidates/{id}/documents       upload do S3 (multipart / presigned)
GET    /candidates/{id}/documents
POST   /candidates/{id}/profile-photo   zapis wyciętego zdjęcia (Cropper)

GET    /tasks?assigned_to=me&due=today  „Dziś"
POST   /tasks
PATCH  /tasks/{id}                      done/cancel/reschedule

GET    /companies, POST, GET/{id}, PATCH, DELETE
GET    /job-postings, POST, ...

GET    /pipeline?job_posting_id=...     kolumny + karty (kanban)
POST   /applications                    dodaj kandydata do ogłoszenia
PATCH  /applications/{id}               zmiana etapu/pozycji (drag&drop)

POST   /candidates/{id}/profile-pdf     generuj PDF (kolejka) → zwraca job/status
POST   /candidates/{id}/profile-send    wyślij profil do klienta (email)

GET    /activities?subject=candidate&id=...   audit log
```

### 9.2 Kontrakt quick-add (przykład)

```jsonc
// POST /api/v1/candidates  (minimalny payload pod KPI 60s)
{
  "phone": "+48 600 000 000",        // wymagane
  "first_name": "Jan",               // wymagane
  "last_name": null,                 // opcjonalne
  "license_categories": ["C+E"],     // chipy
  "contact": {                       // opcjonalne — od razu loguje kontakt
    "channel": "phone",
    "outcome": "interested",
    "note": "Szuka tras międzynarodowych",
    "next_contact_at": "2026-06-06T10:00:00+02:00"  // → auto-task
  }
}
```

---

## 10. UX mobilny

### 10.1 Pryncypia

- **Jeden kciuk, jeden cel.** Najważniejsze akcje w zasięgu kciuka (dolna część ekranu).
- **Duże cele dotykowe** — min. 44×44 px.
- **Mało kliknięć** — quick-add bez przewijania; chipy zamiast dropdownów.
- **Dolna nawigacja** zamiast górnego menu.
- **FAB „Nowy Kandydat"** — zawsze dostępny na głównych ekranach.
- **Optymistyczne UI** — zapis wydaje się natychmiastowy, synchronizacja w tle.

### 10.2 Ekran krytyczny: Quick-Add (szkic)

```
┌───────────────────────────┐
│  ✕            Nowy kandydat│
├───────────────────────────┤
│  📞 Telefon                │
│  [ +48 ___ ___ ___      ]  │  ← autofocus, klawiatura numeryczna
│  ⚠ Sprawdzanie duplikatu… │  ← lookup w locie
│                            │
│  👤 Imię                   │
│  [ Jan                  ]  │
│                            │
│  🚛 Kategorie              │
│  [B] [C] (C+E) [D] [ADR]   │  ← chipy toggle, jeden tap
│  [Kod95] [Karta kierowcy]  │
│                            │
│  📝 Notatka                │
│  [ ____________________ ]  │  🎤 (dyktowanie)
│                            │
│  Wynik kontaktu            │
│  (Zainteresowany) [Nie] …  │  ← chipy
│  ⏰ Oddzwonić: [ jutro 10 ]│  ← szybkie presety
│                            │
│  ┌──────────────────────┐  │
│  │      ZAPISZ           │  │  ← duży, pełna szerokość
│  └──────────────────────┘  │
└───────────────────────────┘
```

### 10.3 Ekran „Dziś" (start aplikacji)

Lista zadań follow-up na dziś (z call log), posortowana po godzinie. Każdy element: imię,
telefon (tap → dzwoń), kategoria, notatka, akcje „Zadzwoniłem / Przełóż / Gotowe".

### 10.4 Pipeline (kanban)

Kolumny etapów, karty kandydatów, drag&drop (optymistyczny PATCH). Na telefonie: przewijanie
poziome kolumn + długie przytrzymanie do przeniesienia, lub bottom-sheet „zmień etap".

---

## 11. System wizualny / Design System

> Mobile-first, czytelny w jasnym otoczeniu (praca w terenie), wysoki kontrast, duże cele.
> Z istniejącego `DESIGN.md` (Mintlify) adaptujemy jedynie **dyscyplinę tokenów**, nie estetykę.

- **Typografia:** Inter (UI). Rozmiary bazowe ≥ 16 px dla pól (uniknięcie zoomu na iOS).
- **Kolor:** jeden zdecydowany akcent (do ustalenia z marką agencji), wysoki kontrast tekstu.
- **Skala odstępów:** 4 px baza (4/8/12/16/24/32).
- **Promienie:** spójna skala (np. 8 px karty, full dla chipów/FAB).
- **Komponenty bazowe:** Button (lg/full-width), Chip (toggle), Input (44px+), BottomNav, FAB, BottomSheet, Card, Avatar, Badge (status/dokumenty), EmptyState.
- **Stany:** loading (skeleton), empty, error — zawsze zaprojektowane.

Pełna specyfikacja tokenów powstanie w osobnej sekcji po zatwierdzeniu kierunku wizualnego.

---

## 12. Bezpieczeństwo i RODO

> **To jest obszar krytyczny.** System przechowuje dane szczególnie wrażliwe: skany
> dowodów osobistych, paszportów, praw jazdy, kart kierowcy. Błąd tutaj to ryzyko prawne
> i wizerunkowe poważniejsze niż jakakolwiek funkcja.

### 12.1 Zasady

- **Dokumenty nigdy publiczne** — S3 prywatne, dostęp wyłącznie przez presigned URL z krótkim TTL, autoryzowany per użytkownik/tenant.
- **Szyfrowanie**: TLS w tranzycie; szyfrowanie at-rest po stronie storage (S3 SSE); wrażliwe pola rozważyć do szyfrowania na poziomie aplikacji.
- **Autoryzacja**: Policies per rola i per tenant; zasada najmniejszych uprawnień.
- **Audit log** (`activities`) — kto, kiedy, co (w tym dostęp do dokumentów i wysyłki profili).
- **Zgoda RODO** — `candidates.consent_rodo_at`; bez zgody ograniczenia przetwarzania.
- **Retencja i „prawo do bycia zapomnianym"** — soft delete + proces twardego usunięcia danych i plików S3 po okresie retencji / na żądanie.
- **Minimalizacja w PDF** — profil dla klienta nie zawiera notatek wewnętrznych ani nadmiarowych danych osobowych.
- **Rate limiting / throttling** na endpointach auth i lookup.
- **Backupy** bazy i metadanych; plan odtwarzania.

### 12.2 Rejestr czynności przetwarzania

Audit log + dokumentacja kategorii danych i podstaw prawnych przetwarzania (do uzupełnienia z działem prawnym agencji).

---

## 13. Struktura katalogów

> Monorepo: backend (Laravel) + frontend (Nuxt) + infrastruktura.

```
rekruter/
├── design.md                      ← źródło prawdy (ten plik)
├── README.md
├── docker-compose.yml
├── docker-compose.prod.yml
├── .env.example
├── docs/                          ← ADR, diagramy, instrukcje
│   ├── adr/
│   └── erd.png
│
├── backend/                       ← Laravel 11 API
│   ├── app/
│   │   ├── Models/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/
│   │   │   ├── Requests/
│   │   │   ├── Resources/
│   │   │   └── Middleware/
│   │   ├── Actions/               ← logika domenowa (single-purpose)
│   │   │   ├── Candidates/
│   │   │   ├── Contacts/
│   │   │   ├── Pipeline/
│   │   │   └── Profiles/
│   │   ├── Policies/
│   │   ├── Jobs/                  ← PDF, mail, (future) OCR/AI
│   │   ├── Observers/
│   │   ├── Events/ Listeners/
│   │   └── Support/Tenancy/       ← TenantScope, helpers
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/api.php
│   ├── resources/views/pdf/       ← szablony profilu (Blade → PDF)
│   └── tests/ (Feature, Unit)
│
├── frontend/                      ← Nuxt 3 PWA
│   ├── app.vue
│   ├── nuxt.config.ts
│   ├── pages/
│   │   ├── index.vue              ← „Dziś"
│   │   ├── candidates/
│   │   ├── pipeline/
│   │   ├── companies/
│   │   └── job-postings/
│   ├── components/
│   │   ├── ui/                    ← Button, Chip, BottomNav, FAB, BottomSheet…
│   │   ├── candidate/
│   │   ├── contact/
│   │   └── pipeline/
│   ├── composables/               ← useCandidates, useTasks, useAuth (Vue Query)
│   ├── stores/                    ← Pinia
│   ├── layouts/
│   ├── middleware/                ← auth
│   ├── types/                     ← kontrakty API (TS)
│   └── assets/
│
└── infra/                         ← konfiguracje (nginx, gotenberg, horizon)
```

---

## 14. Deployment

- **Docker Compose**, usługi: `app` (Laravel + php-fpm), `nginx`, `frontend` (Nuxt — build statyczny/serwer Node), `postgres`, `redis`, `horizon` (worker), `gotenberg` (PDF — jeśli wybrane), `mailpit` (dev SMTP).
- **Storage**: S3-compatible (docelowo MEGA S3) — konfiguracja przez `.env`.
- **Środowiska**: `dev` (compose + Mailpit + MinIO jako lokalny S3), `prod` (compose prod + realny S3/MEGA + realny SMTP).
- **Migracje** uruchamiane przy starcie kontenera app (kontrolowane).
- **Healthchecki** i restart policy dla usług.
- **Sekrety** wyłącznie przez zmienne środowiskowe / menedżer sekretów; nigdy w repo.

---

## 15. Rozszerzalność na przyszłość

Architektura przygotowana (bez przedwczesnej implementacji) pod:

| Funkcja | Zaczep w architekturze |
|---|---|
| OCR dokumentów | `Document` + `RunOcrJob` (kolejka); pola wynikowe w jsonb |
| AI analiza CV | Action/Job na `Document(cv)`; wynik jako encja powiązana |
| AI dopasowanie kandydatów | `applications` + serwis scoringowy; `required_categories` vs profil |
| WhatsApp Business API | `ContactLog.channel=whatsapp` już istnieje; adapter integracji |
| SMS Gateway | jw. `channel=sms`; abstrakcja `NotificationChannel` |
| Integracje z portalami pracy | `job_postings.external_ref`; moduł importu/eksportu |
| Auto-publikacja ogłoszeń | Job + adaptery per portal |
| Workflow Automation | Events/Listeners + reguły (silnik reguł w przyszłości) |
| Multi-language | i18n od startu w Nuxt; teksty wydzielone |
| Multi-tenant SaaS | `tenant_id` + `TenantScope` już w modelu |

---

## 16. Ryzyka i mitygacje

| # | Ryzyko | Wpływ | Mitygacja |
|---|---|---|---|
| R1 | **RODO / dane wrażliwe** (dowody, paszporty) | Krytyczny (prawny) | Sekcja 12: S3 prywatne, presigned, audit, zgody, retencja, minimalizacja w PDF |
| R2 | Duplikaty kandydatów przy telefonie | Wysoki (jakość danych) | `phone_normalized` unique + lookup w locie przed zapisem |
| R3 | KPI 60s nieosiągnięty przez ciężki formularz | Wysoki (cel produktu) | Quick-add minimalny, chipy, optymistyczne UI, kolejki dla ciężkich operacji |
| R4 | Jakość PDF („premium") słaba przy dompdf | Średni | Gotenberg/Browsershot zamiast dompdf (decyzja sekcja 18) |
| R5 | Kompatybilność MEGA S3 z API S3 | Średni | Warstwa Filesystem S3; test na MinIO; weryfikacja MEGA S3 przed prod |
| R6 | Offline na telefonie (słaby zasięg w terenie) | Średni | PWA + kolejka zapisów offline (faza 2); na MVP wyraźny stan online/offline |
| R7 | Bezpieczeństwo plików (malware w uploadzie) | Średni | Walidacja typu/rozmiaru, skan AV w kolejce (faza 2), brak wykonywania |
| R8 | Przeskalowanie modelu (multi-tenant później) | Niski | `tenant_id` od startu eliminuje migrację rozrywającą |
| R9 | Rozjazd kontraktu API ↔ frontend | Średni | Typy TS z kontraktu, testy Feature na API, wersjonowanie `/v1` |
| R10 | Złożoność kanban na małym ekranie | Średni | Bottom-sheet „zmień etap" jako alternatywa dla drag&drop |

---

## 17. Roadmapa wdrożeniowa

> Po zatwierdzeniu tego dokumentu. Kolejność: szkielet → moduł krytyczny → reszta.

- **Faza 0 — Fundament** ✅ **ZREALIZOWANA** (2026-06-05): docker-compose (postgres, redis, minio, mailpit, gotenberg, app, nginx, queue, frontend), Laravel 11 + Sanctum, model Tenant + User (UUID), `TenantScope` + `BelongsToTenant`, middleware `IdentifyTenant`, endpointy `auth/login|me|logout`, testy funkcjonalne autoryzacji (6 zielonych), Nuxt 3 PWA (SPA, Tailwind, Pinia, Vue Query, dolna nawigacja, FAB, strona logowania), README.
- **Faza 1 — Rdzeń KPI (krytyczny)** ✅ **ZREALIZOWANA** (2026-06-05): model Candidate/Task/ContactLog (UUID, soft delete, jsonb, pg_trgm), normalizacja telefonu + partial unique index (deduplikacja), Akcje `CreateCandidateAction` i `LogContactAction` (kontakt → auto-task follow-up), endpointy `candidates` (CRUD + lookup), `candidates/{id}/contacts`, `tasks` (today/overdue/upcoming + update), 9 testów funkcjonalnych Fazy 1 (łącznie 15 zielonych). Frontend: ekran **Quick-Add** (chipy kategorii/ADR/Kod95, lookup duplikatu w locie, presety terminów, wynik kontaktu), lista kandydatów z wyszukiwaniem, szczegóły kandydata (kontakty + zadania), ekran „Dziś" (zadania follow-up z akcjami Gotowe/Jutro), composables Vue Query.
- **Faza 2 — Dokumenty + Profil**: upload S3, Cropper (zdjęcie profilowe), generator PDF (premium), wysyłka profilu (SMTP), audit log.
- **Faza 3 — Pipeline + Klienci**: Companies, JobPostings, Applications, kanban (drag&drop / bottom-sheet).
- **Faza 4 — Utwardzenie**: RODO (retencja, zgody, eksport/usunięcie danych), offline queue, testy E2E, hardening bezpieczeństwa, deployment prod.

Każda faza kończy się działającym, przetestowanym przyrostem. Po każdej fazie aktualizacja `design.md`.

---

## 18. Decyzje i otwarte pytania

### 18.1 Decyzje zatwierdzone (2026-06-05)

| # | Temat | Decyzja |
|---|---|---|
| D1 | `DESIGN.md` (Mintlify) | Przeznaczony na główny dokument projektowy — treść zastąpiona |
| D2 | Renderer PDF | **Gotenberg** (Chromium headless, kontener) |
| D3 | Nuxt SSR vs SPA | **SPA** (`ssr: false`) |
| D4 | Fazowanie wdrożenia | Zaakceptowane — start **Fazy 0** |

### 18.2 Otwarte pytania (do uzupełnienia w kolejnych fazach)

4. **Branding / kolor akcentu** — czy jest księga znaku agencji (logo, kolory)?
5. **Multi-user teraz?** — ilu rekruterów na start (wpływa na uprawnienia/UX zadań)?
6. **MEGA S3** — dostęp testowy teraz, czy MVP na MinIO, MEGA później? (na dev: MinIO)
7. **Język UI** — tylko PL teraz, czy od razu i18n (PL + np. UA/RU dla kandydatów-kierowców)?

---

> **Status:** Faza 0 (fundament) w realizacji — docker-compose + szkielet Laravel 11 (API,
> Sanctum, tenant scope) + Nuxt 3 PWA (SPA). Aktualizacje postępu w sekcji 17.
