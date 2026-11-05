# h4w-Anpassungen

Dieser Branch ist `main` **plus** die Anpassungen für das h4w-Forum. Regeln: Dev-Kit
`standards/conventions.md` §12 – Neuerungen zuerst auf `main`, dann `main` → `h4w` mergen,
nie umgekehrt. Deploy ins h4w-Test-/Live-Forum von diesem Branch.

## Anpassungen

| Bereich | Dateien | Zweck |
|---|---|---|
| h4w-Markup (Template-Set des h4w-Themes) | `inc/plugins/anschlusssuche/templates/anschlusssuche_form_fields.tpl`, `anschlusssuche_form_row.tpl` | Formularzeilen im Neuer-Thread-Formular als `div.h4w-post-item` statt `<tr><td>`. Templates `anschlusssuche_form_fields`, `anschlusssuche_form_row`. |
| h4w-Markup (Template-Set des h4w-Themes) | `inc/plugins/anschlusssuche/templates/anschlusssuche_forumdisplay_thread.tpl` | Thread-Zeile in der Forumsansicht mit h4w-Klassen (`h4w-mobile-invis`, `h4w-threadlist-lastpost-lastpost`) und der Variable `{$threadsolved}`. Template `anschlusssuche_forumdisplay_thread`. |
| UserCP-Menü in der RPG-Kategorie | `inc/plugins/anschlusssuche/core/Installer.php`, `inc/plugins/anschlusssuche.php` | Der UserCP-Menüpunkt „Anschluss-Angebote“ steht in der Kategorie **RPG** (Templates `usercp_nav_rpg_chara`, Gruppen-Templates). `install_usercp_link()` hängt ihn nicht mehr in `usercp_nav_misc` („Sonstiges“), sondern entfernt nur Zeilen älterer Versionen. Der Hook `usercp_menu` läuft mit Priorität 20 (vor dem RPG-Menü, Priorität 25), damit `{$lang->anschlusssuche_usercp_nav}` dort schon geladen ist. Link: `usercp.php?action=anschlusssuche_loose`. |
