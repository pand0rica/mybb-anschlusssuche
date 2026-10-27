# Anschluss-Suchmaschine – Project Context

> Stand: Neuplanung 2026-10-27 (ersetzt das Plan-Paket vom 09.09., das von „reine Suchmaske über
> Relatives, keine Tabellen, keine Migration“ ausging – diese Annahmen gelten **nicht** mehr).

## Identität
- **Name:** Anschluss-Suchmaschine
- **Codename / Dateiname:** `anschlusssuche` (Präfix: `anschlusssuche`)
- **Version:** 1.0.0
- **Autor:** pand0rica
- **Kurzbeschreibung:** Zentrale Gesuche-Verwaltung für RPG-Foren: Einzel-, Gruppen- und lose
  Gesuche mit im ACP konfigurierbaren Feldern, aufgewertete Threadliste, Profil-Übersicht über
  alle Accounts, Header-Teaser (fix + zufällig) und eine Such-/Filterseite über alle Gesuche.
- **MyBB:** 1.8.x (Zielforum 1.8.41) · **PHP:** 7.4 + 8.x

## Zweck & Zielgruppe
RPG-Charakterforen sammeln „Gesuche“ (gesuchte Mitspieler-Charaktere). Bisher wurde das bei H4W
mit xThreads-Feldern, phptpl-Templates, einem Header-Plugin und Profil-Code in einem
Tweaks-Plugin zusammengebaut. Dieses Plugin ersetzt all das durch **eine** saubere Lösung:
Gesuche bleiben echte MyBB-Threads in frei wählbaren Unterforen, bekommen aber strukturierte,
durchsuchbare Felder; dazu kommen „lose Gesuche“ ohne Thread (aus dem Relatives-Plugin und aus
dem UserCP). Das Plugin ist **öffentlich** (GitHub) und für beliebige Foren nutzbar – nichts
H4W-Spezifisches ist hartcodiert; Foren-IDs, Felder und Texte kommen aus dem ACP, H4W-Daten
kommen über die externe Migration 012 (siehe unten).

## Begriffe
| Begriff | Bedeutung |
|---|---|
| **Gesuchstyp** | `single` (Einzelgesuch), `group` (Gruppengesuch), `loose` (loses Gesuch). Fest im Code. |
| **Aktives Forum** | Unterforum mit zugeordnetem Gesuchstyp (Tabelle `anschlusssuche_forums`). |
| **Archivforum** | Aktives Forum mit `is_archive = 1`; Gesuche darin gelten als „erledigt“. |
| **Eintrag (entry)** | Ein Gesuch – entweder ein Thread (`source = thread`) oder ein loses UserCP-Gesuch (`source = loose`). |
| **Relatives-Person** | Im Relatives-Plugin als „gesucht“ markierte Person – wird nur gelesen, nie kopiert. |

## Architektur
- **Variante:** Klassen-Core-Split (Begründung: 8 Tabellen, Feldtyp-System mit Validierung,
  ACP mit mehreren Ansichten, AJAX-Autocomplete, Adapter zu Relatives und NewPoints –
  `conventions.md` §2).
- **Dateilayout:**
  ```
  anschluss.php                                   # Frontend-Root: Such-/Filterseite (Name bewusst
                                                  # wie Altprototyp, ersetzt dessen Datei)
  inc/plugins/anschlusssuche.php                  # info, Lifecycle, Hook-Registrierung, dünne Handler
  inc/plugins/anschlusssuche/
      core/
          Installer.php        # Tabellen, Settings, Templates, Stylesheet (install/activate/…)
          Repository.php       # alle DB-Zugriffe auf eigene Tabellen (CRUD, Bulk-Loads)
          FieldTypes.php       # Feldtyp-Registry: render_input / validate / normalize / render_display / filter_sql
          EntryService.php     # Anlegen/Aktualisieren von Einträgen, Status-Automatik
          Renderer.php         # Template-Ausgabe: Threadlisten-Zeile, Karten, Profil, Header
          SearchService.php    # Suche über Einträge + Relatives, Pagination
          HeaderService.php    # Auswahl fix/zufällig, header_add-API
          RelativesAdapter.php # einziger Zugriff auf Relatives (öffentliche API)
          AccountResolver.php  # Accountswitcher: alle Accounts eines Spielers
          ShopBridge.php       # Gutschein-Logik (vorbereitet, Hook später)
      modules/
          usercp.php           # UserCP: lose Gesuche + Gutscheine
      templates/*.tpl
      assets/anschlusssuche.css
      assets/anschlusssuche.js # Autocomplete Anschlussliste, Altersspanne-UI
  admin/modules/tools/anschlusssuche.php           # ACP-Tools-Modul
  inc/languages/english/anschlusssuche.lang.php (+ admin/anschlusssuche.lang.php)
  inc/languages/deutsch_du/anschlusssuche.lang.php (+ admin/anschlusssuche.lang.php)
  ```

