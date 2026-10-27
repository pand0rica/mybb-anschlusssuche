# Anschluss-Suchmaschine – Agent Instructions

Dieses Repo wird mit dem **MyBB Plugin Dev Kit** entwickelt. Bevor du hier etwas tust:

1. Lies zuerst das Dev-Kit: **`D:\mybb-plugin-dev-kit\AGENTS.md`** und halte dich an alle dort
   verlinkten Standards. Das Kit ist die kanonische Quelle für Konventionen, Boilerplate,
   Debugging und Testing.
2. Lies dann in diesem Repo: `PROJECT_CONTEXT.md` (Kontext) und – in der Umsetzungsphase –
   `IMPLEMENTATION_PLAN.md` (die Arbeitsschritte).

Das Dev-Kit wird **nicht** in dieses Repo kopiert; es wird per absolutem Pfad referenziert.

## Sprache
Antworten Deutsch, Code/Bezeichner Englisch. PHP 7.4 + 8.x. Keine externen Libraries.

## Kurz-Kontext
Zentrale Gesuche-Verwaltung: Einzel-, Gruppen- und lose Gesuche, ACP-konfigurierbare Felder,
Threadliste, Profil, Header-Teaser und Suchseite `anschluss.php`. Öffentliches Plugin: **nichts
Forenspezifisches hartcodieren**.
- **Relatives** (`F:\RPG\Mybb Plugins - eigene\relatives`) ist **optional**. Es dient als Quelle
  für lose Gesuche und wird ausschließlich über seine öffentliche API angesprochen, nie über
  `relatives_*`-Tabellen. Die benötigte API-Erweiterung steht in `PROJECT_CONTEXT.md`.
- **NewPoints-Shop:** In 1.0 ist nur das Datenmodell vorbereitet; die Anbindung ist
  Plan-Schritt 16 (optional).
- **Datenmigration H4W:** Sie ist nicht Teil dieses Repos, sondern Migration 012 im
  Aufräum-Repo `F:\RPG\H4W Administratives\mybb aufräumen` (`docs\migration-012-gesuche.md`).
- **Test:** lokales Forum `http://h4w.localhost:8080`. Live-Forum und Live-DB nie anfassen.

## Phasen (der Nutzer löst sie einzeln aus)

| Nutzer sagt | Phase | Folge dieser Kit-Datei |
|---|---|---|
| „umsetzen / implementieren" | Umsetzung | `D:\mybb-plugin-dev-kit\workflows\2-implement.md` + `IMPLEMENTATION_PLAN.md` |
| „reviewe das Projekt" | Review | `D:\mybb-plugin-dev-kit\workflows\3-review.md` |
| „release das Projekt" | Release | `D:\mybb-plugin-dev-kit\workflows\4-release.md` |

## Die 5 goldenen Regeln (Kurzform, Details im Kit)
Keine Core-Edits · `TABLE_PREFIX` · `$mybb->get_input()` · POST + `verify_post_check()` ·
alles mit Präfix `anschlusssuche`. Ausgabe `htmlspecialchars_uni()`, SQL `escape_string()`/`(int)`.
