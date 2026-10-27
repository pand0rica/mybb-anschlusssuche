# ToDo – Anschluss-Suchmaschine

## Gesuche aus xThreads übernehmen (beschlossen 2026-10-23, geplant 2026-10-27)

Im Zuge der H4W-Aufräumaktion (`F:\RPG\H4W Administratives\mybb aufräumen`, Phase 4) wird
xThreads abgelöst. Die Einzel- und Gruppengesuche wandern in dieses Plugin, das damit zur
zentralen Gesuche-Verwaltung wird. Am 2026-10-27 wurde das Plan-Paket neu geschrieben
(`PROJECT_CONTEXT.md`, `IMPLEMENTATION_PLAN.md`); die alte Annahme „keine Migration, keine
Tabellen“ ist ersetzt.

- [x] Planung des Datenmodells. Einzel-/Gruppengesuche bleiben echte Threads, ihre Felder
      liegen in eigenen Tabellen (`anschlusssuche_entries`/`_values`/`_links`). Lose Gesuche
      kommen aus Relatives (nur API) oder aus dem UserCP.
- [x] Designentscheidungen getroffen:
  - Block für lose Gesuche über der Threadliste
  - Status als Kernfeld mit Automatik
  - Forum 92 als Archivforum
  - Anschlussliste strukturiert
  - eigene Feldgruppe für lose Gesuche
  - Altersspanne mit Anzeigetext
  - Relatives-API erweitern
  - Shop-Ablauf mit Gutschein und Einlösen
- [ ] **Relatives-API erweitern** (Voraussetzung für Plan-Schritt 9), im Relatives-Repo als
      eigener kleiner Plan/Release:
  - `limit`/`offset`/`order`
  - Filter `owner_uids` und `ids`
  - `relatives_count_persons()`
  - `relationship_description` je Besitzer in der Rückgabe
  
  Details: `PROJECT_CONTEXT.md` → „Abhängigkeiten“.
- [ ] Phase 2: Umsetzung nach `IMPLEMENTATION_PLAN.md` (Startprompt: `start_prompt.md`)
- [ ] Migration 012 im Aufräum-Repo bauen, Grobbeschreibung in
      `F:\RPG\H4W Administratives\mybb aufräumen\docs\migration-012-gesuche.md`. Sie übernimmt
      Foren, Felder, Werte und Header-Settings, räumt xThreads-Felder und Templates auf,
      entfernt `gesuche.php` und den Gesuche-Teil von h4wtweaks.
- [ ] Danach im Forum: xThreads und phptpl komplett entfernen (wenn keine anderen Nutzer mehr
      übrig sind)
- [ ] Später (Plan-Schritt 16, optional): NewPoints-Shop „Header-Platz kaufen“
- [ ] Vor dem ersten Release: Screenshots unter `docs/screenshots/`

### Quelldaten (Stand lokale DB = Live-Dump 2026-10-22)

- Backup der xThreads-Tabellen:
  `F:\RPG\H4W Administratives\mybb aufräumen\backup\2026-10-22\db\xthreads_threadfields_2026-10-22.sql.gz`
  (`mybb_threadfields` = Felddefinitionen, `mybb_threadfields_data` = Werte je `tid`).
  Beim Go-Live läuft die Migration auf dem **dann aktuellen** Live-Dump.
- Felder Einzelgesuche: `gesuche_status`, `gesuche_alter`, `gesuche_altersspanne`,
  `gesuche_anschluss`, `gesuche_geschlecht`, `gesuche_beziehung`, `gesuche_sonstiges`,
  `gesuche_belongs`
- Felder Gruppengesuche: `gesucheg_art`, `gesucheg_altersspanne`, `gesucheg_geschlecht`,
  `gesucheg_gesamtzahl`, `gesucheg_vergeben`, `gesucheg_belongs`, `gesucheg_sonstiges`
- Foren mit Gesuchsdaten:
  - 47: 24 Einzelgesuche
  - 48: 14 Gruppengesuche
  - 92: 98 Einzel- und 43 Gruppengesuche (von 450 Threads)
  - 115: 1 Gruppengesuch
  - Die Header-Settings enthalten zusätzlich 110 (Charakterabgabe).
