# Anschluss-Suchmaschine – Implementation Plan

**Umsetzung:** folge `<dev-kit>/workflows/2-implement.md`. Schritte der Reihe
nach. Bei Unklarheit gezielt nachfragen, aber nicht umplanen. Schritt nach Erledigung auf
`- [x]` setzen. Alle Details (Tabellen, Hooks, Settings, Status-Logik, Feldtypen) stehen in
`PROJECT_CONTEXT.md` – dieser Plan verweist darauf statt sie zu wiederholen.

**Voraussetzungen:**
- Schritte 1–8 und 10–15 haben keine Abhängigkeiten.
- Schritt 9 (Relatives-Quelle der losen Gesuche) braucht die API-Erweiterung im Relatives-Repo
  (`PROJECT_CONTEXT.md` → „Abhängigkeiten“). Ist sie noch nicht da: Schritt 9 nur mit
  UserCP-Quelle umsetzen, Relatives-Teil hinter `function_exists('relatives_count_persons')`
  vorbereiten und mit dem Nutzer klären.
- Die Datenmigration ist **nicht** Teil dieses Plans (Migration 012 im Aufräum-Repo). Die
  öffentlichen Funktionen aus Schritt 4/5 müssen aber migrationstauglich sein.

**Testumgebung:** lokales Forum `http://h4w.localhost:8080` (MySQL 3307, PHP 7.4 + 8.4, siehe
`<aufraeum-repo>/docs/LOCAL_ENV.md`). Live-Forum und Live-DB werden
nicht angefasst.

---

## Schritt 1 – Grundgerüst & Zugriffsschutz
- [x] **Ziel:** Hauptdatei mit `info()`, `IN_MYBB`-Schutz, Autoload der Core-Klassen
      (`require_once` je Datei aus `inc/plugins/anschlusssuche/core/`, Klassen mit Präfix
      `Anschlusssuche_`), Registrierung **aller** Hooks aus `PROJECT_CONTEXT.md` → „Hooks“
      (außer Shop- und Relatives-Provider-Hook) mit leeren Handlern. Handler bleiben dünn und
      delegieren an die Services.
- **Dateien:** `inc/plugins/anschlusssuche.php`, leere Klassen-Skelette in `core/`
- **Vorlage:** `<dev-kit>/standards/boilerplate.md` §1, §2 · `conventions.md` §1–§3
- **Abnahme:** `php -l` auf allen Dateien (7.4 + 8.4); Plugin erscheint in der ACP-Pluginliste.

## Schritt 2 – Lifecycle: install / is_installed / uninstall
- [x] **Ziel:** `Anschlusssuche_Installer`: die 8 Tabellen aus `PROJECT_CONTEXT.md` anlegen/
      droppen, Settinggroup + alle Settings in `install()` (nicht in activate!),
      `is_installed()` über Tabelle `anschlusssuche_entries`. `uninstall()` entfernt Tabellen,
      Settings, Settinggroup, `rebuild_settings()`.
- **Dateien:** `inc/plugins/anschlusssuche.php`, `core/Installer.php`
- **Vorlage:** `boilerplate.md` §3, §4 · `conventions.md` §4 · `debugging.md` Punkt 21
- **Abnahme:** CLI-Lifecycle `install` → Tabellen + Settings da; `uninstall` → alle Zähler 0
  (`testing.md` §3).

