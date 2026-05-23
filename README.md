# Labor Virus-Jagd — PWA

Cinematic Arcade Shooter als installierbare Progressive Web App.

## Inhalt

| Datei                    | Zweck                                              |
|--------------------------|----------------------------------------------------|
| `index.html`             | Spiel inkl. eingebettetem Hintergrundbild          |
| `manifest.webmanifest`   | Web App Manifest                                   |
| `sw.js`                  | Service Worker (Cache-First, Network-First HTML)   |
| `icon-192.png`           | App-Icon 192×192                                   |
| `icon-512.png`           | App-Icon 512×512                                   |
| `icon-maskable-512.png`  | Maskable Icon mit Safe-Zone (Android)              |
| `apple-touch-icon.png`   | iOS Home-Screen Icon 180×180                       |
| `favicon-64.png`         | Browser-Tab Favicon                                |

## Deployment

1. Alle Dateien in **dasselbe Verzeichnis** auf einen HTTPS-fähigen Webserver hochladen
   (oder `localhost` für Tests). Service Worker erfordern HTTPS.
2. Im Browser öffnen → automatisch wird SW registriert und installierbar.
3. Auf Desktop-Chrome: Install-Button erscheint im Hauptmenü.
4. Auf iOS Safari: "Zum Home-Bildschirm hinzufügen" über das Teilen-Menü.
5. Auf Android-Chrome: A2HS-Banner erscheint automatisch oder über das Menü.

## Updates ausrollen

Im `sw.js` die `CACHE_VERSION` inkrementieren (z.B. `v1` → `v2`).
Beim nächsten Besuch erkennt der Browser das, lädt die neue Version im Hintergrund
und zeigt dem User unten einen "Neue Version verfügbar / Aktualisieren"-Toast.

## Offline-Verhalten

- Erster Besuch: alle Assets werden gecacht.
- Folgebesuche: läuft komplett offline (alles ist lokal verfügbar inkl. Hintergrundbild).
- HTML wird Network-First geladen → neue Inhalte sind sofort sichtbar, Fallback auf Cache wenn offline.
- Statische Assets (Icons, Manifest) Cache-First mit Hintergrund-Refresh.

## Lokales Testen

```bash
# einfacher Python-HTTP-Server (PWA-Install funktioniert über http://localhost)
python3 -m http.server 8080
# dann http://localhost:8080 öffnen
```

## Anpassungen

- **App-Name:** in `manifest.webmanifest` (`name`, `short_name`)
- **Theme-Farben:** dort und im `<meta name="theme-color">` in `index.html`
- **Icons:** PNGs ersetzen, Dimensionen beibehalten
- **Highscore-Speicher:** localStorage-Key ist `laborVirusHighscoreCinematic`

## Server-Highscore-Liste

Diese Version enthält eine echte Server-Highscore-Liste.

Neue Dateien/Ordner:

| Datei / Ordner              | Zweck                                      |
|----------------------------|---------------------------------------------|
| `api/highscore.php`        | PHP-API zum Lesen und Speichern der Scores  |
| `data/highscores.json`     | gespeicherte Highscore-Daten                |
| `data/.htaccess`           | schützt den Datenordner bei Apache          |

### Voraussetzungen

- Webserver mit PHP 7.4 oder neuer
- Der Ordner `data` muss für PHP beschreibbar sein.
- Alle Dateien gemeinsam hochladen, also `index.html`, `sw.js`, `api/` und `data/`.

### Test

Nach dem Upload im Browser aufrufen:

```text
api/highscore.php
```

Wenn alles funktioniert, kommt eine JSON-Antwort wie:

```json
{"ok":true,"scores":[]}
```

Wenn das Speichern nicht klappt, dem Ordner `data` Schreibrechte geben. Je nach Hoster z. B. 755 oder 775. Nur falls nötig 777 verwenden.

### Sicherheit

Die API begrenzt Name, Punktzahl und Zusatzdaten serverseitig und speichert nur die Top 20. Für ein kleines Browserspiel reicht das meistens. Gegen absichtliches Cheaten ist eine reine Browser-Spiel-Highscore-Liste grundsätzlich nicht vollständig geschützt, weil der Score vom Client kommt.
