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
- **Faza 5 — Ogłoszenia jako centrum** ✅ — pełny moduł ogłoszeń (typ kierowcy/naczepy, kraj,
  system pracy, wynagrodzenie, gotowy opis, notatka rekruterki, skrypt rozmowy, wymagania),
  szybkie tworzenie kandydata z ogłoszenia, status kandydata w ogłoszeniu, dopasowanie
  kandydat↔ogłoszenie, dysk **MEGA S3**, zdjęcie profilowe (upload/aparat/cropper),
  kompletność profilu + braki, decyzja firmy, timeline kandydata, **wersja desktopowa**
  (boczna nawigacja od `lg`). 46 testów zielonych.

**MVP + rozbudowa biznesowa (Fazy 0–5).** Dalszy rozwój: patrz [`DESIGN.md`](./DESIGN.md) sekcje 15, 17 i 19.

## Storage dokumentów — MEGA S3

Dokumenty kandydatów (CV, skany, zdjęcia, PDF) trafiają na dysk wskazany przez
`DOCUMENTS_DISK`. Lokalnie poza developmentem **nie** zapisujemy dokumentów.

| `DOCUMENTS_DISK` | Zastosowanie |
|---|---|
| `local` | tylko development |
| `s3` | dev/prod na wbudowanym MinIO (zmienne `AWS_*`) |
| `mega_s3` | docelowy storage MEGA S3 (zmienne `S3_*`) |

Aby użyć MEGA S3, ustaw w `backend/.env`:

```env
DOCUMENTS_DISK=mega_s3
S3_ENDPOINT=https://s3.eu-central-1.mega.io     # endpoint MEGA S3
S3_ACCESS_KEY_ID=twoj_access_key
S3_SECRET_ACCESS_KEY=twoj_secret_key
S3_BUCKET=rekruter-dokumenty
S3_REGION=us-east-1
S3_USE_PATH_STYLE_ENDPOINT=true
```

Pobieranie dokumentów: backend najpierw próbuje wygenerować **tymczasowy signed URL**
(jeśli dysk go wspiera), a w razie braku wsparcia robi **fallback do stream download**
przez uwierzytelniony, audytowany endpoint (dokumenty nigdy nie są publiczne — RODO).

## Jak sprawdzić upload plików

1. Zaloguj się i otwórz dowolnego kandydata.
2. W sekcji „Dokumenty" wybierz typ (np. Prawo jazdy) i kliknij **Plik** (lub **Aparat** na telefonie).
3. Po wgraniu plik pojawia się na liście; kliknij ikonę pobierania, aby go odebrać.
4. Weryfikacja po stronie storage: plik powinien znaleźć się na skonfigurowanym dysku
   (`DOCUMENTS_DISK`). Dla MinIO sprawdź w konsoli MinIO (dev), dla MEGA S3 w panelu bucketa.
   W bazie tabela `documents` zawiera `storage_disk`, `storage_path`, `original_filename`, `mime_type`.

## Jak sprawdzić zdjęcie profilowe

1. Na profilu kandydata dotknij awatara (ikona aparatu) → wybierz/zrób zdjęcie.
2. Otworzy się **cropper** — zaznacz twarz i zapisz; zdjęcie zapisze się na dysku dokumentów
   i ustawi jako zdjęcie profilowe (`candidates.profile_photo_id`).
3. Wytnij z dokumentu: dodaj graficzny dokument, a następnie użyj go jako źródła kadru
   (jpg/jpeg/png/webp). Zdjęcie pojawi się w nagłówku profilu i trafi do PDF.

## Jak wygenerować PDF

1. Na profilu kandydata kliknij **Generuj PDF** — otworzy się podgląd (Gotenberg renderuje
   HTML → PDF). PDF zawiera zdjęcie z MEGA S3 (placeholder, gdy brak), uprawnienia, języki,
   dostępność, doświadczenie oraz ogłoszenie i firmę docelową; **bez** uwag wewnętrznych.
2. **Wyślij profil** — wybierz ogłoszenie/firmę i adres e-mail; wysyłka idzie przez kolejkę,
   a historia zapisuje się w `profile_sends` (z możliwością ustawienia decyzji firmy).
3. Wymóg działania PDF: uruchomiona usługa `gotenberg` (jest w `docker-compose`).

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