## Schritt 3 – activate / deactivate: Templates, Stylesheet, Default-Felder, Settings-Nachrüstung
- [x] **Ziel:** `activate()`: Settings idempotent nachrüsten, alle Templates aus
      `templates/*.tpl` (Liste in `PROJECT_CONTEXT.md` → „Templates“) als sid -1 anlegen,
      Stylesheet `anschlusssuche.css` in alle Themes, JS über `headerinclude`-Variable (kein
      Core-Template-Edit nötig: `$anschlusssuche_headerinclude` an `$headerinclude` anhängen
      in `global_start`, nur auf relevanten Seiten). **Default-Felder** nur, wenn
      `anschlusssuche_fields` leer ist: je Typ eine `age_range` + Geschlecht (radio) +
      Beziehung (select) + Sonstiges (text) + Anschlussliste (`links`); generische
      Optionswerte, keine H4W-Texte. `deactivate()`: Templates + Stylesheet exakt entfernen,
      Felder/Daten bleiben.
      Feldblock im Formular: `activate()` fügt per str_replace den Marker
      `{$anschlusssuche_fields}` direkt vor `{$posticons}` in die Core-Templates `newthread`
      und `editpost` ein (alle Sets über `find_replace_templatesets`). `deactivate()` entfernt
      ihn exakt wieder. Fehlt `{$posticons}` in einem Set, wird der Marker vor
      `{$attachbox}` gesetzt.
- **Dateien:** `core/Installer.php`, `templates/*.tpl`, `assets/anschlusssuche.css`,
  `assets/anschlusssuche.js`
- **Vorlage:** `boilerplate.md` §5, §6, §8, §9
- **Abnahme:** CLI `install → activate → deactivate → uninstall` sauber, Templates/Stylesheet
  danach 0; Reaktivieren dupliziert keine Default-Felder.

## Schritt 4 – Repository & Feldtyp-System
- [x] **Ziel:**
      - `Anschlusssuche_Repository`: CRUD für alle Tabellen, Bulk-Loader
        `load_entries_by_tids(array)`, `load_values(array $entry_ids)`,
        `load_links(array $entry_ids)`, `get_forum_map()` (gecacht pro Request),
        `get_fields(string $type)` (gecacht, nur `active`).
      - `Anschlusssuche_FieldTypes`: Registry der 8 Feldtypen mit `render_input`, `validate`,
        `normalize`, `render_display`, `filter_sql` (Tabelle in `PROJECT_CONTEXT.md`).
        `links`: Zeilen mit leerem Namen verwerfen, Name/Rolle trimmen, uid nur übernehmen,
        wenn der Account existiert. `age_range`: von ≤ bis, 0–999, „bis“ leer = offen.
- **Dateien:** `core/Repository.php`, `core/FieldTypes.php`
- **Vorlage:** `conventions.md` §3, §4, §6 (alle SQL-Werte `(int)`/`escape_string`)
- **Abnahme:** CLI-Selbsttest (`testing.md` §4): je Feldtyp gültige/ungültige Eingaben,
  normalize-Ergebnisse, `filter_sql` enthält keine unescaped Werte.

## Schritt 5 – EntryService: Speichern, Status-Automatik, öffentliche API
- [x] **Ziel:** `anschlusssuche_entry_save()` (Signatur siehe `PROJECT_CONTEXT.md` →
      „Eigene Hooks / API“) → `EntryService::save()`: validiert über FieldTypes, schreibt
      `entries` + `values` + `links` (Werte/Links eines Eintrags komplett ersetzen),
      berechnet den Gruppen-Status nach der Status-Logik, feuert `anschlusssuche_entry_saved`.
      `effective_status(entry, forum_map)` liefert `done` bei Archivforum. Funktioniert ohne
      Session/`$mybb->user` (Migration!).
- **Dateien:** `core/EntryService.php`, `inc/plugins/anschlusssuche.php`
- **Vorlage:** `conventions.md` §7 (Eigene Daten anbieten)
- **Abnahme:** CLI-Selbsttest: Gruppe 3/3 → `taken`, 3/3 + open_end → `free`, manuell
  `reserved` bleibt bei Slot-Änderung; Speichern zweimal mit gleichen Daten = gleicher Stand.

## Schritt 6 – Thread erstellen / bearbeiten (ersetzt xThreads-Eingabe)
- [x] **Ziel:** Hooks `newthread_start`, `datahandler_post_validate_thread`,
      `datahandler_post_validate_post` (nur `first_post` beim Edit), `newthread_do_newthread_end`,
      `editpost_action_start`, `editpost_do_editpost_end`. Nur in aktiven Foren vom Typ
      single/group. Kernfelder: Status (single), Belegung gesamt/belegt/„offen“ (group),
      Altersspanne; danach die ACP-Felder nach `disporder`. Fehler erscheinen in MyBBs
      Fehlerliste, Eingaben bleiben bei Vorschau/Fehler erhalten (aus `get_input`).
      Löschen/Verschieben/Zusammenführen: Hooks `class_moderation_*` aus `PROJECT_CONTEXT.md`.
