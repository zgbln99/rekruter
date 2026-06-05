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
- **Faza 3 — Pipeline + Klienci** ✅ — Firmy, Ogłoszenia, Aplikacje, kanban rekrutacyjny
  (bottom-sheet zmiany etapu). 32 testy zielone.
- **Faza 4 — Utwardzenie** ✅ — RODO (eksport/zgoda/usunięcie danych), autoryzacja per rola,
  offline queue (PWA), nagłówki bezpieczeństwa, `docker-compose.prod.yml`. 39 testów zielonych.

**MVP kompletne (Fazy 0–4).** Dalszy rozwój: patrz [`DESIGN.md`](./DESIGN.md) sekcje 15 i 17.

## Wdrożenie na VPS (Termius) — port 4050

Produkcyjny stack wystawia **jeden port publiczny: 4050**. Kontener `web` (nginx)
serwuje PWA i proxuje `/api` do backendu (PHP-FPM) — ten sam origin, brak CORS.
Storage zapewnia wbudowane MinIO (wewnętrzne; docelowo można przełączyć na MEGA S3).

### 1. Wymagania na VPS

```bash
# Docker + Compose (jeśli brak)
curl -fsSL https://get.docker.com | sh

# Otwórz port 4050 (jeśli używasz ufw)
sudo ufw allow 4050/tcp
```

### 2. Pobranie kodu

```bash
git clone <URL_REPO> rekruter && cd rekruter
git checkout claude/nifty-pascal-EEsAQ
```

### 3. Konfiguracja

```bash
cp .env.example .env
cp backend/.env.example backend/.env

# Wygeneruj klucz aplikacji i wstaw do backend/.env
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" backend/.env

# (zalecane) zmień hasła w .env i backend/.env: DB_PASSWORD, AWS_SECRET_ACCESS_KEY
# (opcjonalnie) ustaw publiczny adres w backend/.env, np.:
#   APP_URL=http://TWOJE_IP:4050
```

### 4. Start

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Migracje i seed (administrator + etapy pipeline) uruchamiają się automatycznie.

### 5. Logowanie

Otwórz `http://TWOJE_IP:4050`

- e-mail: `admin@rekruter.local`
- hasło: `password`  ← **zmień po pierwszym logowaniu**

> Domyślne dane logowania nadpiszesz przed startem zmiennymi `SEED_ADMIN_EMAIL`
> i `SEED_ADMIN_PASSWORD` w `backend/.env`.

### Przydatne komendy

```bash
docker compose -f docker-compose.prod.yml ps          # status
docker compose -f docker-compose.prod.yml logs -f app # logi backendu
docker compose -f docker-compose.prod.yml down        # zatrzymanie
docker compose -f docker-compose.prod.yml up -d --build   # aktualizacja po git pull
```

### Uwagi produkcyjne

- **Poczta**: domyślnie `MAIL_MAILER=log` (e-maile trafiają do logu, nic nie jest wysyłane).
  Aby wysyłać profile do klientów, ustaw realny SMTP w `backend/.env` i usuń override
  `MAIL_MAILER: log` z `docker-compose.prod.yml` (usługi `app` i `queue`).
- **HTTPS**: dla produkcji zalecany reverse proxy (Caddy/Traefik/nginx) z certyfikatem
  przed portem 4050, lub publikacja za domeną.
- **Storage docelowy**: aby użyć MEGA S3 zamiast wbudowanego MinIO, ustaw `AWS_ENDPOINT`,
  `AWS_*` w `backend/.env` i usuń usługi `minio`/`minio-init` z compose.
