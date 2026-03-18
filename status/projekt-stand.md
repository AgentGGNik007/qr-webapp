# QR-Webapp Projekt – Stand 18.03.2026

## Grundinfos
- **Domain:** `qr.framenode.net`
- **Server:** Contabo VDS, Ubuntu, Apache 2.4.58, PHP 8.3
- **Repo:** `https://github.com/AgentGGNik007/qr-webapp` (Branch: `dev`)
- **Serverpfad:** `/var/www/qr-webapp`

## Projektstruktur
```
/var/www/qr-webapp/
├── cron/
│   ├── check-invite.php      # täglich 23:00 – Invite-URL prüfen + E-Mail
│   └── new-day.php           # täglich 00:01 – neuen Tag in tracking_days eintragen
├── config/
│   └── .env                  # SMTP + Notify + Shlink (www-data, 640, gitignored)
├── data/
│   ├── app.sqlite            # SQLite DB (www-data, gitignored)
│   └── uploads/              # temporäre Logo-Uploads
├── includes/
│   ├── config.php            # DB-Verbindung + .env Loader
│   ├── footer.php            # Footer-Include
│   ├── head.php              # Header-Include (global.css + extraCss[])
│   ├── invite-check.php      # checkInviteUrl() via cURL
│   ├── mailer.php            # sendMail() via PHPMailer + Brevo SMTP
│   └── qr-generator.php      # generateQrCode(), getQrLibrary(), getLatestQr()
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── global.css    # Theme-System, Base, Layout, Cards, Buttons, Icons
│   │   │   ├── dashboard.css # Dashboard-spezifisches CSS
│   │   │   ├── bib.css       # QR-Bibliothek CSS
│   │   │   ├── privacy.css   # Datenschutz + Interessensabwägung CSS
│   │   │   └── error.css     # Fehlerseite CSS (join/index.php)
│   │   ├── js/
│   │   │   ├── app.js        # Theme-Toggle (Cycle + Footer-Dropdown)
│   │   │   └── dashboard.js  # Dashboard JS (Chart, QR-Popout, URL-Card)
│   │   └── qr/               # generierte QR-Codes (PNG + SVG, max 10)
│   ├── datenschutz/index.php # Datenschutzerklärung (öffentlich)
│   ├── join/index.php        # URL-Check + 302 Redirect auf Discord + E-Mail bei Fehler
│   ├── healthz.txt           # Health-Check (öffentlich)
│   └── zero-trust/
│       ├── api/
│       │   ├── invite-url.php    # GET/POST Discord Invite URL
│       │   ├── logo-upload.php   # POST Logo-Upload (PNG/SVG, finfo MIME-Check)
│       │   ├── qr-library.php    # GET letzte 10 QR-Codes
│       │   ├── qr-preview.php    # POST QR-Vorschau (base64, Hex-Validierung)
│       │   ├── qr-save.php       # POST QR-Code final speichern
│       │   └── stats.php         # GET Shlink-Statistik + tracking_days
│       ├── bib/index.php             # QR-Bibliothek (letzte 10 QR-Codes)
│       ├── dashboard/index.php       # Dashboard (Statistik, QR-Card, URL-Card)
│       └── interessensabwaegung/index.php # Interessensabwägung
├── status/
│   └── projekt-stand.md      # Projektstand (nicht gitignored)
└── vendor/                   # Composer (PHPMailer, endroid/qr-code)
```

## Tech-Stack
- PHP 8.3, SQLite (`data/app.sqlite`), Composer
- Apache mit SSL (Certbot), Cloudflare Proxy + Zero Trust
- Shlink (Docker, Domain: `shlink.qr.framenode.net`, Port 8081) für QR-Link-Tracking
- endroid/qr-code 6.x für QR-Generierung (GD Extension aktiv)
- PHPMailer + Brevo SMTP für E-Mail-Benachrichtigungen
- Chart.js 4.4.1 (CDN, SRI-Hash) für Statistik-Diagramm

## Datenbank (app.sqlite)
### Tabelle `config`
- `key` TEXT PRIMARY KEY
- `value` TEXT NOT NULL
- Einträge: `discord_invite_url`

### Tabelle `tracking_days`
- `date` TEXT PRIMARY KEY (Format: YYYY-MM-DD)
- `created_at` TEXT
- Einträge ab 01.03.2026, täglich per Cron ergänzt
- Rolling Window: 24 Monate (älteste werden automatisch gelöscht)