- **Dateien:** `inc/plugins/anschlusssuche.php`, `core/EntryService.php`, `core/Renderer.php`,
  `templates/anschlusssuche_newthread_fields.tpl`, `templates/anschlusssuche_field_input_*.tpl`
- **Vorlage:** `conventions.md` §5 · Muster `plotcp.php` (newthread_start/_end)
- **Abnahme:** Klicktest lokal: Thread mit/ohne Pflichtfelder, Vorschau, Bearbeiten des ersten
  Beitrags (Werte vorbefüllt), Bearbeiten einer Antwort (kein Feldblock), Verschieben in ein
  Archivforum → Status „erledigt“, Löschen → Zeilen weg.

## Schritt 7 – Threadliste (forumdisplay) + Filterleiste
- [x] **Ziel:** Core-Fakt (verifiziert, `forumdisplay.php:1039–1401`): Jede Zeile wird nach
      dem Hook `forumdisplay_thread_end` per
      `eval("\$threads .= \"".$templates->get("forumdisplay_thread")."\";")` angehängt.
      Deshalb:
      1. `forumdisplay_start`: Ist `fid` ein aktives Forum (Typ single/group, auch Archiv),
         wird `anschlusssuche_forumdisplay_thread` geladen und in
         `$templates->cache['forumdisplay_thread']` eingesetzt. Das ist dasselbe Prinzip wie
         xThreads-Präfixe und das `grouptemplates`-Plugin; nur für diese Seite, nichts wird
         gespeichert.
      2. `forumdisplay_before_thread`: Alle Einträge, Werte und Links der Seite gebündelt laden.
      3. `forumdisplay_thread_end`: Globale Zeilenvariablen setzen (`$anschlusssuche_status`,
         `$anschlusssuche_fieldline`, `$anschlusssuche_links`, `$anschlusssuche_rowclass`).
         Gibt es für den Thread keinen Eintrag (Altbestand), werden sie geleert; das Template
         rendert dann wie die Standardzeile.
      Das Template enthält alle Standard-Variablen der Core-Zeile (`$modbit`, `$rating`,
      `$lastposterlink`, `$attachment_count` …), Vorlage ist das Core-Template
      `forumdisplay_thread`. Wenn das H4W-Plugin `grouptemplates` dasselbe Template umleitet,
      ist die Hook-Reihenfolge zu prüfen und im Plan zu notieren.
      `forumdisplay_threadlist` gibt die Filterleiste (`show_filterbar`) als GET-Formular an
      `anschluss.php?type=…&fid=…` aus.
- **Dateien:** `inc/plugins/anschlusssuche.php`, `core/Renderer.php`,
  `templates/anschlusssuche_forumdisplay_thread.tpl`, `..._forumdisplay_filterbar.tpl`,
  `..._status.tpl`, `..._field_display.tpl`, `..._links_display.tpl`
- **Vorlage:** `conventions.md` §5 · Hook-Positionen `forumdisplay.php` (before_thread :935,
  thread :1041, threadlist :1550)
- **Abnahme:** Forum mit 20+ Threads: Query-Anzahl pro Seite konstant (≤ 4 eigene Queries,
  `?debug=1`); Darstellung Einzel/Gruppe/Archiv; Filterleiste führt zur Suche mit korrekten
  Parametern; curl-Check (`testing.md` §5).

## Schritt 8 – Header-Teaser + `header_add`-API
- [x] **Ziel:** `HeaderService` nach `PROJECT_CONTEXT.md` → „Header“: feste Einträge im
      Zeitraum, Zufallsauffüllung ohne `ORDER BY RAND()`, Ausgabe `$anschlusssuche_header`
      in `global_intermediate`; Provider-Hook `anschlusssuche_header_collect`.
      `anschlusssuche_header_add()` mit Validierung (Zeitraum, Ziel existiert, source).
      Abgelaufene feste Einträge bleiben in der Tabelle (Historie), werden nur nicht gezeigt.
