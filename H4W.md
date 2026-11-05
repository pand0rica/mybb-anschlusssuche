# h4w-Anpassungen

Dieser Branch ist `main` **plus** die Anpassungen für das h4w-Forum. Regeln: Dev-Kit
`standards/conventions.md` §12 – Neuerungen zuerst auf `main`, dann `main` → `h4w` mergen,
nie umgekehrt. Deploy ins h4w-Test-/Live-Forum von diesem Branch.

## Anpassungen

| Bereich | Dateien | Zweck |
|---|---|---|
| h4w-Markup (Template-Set des h4w-Themes) | `inc/plugins/anschlusssuche/templates/anschlusssuche_form_fields.tpl`, `anschlusssuche_form_row.tpl` | Formularzeilen im Neuer-Thread-Formular als `div.h4w-post-item` statt `<tr><td>`. Templates `anschlusssuche_form_fields`, `anschlusssuche_form_row`. |
