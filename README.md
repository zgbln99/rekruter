# Rekruter — Recruitment Operating System

System operacyjny dla agencji rekrutacyjnej specjalizującej się w zatrudnianiu
**kierowców zawodowych**. Zaprojektowany **mobile-first**, pod jeden krytyczny moment:
rozmowę telefoniczną z kandydatem (KPI: dodanie kandydata < 60 sekund).

> 📐 Pełna specyfikacja architektury, modelu domenowego, ERD, UX i ryzyk znajduje się
> w pliku [`DESIGN.md`](./DESIGN.md) — **głównym źródle prawdy** projektu.

## Stos technologiczny

| Warstwa | Technologia |
|---|---|
| Frontend | Nuxt 3 · Vue 3 · TypeScript · Pinia · Vue Query · TailwindCSS · PWA (SPA) |
| Backend | Laravel 11 · REST API · Sanctum |
| Baza danych | PostgreSQL |
| Kolejki / cache | Redis |
| Storage | S3-compatible (dev: MinIO, docelowo MEGA S3) |
| PDF | Gotenberg (HTML → PDF) |
| Poczta | SMTP (dev: Mailpit) |
| Deployment | Docker Compose |

## Struktura repozytorium

```
backend/     Laravel 11 — API (Sanctum, multi-tenant ready)
frontend/    Nuxt 3 — PWA (mobile-first, dolna nawigacja, FAB)
infra/       Konfiguracje (nginx)
DESIGN.md    Dokument projektowy (źródło prawdy)
docker-compose.yml
```

## Szybki start (Docker)

```bash
# 1. Konfiguracja
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 2. Klucz aplikacji backendu (jednorazowo, lokalnie)
#    lub pozostaw puste — entrypoint wygeneruje przy starcie.

# 3. Start środowiska
docker compose up -d --build
```

Po starcie:

| Usługa | Adres |
|---|---|
| Frontend (PWA) | http://localhost:3000 |
| Backend API | http://localhost:8000/api/v1 |
| MinIO (konsola) | http://localhost:9001 |
| Mailpit (poczta) | http://localhost:8025 |

Migracje i seed startowy (domyślny tenant + administrator) uruchamiają się automatycznie.

**Dane logowania (domyślne, dev):**

- e-mail: `admin@rekruter.local`
- hasło: `password`

> Zmień je przez zmienne `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD`.

## Rozwój lokalny (bez Dockera)

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Ustaw połączenie z PostgreSQL w .env
php artisan migrate --seed
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Testy

```bash
cd backend
php artisan test          # wymaga PostgreSQL (kolumny jsonb)
```

## Stan implementacji

- **Faza 0 — Fundament** ✅ — docker-compose, Laravel 11 (API + Sanctum + multi-tenant
  scope), Nuxt 3 PWA (SPA, dolna nawigacja, FAB, logowanie), testy autoryzacji.
- **Faza 1 — Rdzeń KPI** ✅ — Kandydaci, **Quick-Add < 60s** z deduplikacją po numerze,
  Call Log (kontakt → automatyczny task follow-up), ekran „Dziś". 15 testów zielonych.
- **Faza 2 — Dokumenty + Profil** ✅ — upload do prywatnego S3, zdjęcie profilowe (CropperJS),
  generator PDF (Gotenberg), wysyłka profilu mailem (kolejka), audit log. 25 testów zielonych.
- **Faza 3 — Pipeline + Klienci** ⏳ — Firmy, Ogłoszenia, kanban rekrutacyjny.
- Kolejne fazy: patrz [`DESIGN.md`](./DESIGN.md) sekcja 17.