- **Dateien:** `core/HeaderService.php`, `inc/plugins/anschlusssuche.php`,
  `templates/anschlusssuche_header*.tpl`
- **Vorlage:** `conventions.md` §5, §7
- **Abnahme:** CLI-Selbsttest: 2 feste + count 3 → 2 fest + 1 zufällig; 4 feste → 3;
  Zeitgrenzen (start inklusive, end exklusive). Lokal `{$anschlusssuche_header}` testweise in
  ein Header-Template setzen und ansehen.

## Schritt 9 – Lose Gesuche: UserCP, Relatives-Adapter, Block im Unterforum
- [x] **Ziel:**
      - UserCP (`modules/usercp.php`, Menüeintrag `boilerplate.md` §14): Liste der eigenen
        losen Gesuche, Anlegen/Bearbeiten/Löschen (POST + `verify_post_check`, Gruppen- und
        Limit-Prüfung aus Settings), Felder = Kernfelder (Titel, Status, Altersspanne) +
        ACP-Felder Typ `loose`.
      - `RelativesAdapter`: Erkennung (Plugin-Cache + `function_exists`), Mapping nach
        `PROJECT_CONTEXT.md` → „Abbildung Relatives-Person“, Escaping erst im Renderer,
        Link nur bei gültiger URL.
      - `forumdisplay_threadlist` in Foren vom Typ `loose`: Kartenblock beider Quellen über
        der Threadliste, eigene Pagination-Parameter (`lpage`), Filter-Kurzleiste.
- **Dateien:** `modules/usercp.php`, `core/RelativesAdapter.php`, `core/Renderer.php`,
  `templates/anschlusssuche_usercp_*.tpl`, `..._forumdisplay_loose_block.tpl`, `..._card.tpl`
- **Vorlage:** `boilerplate.md` §14, §15 · `conventions.md` §7 (Adapter + Sanitize-Pflicht)
- **Abnahme:** Relatives deaktiviert → Block zeigt nur UserCP-Gesuche, keine Warnung;
  aktiviert → gesuchte Personen erscheinen (lokal 35); fremdes Gesuch per manipulierter ID
  bearbeiten → `error_no_permission()`.

## Schritt 10 – Profil (ersetzt `h4wtweaks_profile_gesuche()`)
- [x] **Ziel:** `AccountResolver` (Root über `as_uid`, `field_exists`-Check), `member_profile_end`
      setzt `$anschlusssuche_profile`: Abschnitte Einzel / Gruppe / lose (eigene + Relatives
      über `owner_uids`) für alle Accounts des Spielers, optional „erledigt“
      (`profile_show_done`). Nur Threads, die der Betrachter sehen darf. Leerzustand-Text.
- **Dateien:** `core/AccountResolver.php`, `core/Renderer.php`,
  `templates/anschlusssuche_profile*.tpl`
- **Vorlage:** `conventions.md` §7 (EnhancedAccountSwitcher)
- **Abnahme:** Profil eines Haupt- **und** eines Zweitaccounts zeigt dieselbe Gesamtliste;
  Account ohne Gesuche zeigt Leertext; ohne Accountswitcher-Spalte kein SQL-Fehler.

## Schritt 11 – Suchseite `anschluss.php`
- [x] **Ziel:** Root-Controller nach `PROJECT_CONTEXT.md` → „Suche“: Typ-Tabs, Kern- und
      ACP-Feldfilter, Archiv-Checkbox, Pagination (`multipage()`), Gast-Setting,
      Forenrechte, Relatives-Mischung. Breadcrumb, `output_page()`.