## Datenbank-Tabellen
Konventionen `conventions.md` §4: InnoDB, utf8 (wie Kit), `INT(11)` Unix-Zeitstempel, Bools
`TINYINT(1)`.

| Tabelle (`TABLE_PREFIX .`) | Zweck | Wichtige Spalten |
|---|---|---|
| `anschlusssuche_forums` | Aktive Unterforen + Typ | `fid` INT PK, `type` VARCHAR(10) (`single`/`group`/`loose`), `is_archive` TINYINT(1), `show_filterbar` TINYINT(1) DEFAULT 1, `show_loose_block` TINYINT(1) DEFAULT 1 (nur Typ loose relevant) |
| `anschlusssuche_fields` | Felddefinitionen je Typ | `id` PK, `type` (single/group/loose), `fkey` VARCHAR(50) (UNIQUE mit type), `title` VARCHAR(100), `description` TEXT, `fieldtype` VARCHAR(20), `required` TINYINT(1), `disporder` INT, `options` TEXT (eine Option je Zeile), `icon` VARCHAR(100) (CSS-Klasse, z.B. `fas fa-users`), `show_list`/`show_profile`/`show_header`/`filterable`/`active` TINYINT(1) |
| `anschlusssuche_entries` | Ein Datensatz je Gesuch | `id` PK, `source` VARCHAR(10) (`thread`/`loose`), `tid` INT NULL (UNIQUE), `fid` INT (Thread-Forum, 0 bei loose), `uid` INT (Autor/Besitzer), `type`, `title` VARCHAR(200) (nur loose; bei Threads = subject), `status` VARCHAR(10) (`free`/`reserved`/`taken`), `status_manual` TINYINT(1), `slots_total` INT, `slots_taken` INT, `slots_open_end` TINYINT(1), `age_min` INT NULL, `age_max` INT NULL, `age_text` VARCHAR(100), `created_at`, `updated_at` · KEY (`type`,`status`), KEY `uid`, KEY `fid` |
| `anschlusssuche_values` | Werte der ACP-Felder je Eintrag | `entry_id`, `field_id` (PK zusammen), `value` TEXT, `num_min` INT NULL, `num_max` INT NULL · KEY (`field_id`,`value`(100)) |
| `anschlusssuche_links` | Anschlussliste (Feldtyp `links`) | `id` PK, `entry_id`, `field_id`, `name` VARCHAR(120), `role` VARCHAR(120), `uid` INT NULL, `disporder` INT · KEY `entry_id`, KEY `uid` |
| `anschlusssuche_header` | Feste Header-Einträge | `id` PK, `entry_id` INT NULL, `relatives_person_id` INT NULL, `source` VARCHAR(10) (`acp`/`shop`), `owner_uid` INT, `start_at` INT, `end_at` INT, `note` VARCHAR(255), `credit_id` INT NULL, `created_by` INT, `created_at` · KEY (`start_at`,`end_at`) |
| `anschlusssuche_credits` | Shop-Gutscheine (vorbereitet) | `id` PK, `uid`, `shop_item_id` INT, `user_item_id` INT NULL, `days` INT, `created_at`, `redeemed_at` INT NULL, `header_id` INT NULL · KEY `uid` |
| `anschlusssuche_shop_items` | Mapping Shop-Item → Header-Dauer (vorbereitet) | `shop_item_id` INT PK, `days` INT |

**Kernfelder vs. ACP-Felder:** Status, Belegung (`slots_*`), Altersspanne (`age_*`) und Titel
sind **Kernfelder** in `entries` (feste Logik, immer filterbar, im ACP nur Texte/Sichtbarkeit).
Alles andere (Geschlecht, Zugehörigkeit, Beziehung, Gesuchsart, Sonstiges, Anschlüsse …) sind
**ACP-Felder** in `fields`/`values`/`links`. Altersspanne ist dennoch als Feldtyp `age_range`
registriert, damit Reihenfolge/Titel/Pflicht/Anzeige im ACP gepflegt werden können – ein Feld
dieses Typs pro Gesuchstyp speichert in `entries.age_*` (max. eins pro Typ, ACP prüft das).

