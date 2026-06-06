# HTTPS + powiadomienia push (darmowo, bez własnej domeny)

Powiadomienia push na telefonie działają **tylko po HTTPS**. Poniżej najprostsza,
darmowa droga: subdomena z **DuckDNS** + automatyczny certyfikat (Caddy).

## 1. Darmowa subdomena (DuckDNS)

1. Wejdź na https://www.duckdns.org i zaloguj się (Google/GitHub).
2. Utwórz subdomenę, np. `ltslogistik` → dostaniesz `ltslogistik.duckdns.org`.
3. W polu **current ip** wpisz **publiczny IP swojego VPS** i kliknij „update ip".
   (sprawdź IP: `curl ifconfig.me` na serwerze)

## 2. Otwórz porty 80 i 443 na VPS

```bash
sudo ufw allow 80
sudo ufw allow 443
```
(lub w panelu firewalla u dostawcy VPS)

## 3. Ustaw domenę w .env

W głównym pliku `./.env` (tam gdzie klucze MEGA/VAPID) dopisz:
```
DOMAIN=ltslogistik.duckdns.org
```

## 4. Uruchom z nakładką HTTPS

```bash
cd ~/rekruter
docker compose -f docker-compose.prod.yml -f docker-compose.https.yml up -d
```

Caddy sam pobierze certyfikat Let's Encrypt (kilkanaście sekund). Od teraz:
**https://ltslogistik.duckdns.org** — z kłódką.

## 5. Włącz push

1. Otwórz aplikację po **https://** (nie po IP:4050).
2. (jednorazowo) wygeneruj klucze, jeśli jeszcze nie:
   `docker compose -f docker-compose.prod.yml exec -T app php artisan rekruter:vapid`
   i wstaw `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` do `.env`, potem
   `docker compose -f docker-compose.prod.yml -f docker-compose.https.yml up -d`.
3. W aplikacji: **dzwonek → „Włącz"** → zaakceptuj zgodę → **„Wyślij testowe"**.

> iPhone: najpierw „Dodaj do ekranu głównego", potem włącz push (iOS 16.4+).

## Uwagi

- Stary dostęp po `http://IP:4050` nadal działa (do testów), ale **push tylko po HTTPS**.
- Komenda przypomnień (cron): `docker compose -f docker-compose.prod.yml exec -T app php artisan rekruter:send-reminders`.

---

# Wariant z domeną na Cloudflare

Jeśli masz (lub kupujesz) domenę na **Cloudflare**, jest jeszcze prościej —
nie trzeba DuckDNS.

## 1. Dodaj rekord DNS

W panelu Cloudflare → **DNS → Add record**:
- **Type:** A
- **Name:** `panel` (czyli adres `panel.twojadomena.pl`) — lub `@` dla domeny głównej
- **IPv4 address:** publiczny IP Twojego VPS (`curl ifconfig.me`)
- **Proxy status:** **DNS only** (szara chmurka) — WAŻNE, żeby Caddy sam pobrał certyfikat

> Pomarańczowa chmurka (proxied) też się da, ale wtedy certyfikat trzeba załatwić
> inaczej (DNS-01 z tokenem API albo Origin Certificate). Na start zostaw **szarą**.

## 2. Otwórz porty 80 i 443 na VPS

```bash
sudo ufw allow 80
sudo ufw allow 443
```

## 3. Ustaw domenę i uruchom

W `./.env`:
```
DOMAIN=panel.twojadomena.pl
```
```bash
cd ~/rekruter
docker compose -f docker-compose.prod.yml -f docker-compose.https.yml up -d
```

Caddy pobierze certyfikat (kilkanaście sekund) → **https://panel.twojadomena.pl**.

## 4. Push

Wejdź po **https://**, dzwonek → **„Włącz"** → zgoda → **„Wyślij testowe"**.

> Gdy później zechcesz włączyć pomarańczową chmurkę (ukrycie IP, ochrona Cloudflare):
> w Cloudflare ustaw **SSL/TLS → Full (strict)**. Jeśli certyfikat przestanie się
> odnawiać przez proxy — napisz, dodam wariant Caddy z modułem Cloudflare (DNS-01,
> działa nawet przy pomarańczowej chmurce i ukrywa serwer).