- **Dateien:** `anschluss.php`, `core/SearchService.php`, `templates/anschlusssuche_search*.tpl`
- **Vorlage:** `boilerplate.md` §11
- **Abnahme:** curl als Gast (Setting an/aus), als Mitglied; Filter-Kombinationen
  (Alter-Überlappung 20–39 trifft 25–30 und 35–50, nicht 41–60); SQL-Injection-Versuch in
  jedem Parameter ohne Wirkung; Seite 2 behält Filter.

## Schritt 12 – Autocomplete Anschlussliste (xmlhttp)
- [x] **Ziel:** `xmlhttp`-Handler `action=anschlusssuche_users` (nur eingeloggt, Prefix-Suche
      auf `username`, max. 10, JSON), JS in `assets/anschlusssuche.js` für Zeilen
      hinzufügen/entfernen + Vorschläge; ohne JS bleibt das Feld als Name/Rolle-Eingabe nutzbar.
- **Dateien:** `inc/plugins/anschlusssuche.php`, `assets/anschlusssuche.js`,
  `templates/anschlusssuche_field_input_links.tpl`
- **Vorlage:** `conventions.md` §5 (`xmlhttp`)
- **Abnahme:** Gast bekommt Fehler/leere Antwort; Mitglied bekommt Treffer; Speichern mit
  gewähltem Account setzt `links.uid`.

## Schritt 13 – ACP-Tools-Modul
- [x] **Ziel:** Tabs Foren / Felder (Sub-Tabs je Typ) / Header nach `PROJECT_CONTEXT.md` →
      „ACP“; gemeinsamer Tab-Helper `anschlusssuche_acp_nav_tabs()`; jede Schreibaktion mit
      `verify_post_check`, `log_admin_action`; Feld löschen mit Bestätigung; Optionsumbenennung
      zieht Werte nach; max. ein `age_range`-Feld je Typ.
- **Dateien:** `admin/modules/tools/anschlusssuche.php`,
  `inc/languages/*/admin/anschlusssuche.lang.php`
- **Vorlage:** `boilerplate.md` §12 · globale CLAUDE.md „ACP Page Structure“
- **Abnahme:** `php -l`; Methoden-Abgleich gegen Referenzmodul; **manueller Klicktest an
  Nutzer übergeben** (`testing.md` §8).

## Schritt 14 – Sprachdateien (englisch + deutsch_du, Frontend + Admin getrennt)
- [x] **Ziel:** alle Keys definieren; Status-Texte, Typnamen, Leerzustände, Fehlermeldungen.
      Genderneutral, du-Form.
- **Dateien:** `inc/languages/english/anschlusssuche.lang.php` (+ `admin/`),
  `inc/languages/deutsch_du/anschlusssuche.lang.php` (+ `admin/`)
- **Vorlage:** `boilerplate.md` §13
- **Abnahme:** `<dev-kit>/scripts/lang_check.ps1` ohne fehlende Keys.

## Schritt 15 – Selbsttests finalisieren
- [x] **Ziel:** `tests/cli-plugin.php` (Lifecycle) + `tests/cli-selftest.php` (FieldTypes,
      Status-Logik, Header-Auswahl, Suche-Altersüberlappung, Adapter mit/ohne Relatives,
      `entry_save` idempotent).
- **Vorlage:** `testing.md` §3, §4
- **Abnahme:** Lifecycle grün, Selbsttest 0 failed, PHP 7.4 und 8.4.

---

## Schritt 16 (optional, späteres Release) – NewPoints-Shop-Anbindung
- [ ] **Ziel:** Ablauf aus `PROJECT_CONTEXT.md` → „NewPoints-Shop“: ACP-Tab Shop
      (Item-Mapping), Hooks `newpoints_shop_purchase_intermediate`/`_end`, `ShopBridge`,
      UserCP „Header-Gutscheine“ mit Einlösen. Nur nach ausdrücklicher Freigabe durch den
      Nutzer beginnen.
- **Dateien:** `core/ShopBridge.php`, `modules/usercp.php`, ACP-Modul, Templates
  `anschlusssuche_usercp_credits*.tpl`
- **Abnahme:** lokal Kauf eines gemappten Items → Gutschein; Einlösen → Header-Eintrag
  `source=shop`; Kollision mit `shop_max_concurrent` wird abgewiesen.