### Feldtypen (`FieldTypes.php`)
| fieldtype | Eingabe | Speicherung | Filter |
|---|---|---|---|
| `text` | input | `value` | LIKE |
| `textarea` | textarea | `value` | LIKE |
| `select` | select aus `options` | `value` | exakt |
| `radio` | radios aus `options` | `value` | exakt |
| `multi` | Checkboxen aus `options` | `value` = Werte mit `\n` getrennt | `FIND_IN_SET`-ähnlich (je Option) |
| `number` | input number | `value` + `num_min` | von–bis |
| `age_range` | zwei Zahlen (von, bis; bis leer = „und älter“) + optional Anzeigetext | `entries.age_min/age_max/age_text` | Überlappung mit gesuchter Spanne |
| `links` | dynamische Zeilen Name + Rolle, Name mit Username-Autocomplete | `anschlusssuche_links` | Name LIKE / uid |

Jeder Typ liefert: `render_input()`, `validate()`, `normalize()`, `render_display()`,
`filter_sql()`. Neue Typen nur hier ergänzen.

### Status-Logik (`EntryService`)
- Werte: `free` / `reserved` / `taken`; **effektiver** Status `done`, wenn das Thread-Forum ein
  Archivforum ist (zur Laufzeit berechnet, nicht gespeichert).
- Einzel/lose: User setzt den Status selbst.
- Gruppe: `slots_total`/`slots_taken`; ist `status_manual = 0`, wird automatisch berechnet:
  `taken`, wenn `slots_taken >= slots_total` und nicht `slots_open_end`, sonst `free`
  (Anzeige „x/y“, bei `slots_open_end` „x/y+“). User kann manuell überschreiben
  (`status_manual = 1`, z.B. „reserviert“).
- Texte über Sprachdatei, Farben über Stylesheet-Klassen `.anschlusssuche-status--free|reserved|taken|done`
  (im ACP über die Theme-Stylesheets änderbar – keine Inline-Farben).

## Settings (settinggroup `anschlusssuche`)
Angelegt in `install()`, nachgerüstet idempotent in `activate()` (`boilerplate.md` §4).

| Setting | optionscode | Zweck |
|---|---|---|
| `anschlusssuche_enabled` | onoff (1) | Master-Schalter |
| `anschlusssuche_search_guests` | yesno (1) | Suchseite auch für Gäste |
| `anschlusssuche_results_per_page` | numeric (20) | Pagination Suchseite |
| `anschlusssuche_loose_per_page` | numeric (12) | Pagination Block lose Gesuche |
| `anschlusssuche_loose_groups` | groupselect (alle registrierten) | Wer lose Gesuche im UserCP anlegen darf |
| `anschlusssuche_loose_max_per_user` | numeric (0 = unbegrenzt) | Limit lose Gesuche je Account |
| `anschlusssuche_relatives_enabled` | yesno (1) | Relatives-„gesucht“-Personen als lose Gesuche einbeziehen (nur wirksam, wenn Relatives aktiv + API-Version passt) |
| `anschlusssuche_relatives_gender_map` | textarea (`0=männlich\n1=weiblich\n2=divers`) | Abbildung Relatives-Geschlechtscode → Anzeigetext/Filterwert |
| `anschlusssuche_profile_enabled` | yesno (1) | Gesuche-Block im Profil |
| `anschlusssuche_profile_show_done` | yesno (0) | Erledigte Gesuche (Archiv) im Profil als eigener Abschnitt |
| `anschlusssuche_header_enabled` | yesno (1) | Header-Teaser |
| `anschlusssuche_header_count` | numeric (3) | Anzahl Einträge im Header (fix + zufällig) |
| `anschlusssuche_header_forums` | forumselect | Quellforen für Zufallseinträge (dürfen auch nicht-aktive Foren sein, z.B. „Charakterabgabe“; dann nur Titel ohne Felder) |
| `anschlusssuche_header_loose` | yesno (0) | Lose Gesuche in den Zufallspool aufnehmen |
| `anschlusssuche_header_free_only` | yesno (1) | Zufallspool nur Status `free` |
| `anschlusssuche_shop_enabled` | yesno (0) | Shop-Gutscheine (vorbereitet; ohne NewPoints-Shop wirkungslos) |
| `anschlusssuche_shop_max_concurrent` | numeric (0 = `header_count`) | Max. gleichzeitige Kauf-Einträge im Header |

