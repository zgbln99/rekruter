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
19. [Faza 5 — Ogłoszenia jako centrum systemu](#19-faza-5--ogłoszenia-jako-centrum-systemu)
20. [Faza 6 — Pełna kartoteka kandydata + użytkownicy](#20-faza-6--pełna-kartoteka-kandydata--użytkownicy)
21. [Faza 7 — Skierowania, kalendarz przyjazdów i rozliczenia](#21-faza-7--skierowania-kalendarz-przyjazdów-i-rozliczenia)
22. [Faza 8 — Grafiki ogłoszeń (AI generuje tło, backend nakłada tekst)](#22-faza-8--grafiki-ogłoszeń-ai-generuje-tło-backend-nakłada-tekst)

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
> Język wizualny oparty na `docs/design-system.md` (specyfikacja Mintlify): Inter,
> mięta jako akcent, czarne (ink) pill-buttony jako akcja dominująca, czyste karty
> z hairline, dyscyplina promieni, ikony liniowe (Heroicons) zamiast emoji.

**Konkretne tokeny (zaimplementowane w `frontend/tailwind.config.ts`):**
- **Typografia:** Inter (400/500/600/700), wczytywana z Google Fonts. Pola ≥ 15 px (brak zoomu iOS).
- **Akcent (mięta):** `brand` `#10b981`, `brand-deep` `#059669`, `brand-soft` `#ecfdf5` — wyłącznie akcenty, stany aktywne, potwierdzenia.
- **Akcja dominująca (ink):** `#18181b` — pill-buttony, FAB, zapis, zaznaczone chipy.
- **Tekst:** ink `#18181b` (nagłówki) · charcoal/slate/steel/stone/muted (hierarchia szarości).
- **Powierzchnie:** canvas `#ffffff`, surface `#f4f4f5`, surface-soft `#fafafa`, hairline `#e4e4e7`.
- **Promienie:** md 8 · lg 12 · xl 16 · 2xl 20 · full (pill).
- **Cienie:** subtle / card / elevated / fab — płaskość ze strategiczną elewacją.
- **Komponenty bazowe** (`assets/css/main.css`): `.btn-primary` (ink pill), `.btn-accent` (mięta), `.btn-outline`, `.btn-sm`, `.card`, `.input-field`, `.chip`/`.chip-active`, `.badge` + warianty; `AppIcon` (ikony liniowe), `BottomNav`, `NewCandidateFab`, `CropperModal`, bottom-sheet.
- **Stany:** loading, empty (z ikoną + opisem), error — zaprojektowane na każdym ekranie.

> Kolor akcentu (`brand`) można dopasować do księgi znaku agencji w jednym miejscu
> (`tailwind.config.ts`) — reszta UI dziedziczy zmianę.

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
- **Faza 2 — Dokumenty + Profil** ✅ **ZREALIZOWANA** (2026-06-05): upload dokumentów do prywatnego S3 (Flysystem), enum `DocumentType` (CV/dowód/paszport/prawo jazdy/karta kierowcy/ADR/Kod95/zdjęcie), zdjęcie profilowe z Cropper (`SaveProfilePhotoAction`), generator PDF premium przez **Gotenberg** (`GotenbergClient` + szablon Blade, bez notatek wewnętrznych), wysyłka profilu mailem w kolejce (`SendProfileEmailJob` + `ProfileMail`), **audit log** (`activities` + trait `RecordsActivity`), pobieranie dokumentów przez uwierzytelniony/audytowany endpoint. 10 nowych testów (łącznie 25 zielonych). Frontend: zdjęcie profilowe z CropperJS, sekcja dokumentów (upload/pobieranie), „Generuj PDF" i „Wyślij profil".
- **Faza 3 — Pipeline + Klienci** ✅ **ZREALIZOWANA** (2026-06-05): modele Company / JobPosting / PipelineStage / Application (UUID, audit), domyślne etapy pipeline (`EnsurePipelineStagesAction`, 6 etapów z terminalnymi), Akcje `AddCandidateToPipelineAction` (deduplikacja w ogłoszeniu) i `MoveApplicationAction` (zmiana etapu + audit „moved"), endpointy `companies` i `job-postings` (CRUD), `pipeline-stages`, `job-postings/{id}/pipeline` (tablica kanban), `applications` (dodanie/przeniesienie/usunięcie). 7 nowych testów (łącznie 32 zielone). Frontend: lista i szczegóły firm z dodawaniem ogłoszeń, lista pipeline, **tablica kanban** z poziomym przewijaniem kolumn i **bottom-sheet zmiany etapu** (mitygacja R10), przypisanie kandydata do ogłoszenia z ekranu kandydata.
- **Faza 4 — Utwardzenie** ✅ **ZREALIZOWANA** (2026-06-05): RODO — eksport danych kandydata (`ExportCandidateAction`, art. 15), zarządzanie zgodą (`consent`), trwałe usunięcie z czyszczeniem plików S3 i audit logu (`ForgetCandidateAction`, art. 17, admin-only); autoryzacja per rola (Policies: forget/usuwanie firm i ogłoszeń tylko admin); throttling (login, lookup); middleware `SecurityHeaders`; **offline queue** w PWA (kolejkowanie Quick-Add bez sieci + auto-synchronizacja po powrocie online, baner trybu offline); `docker-compose.prod.yml` (realny S3/SMTP, współdzielony wolumen kodu, restart policy). 8 nowych testów (łącznie 39 zielonych).
- **Faza 5 — Ogłoszenia jako centrum systemu** 🔜 **W TRAKCIE** (specyfikacja: sekcja 19): pełny moduł ogłoszeń (job offers) z opisem publicznym, notatką rekruterki i skryptem rozmowy; szybkie tworzenie kandydata z ogłoszenia; status kandydata w ramach ogłoszenia (pivot `applications` + enum); dopasowanie kandydat↔ogłoszenie; dysk `mega_s3`; rozbudowa dokumentów/zdjęcia/croppera/aparatu; checklista kompletności i braki; historia wysyłki + decyzja firmy; timeline kandydata; PDF ze zdjęciem.

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

> **Status:** Fazy 0–4 zrealizowane (MVP). Faza 5 (sekcja 19) rozbudowuje system wokół
> **ogłoszeń jako centrum** — pełny moduł ofert, szybkie tworzenie kandydata z ogłoszenia,
> dopasowanie, MEGA S3, dokumenty/zdjęcie/cropper/aparat, timeline, PDF ze zdjęciem.

---

## 19. Faza 5 — Ogłoszenia jako centrum systemu

> Cel biznesowy: ogłoszenie (oferta pracy klienta) staje się punktem wyjścia całej pracy
> rekruterki. Kierowca dzwoni „po ogłoszeniu", więc rekruterka pracuje **na ogłoszeniu**:
> widzi wymagania, skrypt rozmowy, gotowy opis do skopiowania, i jednym tapnięciem tworzy
> kandydata przypisanego do tej oferty. Priorytet: funkcje biznesowe, nie wygląd.

### 19.1 Decyzje architektoniczne

| # | Decyzja | Uzasadnienie |
|---|---|---|
| ADR-9 | „Ogłoszenie / job offer" = istniejący model **`JobPosting`** mocno rozszerzony (nie nowa tabela) | Brak duplikacji; pipeline/aplikacje już się o niego opierają |
| ADR-10 | Endpointy `/api/v1/job-offers` jako podstawowe; `/api/v1/job-postings` zachowane jako alias (te same kontrolery) | Spójność z wymaganiem biznesowym bez łamania istniejącego frontu/testów |
| ADR-11 | **`candidate_job_offer` = tabela `applications`** rozszerzona o kolumnę `status` (enum biznesowy) | Pivot kandydat↔ogłoszenie już istnieje; dodajemy status per ogłoszenie zamiast jednego globalnego |
| ADR-12 | Kanban (board) grupuje po `applications.status` (enum), `pipeline_stages` **deprecated** (tabela zostaje dla zgodności, `stage_id` nullable) | Zadanie definiuje stały zestaw statusów biznesowych; eliminujemy podwójne źródło prawdy |
| ADR-13 | Dysk **`mega_s3`** (S3-compatible) jako domyślny storage dokumentów w prod; lokalny tylko w dev | Wymóg: dokumenty wyłącznie w MEGA S3 poza developmentem |
| ADR-14 | Timeline kandydata budowany z istniejącego **audit logu (`activities`)** agregowanego po kandydacie i encjach powiązanych | Zero nowej tabeli; audit już rejestruje zdarzenia |
| ADR-15 | Dopasowanie i kompletność profilu liczone **w locie** (Action), bez materializacji | Reguły będą się zmieniać; brak ryzyka rozjazdu danych |

### 19.2 Rozszerzenie modelu `job_postings` (ogłoszenie)

Nowe kolumny (migracja `add_offer_fields_to_job_postings`):

```
driver_type        string  null   -- typ kierowcy (np. solo, team, wywrotka)
trailer_type       string  null   -- typ naczepy (plandeka, chłodnia, cysterna, ...)
country            string  null   -- kraj pracy
region_base        string  null   -- region / baza
work_system        string  null   -- system pracy (np. 2/1, 3/1, 4/2)
salary_amount      string  null   -- wynagrodzenie (kwota/zakres)
currency           string  null   -- waluta (PLN, EUR, ...)
start_date         date    null   -- data startu
required_language  string  null   -- wymagany język (opis)
required_experience string null   -- wymagane doświadczenie (opis)
public_description text    null   -- GOTOWY opis do kopiowania (FB/OLX/Jooble)
recruiter_notes    text    null   -- notatka wewnętrzna (NIE w PDF, NIE publiczna)
call_script        jsonb   '[]'   -- checklista pytań do rozmowy (lista stringów)
requirements       jsonb   '{}'   -- wymagania-checkboxy (mapa boolean, p. 19.3)
```

`status` (już istnieje): `open` (aktywne) / `paused` (wstrzymane) / `closed` (zamknięte).
`required_categories` (już istnieje) zachowane dla C / C+E.

### 19.3 Wymagania ogłoszenia ↔ atrybuty kandydata (dopasowanie)

Wymagania ogłoszenia w `requirements` (jsonb, klucze boolean):

```
c, ce, code_95, driver_card, adr, hds,
exp_reefer (chłodnia), exp_tilt (plandeka), exp_international,
lang_de (niemiecki), lang_en (angielski)
```

Aby dopasowanie miało sens, **rozszerzamy `candidates`** o odpowiadające atrybuty
(migracja `add_driver_attributes_to_candidates`):

```
has_hds           bool  default false
exp_reefer        bool  default false
exp_tilt          bool  default false
exp_international  bool  default false
lang_de           bool  default false
lang_en           bool  default false
nationality       string null
availability_from date   null
experience_notes  text   null
```

Mapowanie wymaganie → atrybut kandydata (dla `MatchCandidateToOfferAction`):

| Wymaganie | Spełnione gdy |
|---|---|
| c / ce | `'C'` / `'C+E'` w `license_categories` |
| code_95 | `has_code_95` |
| driver_card | `driver_card_expiry` ustawione |
| adr | `has_adr` |
| hds | `has_hds` |
| exp_reefer / exp_tilt / exp_international | odpowiednie pole bool |
| lang_de / lang_en | `lang_de` / `lang_en` |

**Wynik dopasowania:** `match` (pasuje — wszystkie wymagane spełnione), `partial`
(częściowo — część spełniona), `no_match` (żadne wymagane lub brak danych). Zwracana lista
braków w czytelnej formie PL (np. „brak ADR", „brak doświadczenia na chłodni", „brak języka
niemieckiego"). Endpoint: `GET /candidates/{id}/match/{jobOfferId}`. Pokazywane też na
profilu kandydata i na widoku ogłoszenia (dla przypisanych kandydatów).

### 19.4 Status kandydata w ramach ogłoszenia (`applications.status`)

Enum `ApplicationStatus` (kolumna `status` na `applications`, domyślnie `new`):

```
new (nowy) · interested (zainteresowany) · missing_data (brakuje_danych) ·
ready_for_pdf (gotowy_do_pdf) · sent_to_company (wysłany_do_firmy) ·
accepted_by_company (zaakceptowany_przez_firmę) · rejected_by_company (odrzucony_przez_firmę) ·
hired (zatrudniony) · failed (nieudany)
```

`stage_id` staje się nullable (deprecated). Kanban (`GET /job-offers/{id}/pipeline`)
grupuje aplikacje po `status`. `MoveApplicationAction` zmienia `status` (+ wpis `moved`
w audit logu). `AddCandidateToPipelineAction` ustawia `status = new`.

### 19.5 Szybkie tworzenie kandydata z ogłoszenia

`POST /job-offers/{id}/create-candidate` — krok 1: tylko `first_name`, `last_name`,
`phone`. Po zapisie kandydat jest automatycznie tworzony (z deduplikacją po numerze) i
**przypisywany do ogłoszenia i firmy** (rekord `applications` ze statusem `new`). Resztę
uzupełnia się później. Cel: < 60 s. Realizacja: `CreateCandidateFromOfferAction`
(reużywa `CreateCandidateAction` + `AddCandidateToPipelineAction`).

### 19.6 MEGA S3 — storage dokumentów

Nowy dysk `mega_s3` w `config/filesystems.php`, konfigurowany ENV:

```
S3_ENDPOINT, S3_ACCESS_KEY_ID, S3_SECRET_ACCESS_KEY, S3_BUCKET,
S3_REGION, S3_USE_PATH_STYLE_ENDPOINT=true
```

`DOCUMENTS_DISK` (ENV) wskazuje dysk dokumentów: `mega_s3` w prod, `local` w dev.
Akcje storage przestają hardkodować `'s3'` — używają `config('rekruter.documents_disk')`.
Pobieranie: najpierw **tymczasowy signed URL** (gdy dysk wspiera `temporaryUrl`), w razie
braku wsparcia **fallback do stream download** przez uwierzytelniony endpoint (jak dziś).

### 19.7 Dokumenty — mapowanie nazw

Zadanie wymienia kolumny `storage_disk`, `storage_path`, `original_filename`, `mime_type`.
W projekcie istnieją równoważne: `disk`, `path`, `original_name`, `mime` (+ `size`,
`uploaded_by`, `created_at`, `candidate_id`, `type`). **Zachowujemy istniejące nazwy**
(brak ryzykownej migracji), a w API Resource wystawiamy też aliasy zgodne z zadaniem.
Typy (`DocumentType`) bez zmian: cv, profile photo (`photo`), id_card, passport,
driving_license, driver_card, adr, code_95, other.

### 19.8 Zdjęcie profilowe + cropper + aparat

- `candidate.profile_photo_id` (już istnieje) = `profile_photo_document_id` z zadania.
- Endpointy: `POST /candidates/{id}/profile-photo` (upload/aparat), `POST .../profile-photo/from-crop` (wykadrowany blob z CropperJS), `DELETE .../profile-photo`.
- Front: input z `capture="user"` (twarz) i `capture="environment"` (dokumenty); CropperJS już zintegrowany — flow „Wytnij z dokumentu" → zapis jako zdjęcie profilowe. Walidacja typu (jpg/jpeg/png/webp) i rozmiaru.

### 19.9 Checklista kompletności i braki

`CandidateCompletenessAction` liczy w locie pozycje: dane podstawowe, telefon, przypisane
ogłoszenie, doświadczenie, uprawnienia, dokumenty, zdjęcie, gotowy PDF. Zwraca listę
spełnione/braki. „Braki do wysłania do firmy" (np. brak zdjęcia, brak uprawnień, brak
przypisanego ogłoszenia) pokazywane na profilu kandydata. Endpoint:
`GET /candidates/{id}/completeness`.

### 19.10 Historia wysyłki + decyzja firmy

`profile_sends` rozszerzone o `decision` (enum `CompanyDecision`: `pending` (oczekuje),
`accepted` (zaakceptowany), `rejected` (odrzucony), `hired` (zatrudniony)). Przy wysyłce
ustawiamy `job_posting_id` + `company_id` (z aplikacji). Endpoint ustawienia decyzji:
`PATCH /profile-sends/{id}/decision` — synchronizuje też `applications.status`
(accepted→accepted_by_company, rejected→rejected_by_company, hired→hired).
`send-profile` przyjmuje `job_offer_id` (wybór ogłoszenia/firmy docelowej).

### 19.11 Źródło kandydata

`candidate.source` (istnieje) z enumem `CandidateSource`: facebook, olx, jooble,
facebook_group, whatsapp, phone, referral, other. Walidacja + etykiety PL.

### 19.12 Timeline kandydata

`CandidateTimelineAction` agreguje wpisy `activities` dla kandydata **oraz** encji
powiązanych (documents, applications, profile_sends tego kandydata), sortuje malejąco i
mapuje na czytelne zdarzenia PL: utworzono kandydata, przypisano do ogłoszenia, dodano
dokument, ustawiono zdjęcie, wygenerowano PDF, wysłano profil, zmieniono status, dodano
notatkę. Dodajemy logowanie brakujących zdarzeń (przypisanie do ogłoszenia, ustawienie
zdjęcia, wygenerowanie PDF, zmiana statusu). Endpoint: `GET /candidates/{id}/timeline`.

### 19.13 PDF profilu (rozbudowa)

Szablon Blade rozszerzony o: zdjęcie (z `mega_s3`, placeholder gdy brak), imię i nazwisko,
narodowość, dostępność (`availability_from`), języki, uprawnienia, doświadczenie,
**ogłoszenie na które kandyduje + firma docelowa**, publiczne uwagi (`public_description`
ogłoszenia). **Bez** uwag wewnętrznych (`recruiter_notes`, `internal_notes`).
`POST /candidates/{id}/generate-pdf` (zapis do storage + zwrot), obok istniejącego
podglądu `GET .../profile-pdf`.

### 19.14 Endpointy (Faza 5)

```
# Ogłoszenia (job offers) — alias job-postings
GET    /api/v1/job-offers
POST   /api/v1/job-offers
GET    /api/v1/job-offers/{id}
PUT    /api/v1/job-offers/{id}
DELETE /api/v1/job-offers/{id}
POST   /api/v1/job-offers/{id}/create-candidate
GET    /api/v1/job-offers/{id}/pipeline

# Dopasowanie
GET    /api/v1/candidates/{id}/match/{jobOfferId}

# Kompletność / timeline
GET    /api/v1/candidates/{id}/completeness
GET    /api/v1/candidates/{id}/timeline

# Zdjęcie profilowe
POST   /api/v1/candidates/{id}/profile-photo
POST   /api/v1/candidates/{id}/profile-photo/from-crop
DELETE /api/v1/candidates/{id}/profile-photo

# PDF / wysyłka / decyzja firmy
POST   /api/v1/candidates/{id}/generate-pdf
POST   /api/v1/candidates/{id}/send-profile        (job_offer_id, recipient_email)
PATCH  /api/v1/profile-sends/{id}/decision
```

(Dokumenty kandydata bez zmian ścieżek; dodajemy aliasy nazewnictwa w Resource.)

### 19.15 Frontend (Faza 5)

Widoki: lista ogłoszeń, szczegóły ogłoszenia (wymagania, skrypt rozmowy jako czytelna
checklista, przycisk „Kopiuj opis", duży przycisk „Nowy kandydat z tego ogłoszenia"),
formularz szybkiego kandydata (krok 1: imię/nazwisko/telefon), profil kandydata (zdjęcie,
dopasowanie, kompletność/braki, status w ogłoszeniu, timeline), dokumenty + aparat +
cropper, lista/szczegóły firm. Mobile-first: dolna nawigacja (Ogłoszenia jako nowa
zakładka centralna), duże przyciski, formularze w krokach.

### 19.16 Testy (Faza 5)

Tworzenie ogłoszenia · tworzenie kandydata z ogłoszenia (auto-przypisanie) · upload
dokumentu na skonfigurowany dysk · ustawienie zdjęcia profilowego · upload wykadrowanego
zdjęcia (from-crop) · generowanie PDF · wysyłka PDF (+ decyzja firmy) · dopasowanie
kandydata do ogłoszenia (match/partial/no_match + braki). Cel: utrzymać 100% zielonych.

### 19.17 Responsywność — wersja desktopowa (korekta)

> **Korekta do sekcji 10/11:** „mobile-first" oznacza, że telefon jest punktem wyjścia
> projektowania — **nie** brak wersji desktopowej. Obecny UI był wyłącznie mobilny
> (wąski, wycentrowany `max-w-2xl`, tylko dolna nawigacja). Dodajemy responsywny layout
> tak, by aplikacja była w pełni użyteczna również na komputerze (desktop jako dodatek).

Strategia (breakpointy Tailwind):
- **< `lg` (telefon/tablet):** bez zmian — dolna nawigacja (`BottomNav`) + FAB „Nowy kandydat", kontener wąski.
- **≥ `lg` (desktop):** **stała boczna nawigacja** (`SideNav`, lewa kolumna ~240px) z tymi samymi pozycjami + akcja „Nowy kandydat"; dolna nawigacja i pływający FAB ukryte; główny kontener szerszy (np. `max-w-5xl`/`max-w-6xl`) wyśrodkowany obok sidebara; górny pasek (logo, użytkownik, wyloguj) pełnej szerokości.
- Listy/siatki (kandydaci, ogłoszenia, firmy, dokumenty) mogą na desktopie przechodzić w **2 kolumny**; tablica kanban zyskuje więcej przestrzeni (kolumny widoczne bez przewijania, gdy się mieszczą).
- Komponenty (karty, przyciski, pola) pozostają te same — zmienia się jedynie **chrome nawigacyjny** i szerokość/siatka kontenera, co utrzymuje jedno źródło prawdy dla widoków.

Realizacja: `layouts/default.vue` warunkowo renderuje `SideNav` (`hidden lg:flex`) oraz
`BottomNav`/`NewCandidateFab` (`lg:hidden`); kontener `max-w` i `padding` sterowane klasami
responsywnymi. Brak osobnych „desktopowych" stron — ten sam routing i komponenty.

---

## 20. Faza 6 — Pełna kartoteka kandydata + użytkownicy

> Cel: bogatszy profil kandydata (dane potrzebne klientowi w PDF), wygodny
> dwukolumnowy widok na desktopie oraz zarządzanie kontami użytkowników agencji.

### 20.1 Rozszerzenie modelu kandydata

Migracja `add_personal_details_to_candidates`:

```
address           string  null   -- adres zamieszkania
date_of_birth     date    null   -- data urodzenia
work_history      jsonb   '[]'   -- historia pracy: [{employer, position, period, description?}]
```

`work_history` to lista wpisów (pracodawca, stanowisko, okres, opcjonalny opis).
`CandidateResource` i `UpdateCandidateRequest` rozszerzone o te pola
(walidacja: `date_of_birth` = data; `work_history` = tablica obiektów string).

### 20.2 PDF profilu — nowe dane

Szablon `pdf/profile.blade.php` rozszerzony o: **adres**, **data urodzenia** (z wyliczonym
wiekiem) oraz sekcję **Historia pracy** (lista: pracodawca — stanowisko, okres). Dane
osobowe wrażliwe pozostają poza uwagami wewnętrznymi (bez `recruiter_notes`/`internal_notes`).

### 20.3 Edycja kandydata + profil dwukolumnowy

- Pełny formularz edycji kandydata (`/candidates/{id}/edit`): dane osobowe (imię, nazwisko,
  e-mail, telefon, adres, data urodzenia, narodowość, miasto/kraj, dostępność, źródło),
  uprawnienia/atrybuty (kat., ADR, Kod 95, HDS, chłodnia, plandeka, międzynarodowe, DE/EN),
  historia pracy (dynamiczna lista), notatka wewnętrzna. Endpoint: `PATCH /candidates/{id}`.
- **Profil 2-kolumnowy (desktop ≥ `lg`)**: lewa kolumna (≈⅔) — dane, uprawnienia, akcje PDF,
  dokumenty, kontakty; prawa kolumna (≈⅓, „lepki" panel) — kompletność, status w ogłoszeniach,
  timeline, RODO. Na telefonie układ pozostaje jednokolumnowy (te same komponenty).

### 20.4 Moduł użytkowników (zarządzanie kontami)

- Tabela `users` już istnieje (rola: `admin` / `recruiter`, scoping per tenant).
- **Autoryzacja: wyłącznie administrator** (Policy `UserPolicy` / sprawdzenie `isAdmin`).
- Endpointy: `GET/POST/PATCH/DELETE /api/v1/users` (lista, tworzenie, edycja, dezaktywacja).
  Tworzenie: imię, e-mail (unikalny w tenancie), hasło, rola. Edycja: dane + opcjonalna zmiana
  hasła + rola. Administrator nie może usunąć samego siebie.
- Frontend: widok `/users` (lista + dodawanie + edycja), dostępny tylko dla administratora
  (pozycja nawigacji `adminOnly`, strażnik trasy przekierowuje nie-adminów).

### 20.5 Testy (Faza 6)

Aktualizacja danych osobowych kandydata (adres, data urodzenia, historia pracy) ·
administrator tworzy/edytuje/usuwa użytkownika · rekruter nie ma dostępu do modułu
użytkowników (403) · administrator nie może usunąć samego siebie. Cel: 100% zielonych.

---

## 21. Faza 7 — Skierowania, kalendarz przyjazdów i rozliczenia

> Cel: skierowanie do pracy generujemy **z karty kierowcy**, z osobno wpisywaną
> datą i godziną przyjazdu. Przyjazd trafia do **wbudowanego kalendarza**, gdzie
> rekruter weryfikuje, czy kierowca dotarł („Dotarł / Nie dotarł"). Dla
> administratora powstają **terminy rozliczeń**: płatność dzielimy na **dwie raty**
> (faktury co 2 tygodnie), a ich terminy widać w tym samym kalendarzu.

### 21.1 Pojęcie domenowe: `Placement` (skierowanie)

Dotychczas „Skierowanie do pracy" było generowane doraźnie z ogłoszenia (statyczne
dane oferty). Wprowadzamy trwały byt **`Placement`** = konkretne skierowanie
*danego kierowcy* do *danego ogłoszenia/firmy* na *konkretny termin przyjazdu*.

Dzięki temu mamy do czego podpiąć: status przyjazdu (kalendarz) oraz harmonogram
rozliczeń (raty/faktury).

### 21.2 Model danych

Migracja `create_placements_table`:

```
id                   uuid    pk
tenant_id            uuid    fk tenants
candidate_id         uuid    fk candidates  (kierowca)
job_posting_id       uuid    fk job_postings
company_id           uuid    fk companies   (denormalizacja — pracodawca, do rozliczeń)
created_by           uuid    fk users  null
arrival_at           datetime               -- data i GODZINA przyjazdu (wpisywane osobno)
arrival_status       enum(pending|confirmed|no_show) default pending
arrival_confirmed_at datetime null
arrival_confirmed_by uuid fk users null
total_amount         decimal(10,2) null     -- kwota całkowita rozliczenia
currency             string(3) default 'EUR'
notes                text null
timestamps, softDeletes
index(tenant_id, arrival_at)
```

Migracja `create_placement_installments_table` (raty rozliczenia):

```
id            uuid pk
tenant_id     uuid fk tenants
placement_id  uuid fk placements cascade
sequence      smallint        -- 1 lub 2
due_date      date            -- termin wystawienia faktury
amount        decimal(10,2) null
status        enum(pending|invoiced|paid) default pending
invoiced_at   date null
paid_at       date null
timestamps
index(tenant_id, due_date)
```

**Kwota rozliczenia jest ustalona z góry** w ustawieniach agencji
(`settings.placement_fee` + `placement_currency`) — rekruterka jej nie podaje
ani nie widzi. System bierze ją automatycznie przy tworzeniu skierowania
(administrator może ją wyjątkowo nadpisać). Dane finansowe (kwota, raty) są
**widoczne wyłącznie dla administratora**: ukrywa je `PlacementResource`
(dla nie-admina `total_amount=null`, `installments=[]`) oraz UI.

**Reguła generowania rat** (decyzja biznesowa): przy utworzeniu skierowania z
`arrival_at` i stałą kwotą system tworzy **2 raty**:
- rata 1 — `due_date = data_przyjazdu + 14 dni`, `amount = total/2`,
- rata 2 — `due_date = data_przyjazdu + 28 dni`, `amount = total − amount_raty_1`
  (różnica wyrównuje zaokrąglenie groszy).

Terminy i kwoty rat administrator może później ręcznie skorygować (PATCH na racie).

### 21.3 Enumy

`ArrivalStatus`: `pending` (oczekuje), `confirmed` (dotarł), `no_show` (nie dotarł).
`InstallmentStatus`: `pending` (do wystawienia), `invoiced` (wystawiona),
`paid` (opłacona).

### 21.4 Generowanie skierowania z karty kierowcy

Na karcie kandydata sekcja **„Skierowania"**:
1. wybór ogłoszenia (z rekrutacji kandydata lub dowolnego aktywnego ogłoszenia),
2. **data + godzina przyjazdu** (osobne pole `datetime-local`),
3. „Generuj skierowanie" → tworzy `Placement` (kwota ze stałej stawki + 2 raty)
   i od razu otwiera PDF.

Kwota i raty **nie pojawiają się w formularzu rekruterki** — to dane finansowe,
ustawiane raz w sekcji „Rozliczenia" w Ustawieniach (admin). Na liście skierowań
raty/kwoty widzi tylko administrator.

PDF (`pdf/referral.blade.php`) jest ten sam co wcześniej, ale:
- datę przyjazdu bierzemy z `placement.arrival_at` (wpisana ręcznie), nie z oferty,
- w nagłówku/hero pokazujemy **imię i nazwisko kierowcy**.

`GenerateReferralPdfAction::render()` przyjmuje teraz dodatkowo opcjonalnie
`?Candidate $candidate` i `?string $arrivalOverride`, aby spersonalizować dokument.

### 21.5 Kalendarz (wbudowany)

Endpoint `GET /api/v1/calendar?from=&to=` zwraca listę wydarzeń w zakresie:
- **przyjazdy** (`type=arrival`) — z `placements`: `date=arrival_at`, kierowca,
  firma, tytuł ogłoszenia, `arrival_status`; widoczne dla wszystkich,
- **rozliczenia** (`type=installment`) — z `placement_installments`:
  `date=due_date`, kierowca, kwota, `sequence`, `status`; **tylko administrator**.

Frontend `/calendar`:
- responsywna **siatka miesięczna** (nawigacja prev/next, etykieta miesiąca),
  kafelki dni z kolorowymi „chipami" wydarzeń (przyjazd = czerwony/akcent,
  rozliczenie = neutralny/bursztynowy wg statusu),
- wybór dnia otwiera panel agendy z akcjami:
  - przy przyjeździe: **„Dotarł" / „Nie dotarł"** (PATCH `arrival_status`),
  - przy racie (admin): **„Wystawiona" / „Opłacona"** (PATCH `status`).
- filtr typów wydarzeń (przyjazdy / rozliczenia).

Pozycja nawigacji **„Kalendarz"** dla wszystkich (rozliczenia widzi tylko admin).

### 21.6 Endpointy (API)

```
# Skierowania
GET    /candidates/{candidate}/placements          lista skierowań kierowcy
POST   /candidates/{candidate}/placements          utwórz (job_posting_id, arrival_at, total_amount?, currency?, notes?)
GET    /placements/{placement}/referral-pdf        PDF skierowania (z datą z placement)
PATCH  /placements/{placement}/arrival             { status: confirmed|no_show }
DELETE /placements/{placement}                      usuń skierowanie

# Raty (rozliczenia) — tylko administrator
PATCH  /placement-installments/{installment}        { status, invoiced_at?, paid_at?, amount?, due_date? }

# Kalendarz
GET    /calendar?from=YYYY-MM-DD&to=YYYY-MM-DD       wydarzenia (przyjazdy + raty[admin])
```

Stary endpoint `GET /job-offers/{jobPosting}/referral-pdf` zostaje (skierowanie
„czyste", bez konkretnego kierowcy) dla zachowania kompatybilności.

### 21.7 Autoryzacja

- Tworzenie/oznaczanie przyjazdu skierowania: rekruter i administrator.
- Edycja rat (statusy/kwoty/terminy) oraz podgląd wydarzeń rozliczeniowych w
  kalendarzu: **wyłącznie administrator** (kwoty faktur to dane finansowe agencji).

### 21.8 Testy (Faza 7)

Utworzenie skierowania liczy 2 raty (+14 / +28 dni, kwoty 50/50, suma = total) ·
PDF skierowania używa wpisanej daty przyjazdu i nazwiska kierowcy · oznaczenie
„Dotarł" ustawia status i znacznik czasu · kalendarz zwraca przyjazdy dla
rekrutera, a raty tylko dla administratora · rekruter nie może edytować raty (403).
Cel: 100% zielonych.

---

## 22. Faza 8 — Grafiki ogłoszeń (AI generuje tło, backend nakłada tekst)

> Cel: profesjonalne grafiki ofert na social media (post / reels) **bez literówek
> w polskich napisach**. Generowanie całego plakatu (z tekstem) przez model obrazu
> dawało błędy typu „STANOWISKD", „Kierowea", „Dvstrybucja". Rozdzielamy więc
> grafikę na dwa etapy.

### 22.1 Architektura dwuetapowa

**Etap A — tło z AI (bez tekstu).** `GeneratePosterAction::buildBackgroundPrompt()`
buduje prompt opisujący wyłącznie scenę (nowoczesna biała ciężarówka po prawej/
dolnej stronie, jasne korporacyjne tło, czerwone akcenty, pusta jasna przestrzeń
po lewej/górze na tekst) ze **stanowczymi zakazami** generowania jakichkolwiek
napisów, liter, cyfr, logotypów, tablic rejestracyjnych, znaków wodnych.
Wywołanie: `OpenAiClient::image($prompt, '1024x1536', 'medium')` → bajty PNG.

**Etap B — tekst deterministyczny.** Tło jest osadzane jako `data-URI` w szablonie
`pdf/poster.blade.php`, a **Chromium (Gotenberg) renderuje na nim tekst oferty**
(stanowisko, lokalizacja, kategorie, wynagrodzenie, benefity, CTA „APLIKUJ",
nazwa agencji). Dzięki temu polskie napisy są zawsze poprawne, layout
kontrolowany, a wynik ma dokładny rozmiar **1080×1350** (feed) lub **1080×1920**
(reels). Warstwa „scrim" (gradienty) utrzymuje czytelność tekstu nad zdjęciem.

### 22.2 Odporność i wymagania

- **Tło generujemy raz i reużywamy.** Wygenerowane tło zapisujemy w S3
  (`job_postings.poster_bg_path`); kolejne plakaty pobierają je z S3 **bez
  ponownego wywołania AI**. AI uruchamia się ponownie tylko przy „Odśwież tło"
  (`?refresh=1`).
- **Gotowe grafiki trafiają do S3** (`{folder}/poster-feed.png`,
  `poster-reels.png`) — szybkie pobranie / archiwum.
- Tło z AI jest **opcjonalne**: brak klucza API / błąd / brak sieci → grafika i
  tak powstaje na zaprojektowanym jasnym tle (fallback). Błąd AI jest logowany
  (`Log::warning`), nie przerywa generowania; w razie błędu reużywamy
  poprzedniego tła, jeśli istnieje.
- `OpenAiClient::image()` używa tego samego klucza z ustawień organizacji co opisy.
- Endpoint: `GET /job-offers/{jobPosting}/poster?format=feed|reels[&refresh=1]`.
- `nginx`: `fastcgi_read_timeout` = `180s` (generowanie tła trwa kilkanaście–
  kilkadziesiąt sekund). Serwer potrzebuje dostępu do `api.openai.com`
  (polityka sieci) oraz działającego kontenera Gotenberg.

### 22.3 Układ i przygotowanie danych

Szablon `pdf/poster.blade.php` to czytelny plakat ogłoszenia pracy: duży nagłówek
„PRACA DLA / KIEROWCY", sekcje z labelami (Stanowisko + podtytuł, Lokalizacja,
Kategoria, System pracy), wyróżnione **wynagrodzenie na czerwono**, pełnej
szerokości czerwony przycisk „APLIKUJ TERAZ" i stopka z nazwą agencji. Paleta:
granat `#071A33`, labele `#3A4656`, czerwony `#C91414`. Warianty `feed`
(1080×1350) i `reels` (1080×1920) różnią się rozmiarami fontów (klasa na `body`).

Logikę danych liczy `GeneratePosterAction` (Blade pozostaje prezentacyjny):
- **rozbicie tytułu** po „–/—/-" → `headline`, `subtitle`, fallback lokalizacji
  (`Kierowca C+E – Dystrybucja … – Niemcy (Lipsk)`),
- **lokalizacja** z osobnych pól (`country` / `region_base`), a dopiero przy ich
  braku z tytułu,
- **wynagrodzenie** jako zakres z półpauzą i walutą w jednej linii
  (`2 100 - 2 300` → `2100–2300 EUR`) z dopiskiem **„na rękę"**,
- **dobór rozmiaru fontu** stanowiska wg długości (długie nazwy nie wychodzą
  poza canvas).

Fallback bez AI (jasny gradient + znak wodny ciężarówki SVG + czerwony akcent)
wygląda samodzielnie — brak pustych `url('')`.