---

## Umsetzungsnotizen (Phase 2, 2026-10-27)

Abweichungen vom Plan, bewusst so umgesetzt:
- **Schritt 1/6:** Zusätzliche Core-Klasse `FormHandler.php` für Formulare, Validierung und
  Speichern (Thread + lose Gesuche). Die Hauptdatei bleibt dünn.
- **Schritt 2:** Tabellen nutzen `$db->build_create_table_collation()` statt fest `utf8`. So
  passt der Zeichensatz zum Board (utf8mb4).
- **Schritt 3:** `activate()` vergibt das ACP-Recht `tools/anschlusssuche` an alle Admins und
  Admin-Gruppen, die schon Werkzeuge-Rechte haben. `uninstall()` entfernt es wieder. Ohne diese
  Vergabe sperrt MyBB das Modul für Admins mit eigenem Rechte-Set.
- **Schritt 6:**
  - Validierungsfehler gehen gesammelt in **einen** DataHandler-Fehler, weil MyBB Fehler pro
    Code überschreibt.
  - Der Sprachschlüssel lautet `postdata_anschlusssuche_error_generic`, weil der
    PostDataHandler das Präfix `postdata_` voranstellt.
- **Schritt 7:**
  - Der Block für lose Gesuche und die Filterleiste hängen an `forumdisplay_end` (vor
    `$threadslist`), nicht an `forumdisplay_threadlist`. Der Grund: `forumdisplay_threadlist`
    feuert, bevor `$threadslist` gebaut wird.
  - Das Zeilen-Template wird **roh** aus `$templates->cache` kopiert. `get()` liefert einen
    escapten String, der sonst doppelt escaped würde.
  - Solange xThreads ein Template-Präfix für ein Forum gesetzt hat (H4W: 47/48), gewinnt das
    xThreads-Template. Erst Migration 012 entfernt die Präfixe.
- **Schritt 9:** Die Relatives-Quelle setzt Relatives 2.2.0 mit `relatives_count_persons()`
  voraus; die API-Erweiterung ist im Relatives-Repo umgesetzt (2026-10-27). Lokal getestet:
  - Block mit 35 Stammbaum-Personen + UserCP-Gesuchen, Seitenwechsel
  - Suche (Alter, Text; ACP-Feldfilter schließen Relatives-Personen aus)
  - Profil über alle Accounts, fester Header-Eintrag mit Relatives-Person
- **Schritt 12:** Kein eigener `xmlhttp`-Handler. Das Autocomplete nutzt den Core-Endpunkt
  `xmlhttp.php?action=get_users`, der dieselbe Prefix-Suche bietet.
- **Profil:** Die Variable `{$anschlusssuche_profile}` muss im Profil-Template platziert werden.
  Das Plugin editiert kein Profil-Template, bei H4W erledigt das Migration 012.

Getestet lokal (h4w.localhost:8080, PHP 7.4 + 8.4):
- Lifecycle install/deactivate/activate/uninstall auf beiden Versionen; nach Uninstall sind alle
  Zähler 0; Reaktivieren dupliziert keine Felder.
- `tests/cli-selftest.php`: 53 von 53 grün auf beiden Versionen (inkl. Relatives-Adapter).
- Threadliste im Archivforum: Status „erledigt“, Feldzeile und Anschlusszahl.
- Suchseite mit Altersüberlappung.
- Neues Thema: Pflicht- und Altersfehler, Speichern, Gruppenstatus-Automatik.
- Bearbeiten: Felder vorbefüllt, Speichern.
- Moderation: Verschieben ins Archiv ergibt „erledigt“, Löschen räumt Werte weg.
- UserCP lose Gesuche: CSRF-Schutz, Validierung, Speichern, XSS escaped.
- Profil über alle Accounts.
- Header-Rendering: fest + zufällig.
- ACP-Seiten und die Speicher-Aktionen für Feld und Header-Eintrag.

**Offen:** manueller Klicktest im ACP (`testing.md` §8) und Schritt 16 (Shop).