## Templates (Präfix `anschlusssuche_`, global sid -1, aus `.tpl`)
`forumdisplay_thread` (Zeile, ersetzt die Standardzeile in aktiven Foren), `forumdisplay_filterbar`,
`forumdisplay_loose_block`, `card` (lose Gesuche / Suchtreffer), `field_display`, `status`,
`links_display`, `newthread_fields` (Formularblock), `field_input_*` je Feldtyp,
`profile`, `profile_section`, `profile_item`, `header`, `header_item`, `search`, `search_form`,
`search_results`, `usercp_loose_list`, `usercp_loose_edit`, `usercp_credits`, `usercp_nav`.
Ausgangslayout der Threadliste = heutiges H4W-Bild: Status-Badge vor dem Titel, darunter eine
Icon-Zeile der Felder mit `show_list = 1` (Icon aus `fields.icon`), „Anschluss an x Charaktere“
(exakte Zahl aus `links`), Autor-Profillink. Gruppen: Badge `x/y`.

## Hooks
| Hook | Handler | Zweck |
|---|---|---|
| `global_start` | `anschlusssuche_handle_global_start` | Templates je `THIS_SCRIPT` vorladen (forumdisplay, newthread, editpost, member, usercp, anschluss.php) |
| `global_intermediate` | `anschlusssuche_handle_header` | Header-Teaser in `$anschlusssuche_header` |
| `newthread_start` | `anschlusssuche_handle_newthread_form` | Feldblock `{$anschlusssuche_fields}` (nur aktive Foren, nicht Typ loose ohne Felder) |
| `datahandler_post_validate_thread` | `anschlusssuche_handle_validate_thread` | Pflicht/Format prüfen bei neuem Thread → `$this->set_error()` |
| `datahandler_post_validate_post` | `anschlusssuche_handle_validate_post` | dto. beim Bearbeiten, nur wenn `first_post` und Aktion = editpost |
| `newthread_do_newthread_end` | `anschlusssuche_handle_newthread_save` | Eintrag + Werte speichern (`$tid` global) |
| `editpost_action_start` | `anschlusssuche_handle_editpost_form` | Feldblock vorbefüllt (nur erster Beitrag) |
| `editpost_do_editpost_end` | `anschlusssuche_handle_editpost_save` | Werte aktualisieren (nur erster Beitrag); `datahandler_post_update_thread` ist ungeeignet (feuert nur bei Subject/Icon-Änderung) |
| `forumdisplay_start` | `anschlusssuche_handle_forumdisplay_start` | In aktiven Foren `$templates->cache['forumdisplay_thread']` durch `anschlusssuche_forumdisplay_thread` ersetzen, nur zur Laufzeit |
| `forumdisplay_before_thread` | `anschlusssuche_handle_forumdisplay_bulk` | Einträge/Werte/Links aller `$args['tids']` mit wenigen Queries laden |
| `forumdisplay_thread_end` | `anschlusssuche_handle_forumdisplay_thread` | Zeilenvariablen (`$anschlusssuche_status`, `_fieldline`, `_links`, `_rowclass`) für die aktuelle Zeile setzen |
| `forumdisplay_threadlist` | `anschlusssuche_handle_forumdisplay_threadlist` | Filterleiste (→ GET an `anschluss.php`) und bei Typ loose der Kartenblock über der Liste |
| `class_moderation_delete_thread` | `anschlusssuche_handle_delete_thread` | Eintrag + Werte + Links + Header-Einträge löschen |
| `class_moderation_move_simple`, `class_moderation_move_thread_redirect`, `class_moderation_move_threads` | `anschlusssuche_handle_move_*` | `entries.fid` nachziehen |
| `class_moderation_merge_threads` | `anschlusssuche_handle_merge_threads` | Eintrag des weggefallenen Threads löschen |
| `member_profile_end` | `anschlusssuche_handle_profile` | `{$anschlusssuche_profile}` (Einzel, Gruppe, lose inkl. Relatives; alle Accounts) |
| `usercp_menu` (Prio 40) | `anschlusssuche_handle_usercp_menu` | Menüpunkt „Lose Gesuche“ (+ „Header-Gutscheine“ wenn Shop aktiv) – `boilerplate.md` §14 |
| `usercp_start` | `anschlusssuche_handle_usercp` | Aktionen `anschlusssuche_loose`, `anschlusssuche_loose_edit`, `do_anschlusssuche_loose_*`, `anschlusssuche_credits`, `do_anschlusssuche_redeem` |
| `xmlhttp` | `anschlusssuche_handle_xmlhttp` | `action=anschlusssuche_users` – Username-Autocomplete (Login-Pflicht, max. 10 Treffer) |
| `datahandler_user_delete_start` | `anschlusssuche_handle_user_delete` | lose Gesuche + Gutscheine des Users löschen, `links.uid` → NULL (Name bleibt) |
| `relatives_profile_wanted_cards` | `anschlusssuche_handle_relatives_cards` | **nicht** registrieren in 1.0 (Profil zeigt bereits alles); als Option dokumentiert |
| `admin_tools_menu` / `admin_tools_action_handler` / `admin_tools_permissions` | `anschlusssuche_admin_*` | ACP-Tools-Modul |
| *(später)* `newpoints_shop_purchase_end` | `anschlusssuche_handle_shop_purchase` | Gutschein anlegen – siehe „NewPoints-Shop“ |

