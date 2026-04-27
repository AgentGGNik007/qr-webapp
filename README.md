# QR-Webapp

Eine datenschutzkonforme Web-Applikation zur Verwaltung, Analyse und Bereitstellung von QR-Codes für Community-Events.

## Projektbeschreibung

Die QR-Webapp wurde entwickelt um die Reichweite von Community-Veranstaltungen anonym messbar zu machen. Besucher scannen einen QR-Code, werden über einen getrackten Redirect zu einem Discord-Server weitergeleitet und die Scan-Statistiken werden datenschutzkonform ausgewertet.

## Features

- **QR-Code Generator** – Wizard-basierte Erstellung mit individuellen Farben, Logo-Einbettung mit freier Positionierung und KI-Hintergrundentfernung
- **QR-Bibliothek** – Verwaltung der letzten 10 generierten QR-Codes mit Download (PNG/SVG)
- **Statistik-Dashboard** – Interaktive Auswertung mit Monats- und Wochenansicht, Vergleichsfunktion
- **Discord Invite URL** – Verwaltung mit Guild-ID Validierung via Discord API
- **E-Mail-Benachrichtigung** – Zentralisierter 24h URL-gebundener Cooldown bei ungültigem Invite-Link
- **Zero Trust Zugriff** – Administrationsoberfläche gesichert via Cloudflare Zero Trust (Google OIDC)
- **Datenschutzkonform** – Keine personenbezogenen Daten in Logs, DSGVO-konformes Design

## Tech-Stack

- **Backend:** PHP 8.3, SQLite, Composer
- **Frontend:** Vanilla JS, Chart.js 4.4.1, CSS Custom Properties (4 Themes, WCAG AAA)
- **Server:** Apache 2.4, Ubuntu, Contabo VDS
- **Tracking:** Shlink (selbstgehostet, Docker) mit anonymisiertem Link-Tracking
- **Sicherheit:** Cloudflare Proxy, Zero Trust (Google OIDC), HSTS, Security-Header
- **E-Mail:** PHPMailer + Brevo SMTP
- **KI:** rembg (Python, CPU) für Hintergrundentfernung
- **Konvertierung:** rsvg-convert für SVG→PNG

## QR-Code Generator Wizard

1. **Hintergrundfarbe** wählen
2. **Vordergrundfarbe** wählen
3. **Logo** optional hochladen (PNG oder SVG, max 2MB)
4. **Hintergrund entfernen** – KI-basierte Hintergrundentfernung (optional)
5. **Logo positionieren** – Drag & Drop auf Vorschau-QR, Größen-Slider, gesperrte Zonen sichtbar
6. **Vorschau** – fertiger QR-Code mit allen Einstellungen
7. **Übernehmen** – QR-Code wird gespeichert

## Discord Invite URL Validierung

Die URL wird nicht nur auf Erreichbarkeit geprüft, sondern verifiziert über die Discord API ob der Link auf den korrekten Server (Guild-ID) zeigt. Bei Fehler wird eine E-Mail verschickt – maximal einmal pro 24 Stunden pro URL.

## Architektur
```
Browser → Cloudflare Proxy → Apache → PHP
                ↓
         Zero Trust (nur /zero-trust/*)
                ↓
         Shlink (Docker) → Statistik
```

## Routen

| Pfad | Zugang |
|------|--------|
| `/` | 301 → `/datenschutz/` |
| `/datenschutz/` | Öffentlich |
| `/join/` | Öffentlich |
| `/healthz.txt` | Öffentlich |
| `/zero-trust/dashboard/` | Zero Trust |
| `/zero-trust/bib/` | Zero Trust |
| `/zero-trust/interessensabwaegung/` | Zero Trust |
| `/zero-trust/api/*` | Zero Trust |

## Datenschutz

- Keine IP-Adressen oder personenbezogene Daten in Server-Logs
- Shlink mit `ANONYMIZE_REMOTE_ADDR=true`
- Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO
- AV-Verträge mit Contabo und Cloudflare

## Entwicklung
```bash
# Git deploy
cd /var/www/qr-webapp && git pull

# Apache reload
sudo apache2ctl -t && sudo systemctl reload apache2

# Logs live
sudo tail -f /var/log/apache2/qr.framenode.net_access.log

# Git push (als niklas)
sudo -u niklas git -C /var/www/qr-webapp push origin dev
```

## Verantwortlicher

**Niklas Rühl** – [datenschutz@framenode.net](mailto:datenschutz@framenode.net)