## Funktionen (aktueller Stand)
- QR-Code Generierung (PNG + SVG, 400px, Popout-Wizard: BG-Farbe → FG-Farbe → Logo → Vorschau → Übernehmen)
- QR-Code Bibliothek (max. 10, mit Zeitstempel, Download PNG/SVG)
- `join/index.php` → URL-Check → 302 Redirect auf Discord Invite URL + E-Mail bei Fehler (kein Shlink-Aufruf mehr)
- Fehlerseite bei nicht erreichbarer URL (kein Header/Footer, zentriert) + E-Mail
- Cron: tägliche URL-Prüfung um 23:00 + E-Mail bei Fehler
- Cron: neuer tracking_day täglich um 00:01
- Statistik-Dashboard mit echten Shlink-Daten
  - Monatsansicht (alle Tage 1-31, null für Zukunft)
  - Wochenansicht (So-Sa, null für Zukunft/vor firstDay)
  - Drum-Roll Picker für Monat/KW + Jahr
  - Heute-Button (springt zur aktuellen Periode)
  - Vergleichs-Chart (zweites Chart zum Periodenvergleich)
  - Pfeile für Navigation + Scroll auf Buttons
  - Navigationsgrenze = erster erfasster Tag in tracking_days
- Tracking-Architektur: QR-Code → `shlink.qr.framenode.net/j` → Shlink trackt → `qr.framenode.net/join/` → Discord
- Admin-Login über Cloudflare Zero Trust (Google OIDC, 2 erlaubte E-Mails)
- Datenschutzseite öffentlich erreichbar
- Interessensabwägung nur per Zero Trust erreichbar

## Routen
| Pfad | Zugang |
|------|--------|
| `/` | 301 → `/datenschutz/` |
| `/datenschutz/` | Öffentlich |
| `/join/` | Öffentlich |
| `/healthz.txt` | Öffentlich |
| `/zero-trust/*` | Zero Trust (inkl. `/zero-trust/api/*`) |

**Subdomain-Redirects:**
- `dashboard.framenode.net` → `qr.framenode.net/zero-trust/dashboard/` (Apache 301)

## Shlink
- Container: `shlink` (Docker)
- Domain: `shlink.qr.framenode.net` (Apache Proxy, SSL Certbot, kein CF Proxy)
- API-Key: `********` (in `.env` als `SHLINK_API_KEY`)
- Short-URL Slug: `j`
- Short-URL: `https://shlink.qr.framenode.net/j` (in `.env` als `SHLINK_SHORT_URL`)
- Long-URL: `https://qr.framenode.net/join/`
- `ANONYMIZE_REMOTE_ADDR=true`
- `TRACK_ORPHAN_VISITS=false`
- Daten: `/opt/shlink/data`

## Docker Shlink starten
```bash
docker stop shlink && docker rm shlink && docker run -d \
  --name shlink --restart unless-stopped \
  -p 127.0.0.1:8081:8080 \
  -v /opt/shlink/data:/data \
  -e DB_DRIVER=sqlite \
  -e DB_NAME=/data/database.sqlite \
  -e DEFAULT_DOMAIN=shlink.qr.framenode.net \
  -e IS_HTTPS_ENABLED=true \
  -e TZ=Europe/Berlin \
  -e INITIAL_API_KEY=******** \
  -e ANONYMIZE_REMOTE_ADDR=true \
  -e TRACK_ORPHAN_VISITS=false \
  shlinkio/shlink:stable
```

