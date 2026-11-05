# Anschluss-Suchmaschine – Start-Prompt für Phase 2 (Umsetzung)

Du setzt das MyBB-1.8-Plugin **Anschluss-Suchmaschine** (Codename `anschlusssuche`) um. Das
Umsetzungs-Paket aus Phase 1 liegt bereits vollständig im Repo:

  mybb-anschlusssuche

Lies in dieser Reihenfolge, BEVOR du irgendetwas tust:
1. `AGENTS.md` (dieses Repo) – Pointer aufs Dev-Kit, 5 goldene Regeln, Phasen-Zuordnung.
2. `PROJECT_CONTEXT.md` (dieses Repo) – Architektur, Tabellen, Settings, Hooks, Abhängigkeiten,
   „What NOT to change".
3. `IMPLEMENTATION_PLAN.md` (dieses Repo) – die nummerierten Arbeitsschritte.
4. Aus dem Dev-Kit bei Bedarf: `<dev-kit>/standards/boilerplate.md` (kopierfertige
   Code-Vorlagen) und `<dev-kit>/standards/conventions.md` (Naming, DB, Hooks,
   Security).

Folge danach exakt `<dev-kit>/workflows/2-implement.md`:
- Arbeite die Schritte aus `IMPLEMENTATION_PLAN.md` der Reihe nach ab und hake sie dort ab
  (`- [x]`), inklusive des jeweiligen Test-/Abnahmehinweises.
- Halte bei jedem Schritt die 5 goldenen Regeln ein: keine Core-Dateien ändern, `TABLE_PREFIX`
  statt `mybb_`, nur `$mybb->get_input()`, jede schreibende Aktion per POST +
  `verify_post_check()`, jede Funktion/jeder globale Bezeichner mit Präfix `anschlusssuche_`.
  Ausgabe mit `htmlspecialchars_uni()`, SQL mit `escape_string()`/`(int)`.
- Bei Unklarheiten gezielt nachfragen – **nicht neu planen**, Struktur und Umfang aus
  `IMPLEMENTATION_PLAN.md` stehen fest. Größere Abweichungen (neue Tabelle, anderer Hook) mit
  dem Nutzer klären und danach den Plan-Eintrag aktualisieren, statt sie stillschweigend
  umzusetzen.
- Sprachdateien: englisch + deutsch_du, Frontend- und Admin-Sets strikt getrennt.

**Abhängigkeiten:** Es gibt keine harten Abhängigkeiten, die Schritte 1–8 und 10–15 können
sofort starten.
- **Schritt 9** (lose Gesuche aus Relatives) setzt die API-Erweiterung im Relatives-Repo
  `mybb-relatives` voraus. Dazu gehören `limit`/`offset`/`order`,
  `owner_uids`, `ids`, `relatives_count_persons()` und `relationship_description` (siehe
  `PROJECT_CONTEXT.md` „Abhängigkeiten“). Frag vor Schritt 9 den Nutzer, ob diese Erweiterung
  umgesetzt ist. Falls nicht, setz nur die UserCP-Quelle um und bereite den Relatives-Teil
  hinter `function_exists('relatives_count_persons')` vor.
- **Nie** direkt auf `relatives_*`-Tabellen zugreifen.
- Die H4W-Datenmigration ist **nicht** Teil dieser Phase (Migration 012 im Aufräum-Repo). Die
  öffentlichen Funktionen `anschlusssuche_entry_save()`/`anschlusssuche_header_add()` müssen
  aber ohne Session nutzbar sein.
- Getestet wird im lokalen Forum `http://h4w.localhost:8080`, niemals live.

Schreib **keinen** Code, bevor du die drei Repo-Dateien oben gelesen hast. Antworten auf
Deutsch, Code/Bezeichner/Kommentare auf Englisch, PHP 7.4 + 8.x kompatibel, keine externen
Libraries.

## Abschluss
Wenn alle Schritte abgehakt sind: `php -l` sauber, CLI-Lifecycle-Test (install→uninstall→
install) beweist vollständigen Cleanup, kurzer Bericht was umgesetzt wurde / was noch offen ist
(z.B. manueller ACP-Klicktest) / bekannte Risiken. Begleitdateien (README, PLUGIN_POST.html,
CHANGELOG …) sind **nicht** Teil dieser Phase – das macht Phase 3 (Review), ausgelöst mit
„reviewe das Projekt".