## Eigene Hooks / API
Öffentliche Funktionen (stabil, für andere Plugins und Migration 012):
- `anschlusssuche_header_add(array $data): int` – legt einen festen Header-Eintrag an.
  Keys: `entry_id` **oder** `relatives_person_id`, `source` (`acp`|`shop`), `owner_uid`,
  `start_at`, `end_at`, `note`, `credit_id`, `created_by`. Validiert Zeitraum + Existenz,
  gibt neue ID zurück (0 bei Fehler). Kein Escaping-Verlass beim Aufrufer.
- `anschlusssuche_entry_save(array $entry, array $values, array $links): int` – Anlegen/
  Aktualisieren eines Eintrags inkl. Werte (wird intern und von der Migration genutzt; Werte
  werden normalisiert, aber **nicht** HTML-escaped gespeichert).
- `anschlusssuche_get_entries_for_uids(array $uids, array $opts = []): array` – für Profil-/
  Fremdplugins.
- Eigene Hooks: `anschlusssuche_entry_saved` (`$args = ['entry_id','is_new']`),
  `anschlusssuche_header_collect` (Provider-Hook, `$args = ['items' => &$items]` vor dem
  Rendern – z.B. für spätere Shop-Highlights).

## Abhängigkeiten
- **Keine harten Abhängigkeiten.** Das Plugin läuft ohne weitere Plugins.
- **Relatives** (`F:\RPG\Mybb Plugins - eigene\relatives`, optional): Quelle 1 der losen
  Gesuche. Erkennung über Plugin-Cache **und** `function_exists('relatives_count_persons')`
  (= Mindestversion mit API-Erweiterung, siehe unten). Fehlt eins davon, werden nur die eigenen
  losen Gesuche gezeigt – kein Fehler. Zugriff **ausschließlich** über `RelativesAdapter` →
  öffentliche Relatives-API; nie `relatives_*`-Tabellen.
  - **Benötigte API-Erweiterung im Relatives-Repo (Voraussetzung für Schritt 9):**
    - `relatives_search_persons()` neue Filter-Keys `limit`, `offset`, `order`
      (`name`|`age`|`newest`), `owner_uids` (int[]), `ids` (int[]).
    - neue Funktion `relatives_count_persons(array $filters): int` (gleiche Filter, ohne
      limit/offset).
    - Rückgabe je Person zusätzlich: `owners[].relationship_description` (Gesuchstext je
      Besitzer), `avatar` (schon vorhanden, bestätigen).
  - **Abbildung Relatives-Person → loses Gesuch (fest, Kernfelder):** Titel = `name`;
    Alter = `age` (age_min = age_max); Geschlecht = `gender` über
    `anschlusssuche_relatives_gender_map`; Beziehung = `owners[0].computed_label`;
    Beschreibung = `owners[0].relationship_description` ?: `description`; Link =
    `wanted_thread_link` (über eigene URL-Prüfung); Besitzer = `owners[].owner_uid`;
    Status immer `free`. Zusätzliche ACP-Felder der losen Gesuche bleiben bei ihnen leer.
    Bearbeiten nur im Relatives-Plugin (Karte verlinkt dorthin).
- **EnhancedAccountSwitcher** (optional): `AccountResolver` ermittelt den Root-Account
  (`as_uid` des Users, sonst er selbst) und dann `uid = root OR as_uid = root` – funktioniert
  auch, wenn das Profil eines Zweitaccounts angesehen wird. Ohne Accountswitcher (Spalte fehlt →
  `field_exists`-Check) nur der eine Account.
- **NewPoints 3.x + Shop 3.x** (optional, später) – siehe nächster Abschnitt.

## NewPoints-Shop (vorbereitet, in 1.0 nicht angebunden)
Fakten (NewPoints 3.1.6 / Shop 3.0.1): Shop-Items haben **kein** Typ-/Plugin-Feld und es gibt
keine „Benutzen“-Aktion. Der Shop feuert über `Newpoints\Core\run_hooks()` normale MyBB-Hooks
mit Präfix `newpoints_`, u.a. `newpoints_shop_purchase_intermediate` (`$args['errors']` per
Referenz – Kauf verhinderbar) und `newpoints_shop_purchase_end` (`$args['item_data']`, **ohne**
neue `user_item_id`). Ein normales MyBB-Plugin kann sich mit `$plugins->add_hook()` anhängen.
Geplanter Ablauf („Gutschein + Einlösen“):
1. ACP-Tab „Shop“: Tabelle `anschlusssuche_shop_items` ordnet Shop-Item-IDs eine Dauer (Tage) zu.
2. `newpoints_shop_purchase_intermediate`: wenn Item gemappt und Setting aus → Kauf mit Fehler
   abbrechen.
3. `newpoints_shop_purchase_end`: Item gemappt → `ShopBridge::create_credit()` legt einen
   Gutschein (`credits`) an.
4. UserCP „Header-Gutscheine“: User wählt eigenes Gesuch (Einträge aller eigenen Accounts) +
   Startdatum; Prüfung gegen `shop_max_concurrent` im Zeitraum → `anschlusssuche_header_add()`
   mit `source = shop`, `credit_id`.
In 1.0 werden Tabellen, `ShopBridge` (ohne Hook-Registrierung), ACP-Tab und UserCP-Seite
**noch nicht** gebaut – nur Tabellen + `header_add()` mit `source`/`credit_id`. Der spätere
Ausbau ist als eigener Plan-Schritt (15, optional) beschrieben.

## Header (ersetzt das H4W-Plugin `gesuche.php`)
- Auswahl: alle festen Einträge mit `start_at <= now < end_at` (sortiert nach Start, gedeckelt
  auf `header_count`), Rest zufällig aus `header_forums` (sichtbar, nicht verschoben, nicht
  Archiv, ggf. nur `free`) + optional lose Gesuche; keine Dubletten.
- Zufall ohne `ORDER BY RAND()` über die ganze Tabelle: IDs der Kandidaten laden (klein),
  in PHP `array_rand`.
- Ausgabe in **`$anschlusssuche_header`** (Template-Variable `{$anschlusssuche_header}`).
  Felder mit `show_header = 1` werden angezeigt. Kein Debug-HTML.
- CSS-Klassen der Ausgabe: `.anschlusssuche-header`, `.anschlusssuche-header__item`,
  `.anschlusssuche-header__fields`, `.anschlusssuche-header__field` (Themes passen ihr CSS an;
  die H4W-Migration ersetzt `{$gesuche}` in den Header-Templates).

## Suche (`anschluss.php`)
- Typ-Tabs (alle / Einzel / Gruppe / lose), Kernfilter Status, Alter (von–bis, Überlappung),
  Freitext (Titel + Text-Felder), plus alle ACP-Felder mit `filterable = 1` des gewählten Typs.
- Archiv standardmäßig ausgeschlossen (Checkbox „erledigte einbeziehen“).
- Relatives-Personen: Alter/Geschlecht/Freitext werden an die Relatives-API durchgereicht;
  ACP-Feldfilter gelten nur für eigene Einträge (Relatives-Personen fallen bei gesetztem
  ACP-Filter heraus). Zusammenführen + Pagination: eigene Einträge per SQL, Relatives per
  `count` + `limit/offset`, gemischte Seite über „erst eigene, dann Relatives“ nach Sortierung.
- Sichtbarkeit: nur Threads aus Foren, die der Betrachter lesen darf
  (`get_unviewable_forums()` / `forum_permissions()`), nur `visible = 1`.
- GET-Formular (bookmarkbar), alle Eingaben über `get_input()`; Filterleiste im Forum schickt
  dieselben Parameter.

## Berechtigungen
- Felder ausfüllen/ändern: wer den Thread erstellen bzw. den ersten Beitrag bearbeiten darf
  (MyBB-Rechte).
- Lose Gesuche: Gruppen aus `anschlusssuche_loose_groups`, nur eigene (bzw. Moderatoren mit
  `canmodcp` über das ACP). Alle Schreibaktionen POST + `verify_post_check()`.
- Suchseite: eingeloggt immer, Gäste per Setting; Forenleserechte gelten.
- Autocomplete: nur eingeloggt.
- ACP: Admin-Berechtigung `tools/anschlusssuche`.

## ACP (`admin/modules/tools/anschlusssuche.php`, Tabs via `output_nav_tabs()`)
1. **Foren** – aktive Unterforen hinzufügen/entfernen, Typ, Archiv-Flag, Filterleiste an/aus.
2. **Felder** – je Typ (Sub-Tabs Einzel/Gruppe/lose): Liste mit Reihenfolge, Hinzufügen/
   Bearbeiten (Titel, Schlüssel, Beschreibung, Feldtyp, Pflicht, Optionen, Icon, Anzeige
   Liste/Profil/Header, filterbar, aktiv), Löschen (mit Bestätigung; löscht Werte).
   Optionen umbenennen ändert vorhandene Werte mit (Werte werden per alter → neuer Text ersetzt).
3. **Header** – feste Einträge (Gesuch per Thread-ID/Titel-Suche, Zeitraum, Notiz, Herkunft
   anzeigen; Kauf-Einträge nur lesbar + löschbar).
4. *(später)* **Shop** – Item-Mapping.

## Migration (extern – Migration 012 im Aufräum-Repo)
Das Plugin enthält **kein** Migrationstool. Die H4W-Datenübernahme (xThreads-Felder, Werte,
Header-Settings, Template-Aufräumen) läuft als idempotente Migration
`F:\RPG\H4W Administratives\mybb aufräumen\migrations\012_anschlusssuche.php`, beschrieben in
`docs\migration-012-gesuche.md` dort. Sie nutzt ausschließlich die öffentlichen Funktionen
`anschlusssuche_entry_save()` / `anschlusssuche_header_add()` sowie `Installer`-Teile
(Tabellen, Settings, Templates, Stylesheet) – kein vollständiger `_activate()`-Lauf nötig.
Deshalb müssen diese Funktionen ohne geladene Hooks/Session nutzbar sein (kein Zugriff auf
`$mybb->user` darin; `created_by` wird übergeben).

## What NOT to change
- Root-Datei heißt `anschluss.php` (Kontinuität alter Links) – nicht umbenennen.
- Kein direkter Zugriff auf `relatives_*`-Tabellen, auch nicht lesend und nicht „aus
  Performance-Gründen“ – stattdessen die Relatives-API erweitern.
- Keine H4W-Foren-IDs, Feldnamen oder Texte im Plugin-Code; Default-Felder, die `activate()`
  bei leerer Feldtabelle anlegt, sind generisch (Geschlecht, Beziehung, Sonstiges,
  Anschlussliste, Altersspanne).
- Status ist Kernfeld (Spalte), keine ACP-Feldzeile – nicht in `fields` verschieben.
- „Erledigt“ wird nie gespeichert, sondern aus dem Archiv-Flag des Forums abgeleitet.
- Die Threadliste wird **nicht** serverseitig gefiltert (kein Core-Hook vor der Count-Query in
  `forumdisplay.php`) – Filter gehen bewusst an `anschluss.php`.
- Werte werden roh gespeichert und **nur beim Rendern** escaped (`htmlspecialchars_uni`).
- Die Threadlisten-Zeile wird über `$templates->cache['forumdisplay_thread']` umgeleitet, und
  zwar nur zur Laufzeit in aktiven Foren. Das Core-Template wird nicht editiert. Die einzigen
  Core-Template-Edits sind die Marker `{$anschlusssuche_fields}` in `newthread`/`editpost`.
- `anschlusssuche_header_add()` / `anschlusssuche_entry_save()` behalten ihre Signatur
  (Migration 012 und der spätere Shop-Hook hängen daran).