## E-Mail (Brevo SMTP)
- Absender: `noreply@framenode.net`
- Empfänger: `datenschutz-cozycrit@googlegroups.com`
- Config: `/var/www/qr-webapp/config/.env`
- Variablen: `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `SMTP_FROM_NAME`, `NOTIFY_EMAIL`

## .env Variablen
```
SMTP_HOST
SMTP_PORT
SMTP_USER
SMTP_PASS
SMTP_FROM
SMTP_FROM_NAME
NOTIFY_EMAIL
SHLINK_API_KEY
SHLINK_SHORT_URL
```

## Cron-Jobs (www-data)
```
0  23 * * * /usr/bin/php /var/www/qr-webapp/cron/check-invite.php
1  0  * * * /usr/bin/php /var/www/qr-webapp/cron/new-day.php
```

## Sicherheit
- Cloudflare Proxy + Zero Trust (Google OIDC) als einzige Zugangskontrolle
- Apache Origin nur für Cloudflare-IPs erreichbar
- HSTS + Security-Header aktiv
- Keine personenbezogenen Daten in Server-Logs (IP, User-Agent)
- Log-Format: nur Timestamp, Request, Status, Bytes
- Shlink mit `ANONYMIZE_REMOTE_ADDR=true`
- API-Endpunkte unter `/zero-trust/api/*` – vollständig hinter Zero Trust
- Hex-Validierung bei QR-Farben (qr-preview.php, qr-save.php)
- MIME-Type Prüfung per `finfo_file()` bei Logo-Upload (nur PNG + SVG erlaubt)
- Shlink API-Key aus `.env` (nicht mehr hardcodiert)
- Chart.js CDN mit SRI-Hash abgesichert
- Gilt für: `qr.framenode.net` und `shlink.qr.framenode.net`

## CSS-System
- Aufgeteilt in seitenspezifische Dateien – jede Page lädt nur was sie braucht
- `global.css` – Theme-System, Base, Layout, Cards, Buttons, Icons, Theme-Menu
- `dashboard.css` – Dashboard, Stats, Chart, Drum-Roll, Popout, URL-Card, Color-Picker
- `bib.css` – QR-Bibliothek Grid und Items
- `privacy.css` – Datenschutz + Interessensabwägung
- `error.css` – Fehlerseite (join/index.php)
- Einbindung über `$extraCss[]` Array in head.php mit automatischem Cache-Busting

## Theme-System
- 4 Themes: `light`, `grey`, `dark`, `contrast` (WCAG AAA)
- Fallback via `prefers-color-scheme` wenn kein `data-theme` gesetzt (kein hardcodierter Default mehr)
- Reihenfolge Cycle: light → grey → dark → contrast
- localStorage Key: `qr-webapp-theme`
- Header: Cycle-Button mit Icon
- Footer: Hover-Dropdown mit Slide-up (alle 4 Themes, Icon + Label)
- CSS-Variable `--accent-text` für korrekten Kontrasttext auf Accent-Hintergrund

## Farbpaletten (WCAG AAA)
### Light (Background `#F6F6F6`)
- Surface: `#FFFFFF` / `#F1F5F9`
- Text: `#1F2937` / `#374151` / `#4B5563`
- Accent: `#1D4ED8` / Hover `#1E40AF` / Focus `#F59E0B`
- Accent-Text: `#FFFFFF`

### Dark (Background `#1D1919`)
- Surface: `#262020` / `#2F2828`
- Text: `#F3F4F6` / `#E5E7EB` / `#D1D5DB`
- Accent: `#93C5FD` / Hover `#BFDBFE` / Focus `#FBBF24`
- Accent-Text: `#000000`

### Grey (Background `#838383`)
- Surface: `#9A9A9A` / `#8F8F8F`
- Text: `#111827` / `#1F2937` / `#374151`
- Accent: `#1D3A8A` / Hover `#1E3270` / Focus `#B45309`
- Accent-Text: `#FFFFFF`

### Contrast (Background `#121212`)
- Surface: `#1E1E1E` / `#262626`
- Text: `#FFFFFF` / `#F5F5F5`
- Accent: `#00E5FF` / Hover `#80F0FF` / Focus `#FFD600`
- Accent-Text: `#000000`

## Arbeitsweise & Präferenzen
- Antworten auf Deutsch
- Neue Aufgaben erst theoretisch besprechen, dann schrittweise umsetzen
- Vor großen/destruktiven Änderungen nachfragen
- Code immer vollständig und direkt verwendbar (copy & paste)
- Bei Unklarheiten immer nachfragen, nie raten

## Offene Punkte
- [ ] QR-Code Download: zwei Varianten (mit Infotext und ohne) – nach Team-Absprache
- [ ] Design-Kleinigkeiten (aufgefallen während Session)

## Wichtige Befehle
```bash
# Apache reload
sudo apache2ctl -t && sudo systemctl reload apache2

# Logs live
sudo tail -f /var/log/apache2/qr.framenode.net_access.log

# Git deploy
cd /var/www/qr-webapp && git pull

# Git push (als niklas)
sudo -u niklas git -C /var/www/qr-webapp push origin dev

# Crontab www-data prüfen
crontab -u www-data -l

# Shlink API testen
curl -s https://shlink.qr.framenode.net/rest/v3/short-urls \
  -H "X-Api-Key: ********"
```

## Datenschutz
- AV-Vertrag mit Contabo abgeschlossen
- Cloudflare DPA gilt automatisch (Self-Service Plan)
- Brevo DPA vorhanden
- Verantwortlicher: Niklas Rühl
- Datenschutzerklärung: `qr.framenode.net/datenschutz/` (Stand: 18.03.2026)
- Interessensabwägung: `qr.framenode.net/zero-trust/interessensabwaegung/`
- Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO
