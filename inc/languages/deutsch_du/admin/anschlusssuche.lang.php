<?php
/**
 * Anschluss-Suchmaschine – ACP-Sprachdatei (Deutsch, du).
 */

$l['anschlusssuche'] = 'Anschluss-Suchmaschine';
$l['anschlusssuche_can_manage'] = 'Darf Gesuche verwalten?';
$l['anschlusssuche_not_installed'] = 'Das Plugin Anschluss-Suchmaschine ist nicht installiert.';

// Tabs
$l['anschlusssuche_tab_forums'] = 'Foren';
$l['anschlusssuche_tab_desc_forums'] = 'Wähle die Foren, in denen das Plugin aktiv ist, und weise jedem einen Gesuchstyp zu.';
$l['anschlusssuche_tab_fields'] = 'Felder';
$l['anschlusssuche_tab_desc_fields'] = 'Lege die Felder jedes Gesuchstyps fest: Reihenfolge, Feldtyp, Pflicht, Optionen und wo sie angezeigt werden.';
$l['anschlusssuche_tab_header'] = 'Header-Einträge';
$l['anschlusssuche_tab_desc_header'] = 'Feste Gesuche, die für einen Zeitraum im Header erscheinen. Freie Plätze werden zufällig aufgefüllt.';
$l['anschlusssuche_tab_field_edit'] = 'Feld bearbeiten';
$l['anschlusssuche_tab_desc_field_edit'] = 'Feld anlegen oder ändern. Der Schlüssel wird intern und in Suchlinks verwendet.';

// Typen
$l['anschlusssuche_type_single'] = 'Einzelgesuche';
$l['anschlusssuche_type_group'] = 'Gruppengesuche';
$l['anschlusssuche_type_loose'] = 'Anschluss-Angebote';

// Allgemein
$l['anschlusssuche_actions'] = 'Aktionen';
$l['anschlusssuche_edit'] = 'Bearbeiten';
$l['anschlusssuche_delete'] = 'Löschen';
$l['anschlusssuche_save'] = 'Speichern';
$l['anschlusssuche_please_select'] = 'Bitte auswählen';

// Foren
$l['anschlusssuche_forums_title'] = 'Aktive Foren';
$l['anschlusssuche_no_forums'] = 'Noch keine Foren zugeordnet.';
$l['anschlusssuche_forum'] = 'Forum';
$l['anschlusssuche_forum_desc'] = 'Threads in diesem Forum bekommen die Felder des gewählten Gesuchstyps.';
$l['anschlusssuche_forum_missing'] = 'Forum {1} existiert nicht mehr';
$l['anschlusssuche_forum_add'] = 'Forum zuordnen';
$l['anschlusssuche_forum_edit'] = 'Forenzuordnung bearbeiten';
$l['anschlusssuche_type'] = 'Gesuchstyp';
$l['anschlusssuche_type_desc'] = 'Einzel- und Gruppengesuche sind Threads mit Feldern. Ein Forum vom Typ „Anschluss-Angebote“ zeigt über der Threadliste den Block mit den Anschluss-Angeboten; Threads bleiben dort normale Threads.';
$l['anschlusssuche_archive'] = 'Archiv';
$l['anschlusssuche_archive_desc'] = 'Gesuche in Archivforen gelten als erledigt. Header und Suche blenden sie standardmäßig aus, die Threadliste zeigt sie ausgegraut.';
$l['anschlusssuche_filterbar'] = 'Filterleiste';
$l['anschlusssuche_filterbar_desc'] = 'Filterleiste über der Threadliste anzeigen. Sie öffnet die Suchseite mit den gewählten Filtern.';
$l['anschlusssuche_loose_block'] = 'Block Anschluss-Angebote';
$l['anschlusssuche_loose_block_desc'] = 'Nur für Typ „Anschluss-Angebote“: Karten aller Anschluss-Angebote über der Threadliste anzeigen.';
$l['anschlusssuche_error_forum'] = 'Bitte wähle ein gültiges Forum und einen Gesuchstyp.';
$l['anschlusssuche_forum_saved'] = 'Die Forenzuordnung wurde gespeichert.';
$l['anschlusssuche_forum_deleted'] = 'Die Forenzuordnung wurde entfernt. Gespeicherte Gesuchsdaten bleiben erhalten.';
$l['anschlusssuche_forum_delete_confirm'] = 'Diese Forenzuordnung entfernen? Gespeicherte Gesuchsdaten bleiben erhalten und erscheinen wieder, wenn das Forum erneut zugeordnet wird.';

// Felder
$l['anschlusssuche_fields_for'] = 'Felder für:';
$l['anschlusssuche_fields_title'] = 'Felder: {1}';
$l['anschlusssuche_field_add_link'] = 'Feld hinzufügen';
$l['anschlusssuche_no_fields'] = 'Für diesen Typ sind noch keine Felder angelegt.';
$l['anschlusssuche_field_add'] = 'Feld hinzufügen ({1})';
$l['anschlusssuche_field_edit'] = 'Feld bearbeiten ({1})';
$l['anschlusssuche_field_title'] = 'Titel';
$l['anschlusssuche_field_fkey'] = 'Schlüssel';
$l['anschlusssuche_field_fkey_desc'] = 'Kleinbuchstaben, Ziffern und Unterstrich, beginnt mit einem Buchstaben. Muss innerhalb des Gesuchstyps eindeutig sein.';
$l['anschlusssuche_field_description'] = 'Beschreibung';
$l['anschlusssuche_field_description_desc'] = 'Hilfetext unter dem Feldtitel im Formular.';
$l['anschlusssuche_field_fieldtype'] = 'Feldtyp';
$l['anschlusssuche_field_fieldtype_desc'] = 'Altersspanne: von–bis plus optionaler Anzeigetext (max. eins je Gesuchstyp). Anschlüsse: Liste aus Name + Rolle, Namen lassen sich mit Accounts verknüpfen.';
$l['anschlusssuche_field_options'] = 'Optionen';
$l['anschlusssuche_field_options_desc'] = 'Eine Option pro Zeile (für Auswahl, Radio und Checkboxen). Wird eine Option in derselben Zeile umbenannt, werden gespeicherte Werte mitgeändert.';
$l['anschlusssuche_field_icon'] = 'Icon';
$l['anschlusssuche_field_icon_desc'] = 'CSS-Klasse eines Icons, z. B. „fas fa-users“ (Font Awesome). Leer = kein Icon.';
$l['anschlusssuche_field_disporder'] = 'Reihenfolge';
$l['anschlusssuche_field_required'] = 'Pflichtfeld';
$l['anschlusssuche_field_show_list'] = 'In der Threadliste anzeigen';
$l['anschlusssuche_field_show_list_desc'] = 'Gilt auch für Karten (Anschluss-Angebote, Suchtreffer, Profil).';
$l['anschlusssuche_field_show_profile'] = 'Im Profil anzeigen';
$l['anschlusssuche_field_show_header'] = 'Im Header anzeigen';
$l['anschlusssuche_field_filterable'] = 'Filterbar';
$l['anschlusssuche_field_filterable_desc'] = 'Feld als Filter in der Filterleiste und auf der Suchseite anbieten.';
$l['anschlusssuche_field_active'] = 'Aktiv';
$l['anschlusssuche_field_active_desc'] = 'Inaktive Felder werden überall ausgeblendet, ihre gespeicherten Werte bleiben erhalten.';
$l['anschlusssuche_field_flags'] = 'Einstellungen';
$l['anschlusssuche_flag_required'] = 'Pflicht';
$l['anschlusssuche_flag_show_list'] = 'Liste';
$l['anschlusssuche_flag_show_profile'] = 'Profil';
$l['anschlusssuche_flag_show_header'] = 'Header';
$l['anschlusssuche_flag_filterable'] = 'Filter';
$l['anschlusssuche_flag_inactive'] = 'inaktiv';
$l['anschlusssuche_save_order'] = 'Reihenfolge speichern';
$l['anschlusssuche_order_saved'] = 'Die Reihenfolge wurde gespeichert.';
$l['anschlusssuche_field_saved'] = 'Das Feld wurde gespeichert.';
$l['anschlusssuche_field_deleted'] = 'Das Feld und alle gespeicherten Werte wurden gelöscht.';
$l['anschlusssuche_field_delete_confirm'] = 'Feld „{1}“ löschen? Alle gespeicherten Werte dieses Feldes gehen verloren. Tipp: Setz es stattdessen auf inaktiv, dann bleiben die Daten erhalten.';
$l['anschlusssuche_error_field'] = 'Das Feld existiert nicht.';
$l['anschlusssuche_error_type'] = 'Ungültiger Gesuchstyp.';
$l['anschlusssuche_error_fieldtype'] = 'Ungültiger Feldtyp.';
$l['anschlusssuche_error_fkey'] = 'Der Schlüssel darf nur Kleinbuchstaben, Ziffern und Unterstrich enthalten und muss mit einem Buchstaben beginnen.';
$l['anschlusssuche_error_fkey_taken'] = 'Dieser Schlüssel wird schon von einem anderen Feld dieses Gesuchstyps verwendet.';
$l['anschlusssuche_error_title'] = 'Bitte gib einen Titel ein (max. 100 Zeichen).';
$l['anschlusssuche_error_options'] = 'Bitte gib mindestens eine Option ein.';
$l['anschlusssuche_error_age_unique'] = 'Dieser Gesuchstyp hat bereits ein Altersspanne-Feld.';
$l['anschlusssuche_error_fieldtype_change'] = 'Altersspanne und Anschlüsse speichern ihre Daten anders und lassen sich nicht in andere Feldtypen umwandeln (und umgekehrt).';
$l['anschlusssuche_fieldtype_text'] = 'Text (einzeilig)';
$l['anschlusssuche_fieldtype_textarea'] = 'Text (mehrzeilig)';
$l['anschlusssuche_fieldtype_select'] = 'Auswahlliste';
$l['anschlusssuche_fieldtype_radio'] = 'Radio-Buttons';
$l['anschlusssuche_fieldtype_multi'] = 'Checkboxen (Mehrfachauswahl)';
$l['anschlusssuche_fieldtype_number'] = 'Zahl';
$l['anschlusssuche_fieldtype_age_range'] = 'Altersspanne';
$l['anschlusssuche_fieldtype_links'] = 'Anschlüsse';

// Header
$l['anschlusssuche_header_title'] = 'Feste Header-Einträge';
$l['anschlusssuche_no_header'] = 'Keine festen Header-Einträge.';
$l['anschlusssuche_header_target'] = 'Gesuch';
$l['anschlusssuche_header_period'] = 'Zeitraum';
$l['anschlusssuche_header_source'] = 'Herkunft';
$l['anschlusssuche_header_note'] = 'Notiz';
$l['anschlusssuche_header_relatives_person'] = 'Relatives-Person #{1}';
$l['anschlusssuche_source_acp'] = 'ACP';
$l['anschlusssuche_source_shop'] = 'Shop';
$l['anschlusssuche_header_add'] = 'Festen Header-Eintrag hinzufügen';
$l['anschlusssuche_header_edit'] = 'Festen Header-Eintrag bearbeiten';
$l['anschlusssuche_header_tid'] = 'Thread-ID';
$l['anschlusssuche_header_tid_desc'] = 'ID eines Gesuch-Threads (Einzel- oder Gruppengesuch).';
$l['anschlusssuche_header_entry_id'] = 'oder ID eines Anschluss-Angebots';
$l['anschlusssuche_header_entry_id_desc'] = 'Nur wenn keine Thread-ID angegeben ist: ID eines Anschluss-Angebots.';
$l['anschlusssuche_header_start'] = 'Erster Tag';
$l['anschlusssuche_header_end'] = 'Letzter Tag';
$l['anschlusssuche_header_date_desc'] = 'Format JJJJ-MM-TT.';
$l['anschlusssuche_header_end_desc'] = 'Format JJJJ-MM-TT. Der Eintrag erscheint bis zum Ende dieses Tages.';
$l['anschlusssuche_header_saved'] = 'Der Header-Eintrag wurde gespeichert.';
$l['anschlusssuche_header_deleted'] = 'Der Header-Eintrag wurde gelöscht.';
$l['anschlusssuche_header_delete_confirm'] = 'Diesen Header-Eintrag löschen?';
$l['anschlusssuche_error_period'] = 'Bitte gib einen gültigen Zeitraum ein (erster Tag nicht nach dem letzten).';
$l['anschlusssuche_error_header_target'] = 'Zu dieser Thread-ID bzw. ID eines Anschluss-Angebots wurde kein Gesuch gefunden.';

// Einstellungen
$l['setting_group_anschlusssuche'] = 'Anschluss-Suchmaschine';
$l['setting_group_anschlusssuche_desc'] = 'Einstellungen für Gesuche (Einzel, Gruppe, Anschluss-Angebote), Profil, Header und Suche.';
$l['setting_anschlusssuche_enabled'] = 'Plugin aktivieren';
$l['setting_anschlusssuche_enabled_desc'] = 'Hauptschalter für alle Frontend-Funktionen.';
$l['setting_anschlusssuche_search_guests'] = 'Suche für Gäste';
$l['setting_anschlusssuche_search_guests_desc'] = 'Dürfen Gäste die Suchseite anschluss.php nutzen?';
$l['setting_anschlusssuche_results_per_page'] = 'Suchtreffer pro Seite';
$l['setting_anschlusssuche_results_per_page_desc'] = 'Anzahl der Treffer pro Seite auf der Suchseite.';
$l['setting_anschlusssuche_loose_per_page'] = 'Anschluss-Angebote pro Seite';
$l['setting_anschlusssuche_loose_per_page_desc'] = 'Anzahl der Karten pro Seite im Block „Anschluss-Angebote“ eines Forums.';
$l['setting_anschlusssuche_loose_groups'] = 'Gruppen für Anschluss-Angebote';
$l['setting_anschlusssuche_loose_groups_desc'] = 'Benutzergruppen, die im UserCP Anschluss-Angebote anlegen dürfen.';
$l['setting_anschlusssuche_loose_max_per_user'] = 'Max. Anschluss-Angebote pro Account';
$l['setting_anschlusssuche_loose_max_per_user_desc'] = 'Höchstzahl an Anschluss-Angeboten pro Account (0 = unbegrenzt).';
$l['setting_anschlusssuche_relatives_enabled'] = 'Relatives einbeziehen';
$l['setting_anschlusssuche_relatives_enabled_desc'] = 'Im Relatives-Plugin als gesucht markierte Personen als Anschluss-Angebote anzeigen (braucht eine Relatives-Version mit relatives_count_persons()).';
$l['setting_anschlusssuche_relatives_gender_map'] = 'Relatives: Geschlechter-Zuordnung';
$l['setting_anschlusssuche_relatives_gender_map_desc'] = 'Eine Zeile pro Code: Code=Text. Ordnet den Geschlechtscode aus Relatives dem Anzeige- und Filtertext zu.';
$l['setting_anschlusssuche_profile_enabled'] = 'Im Profil anzeigen';
$l['setting_anschlusssuche_profile_enabled_desc'] = 'Gesuche-Block im Profil anzeigen (Variable {$anschlusssuche_profile}).';
$l['setting_anschlusssuche_profile_show_done'] = 'Erledigte Gesuche im Profil';
$l['setting_anschlusssuche_profile_show_done_desc'] = 'Gesuche aus Archivforen als eigenen Abschnitt im Profil anzeigen.';
$l['setting_anschlusssuche_profile_hide_relatives'] = 'Relatives-Personen im Profil ausblenden';
$l['setting_anschlusssuche_profile_hide_relatives_desc'] = 'Personen aus dem Relatives-Plugin nicht im Profilblock auflisten (sinnvoll, wenn die Gesucht-Box von Relatives ebenfalls im Profil steht).';
$l['setting_anschlusssuche_header_enabled'] = 'Header-Teaser';
$l['setting_anschlusssuche_header_enabled_desc'] = 'Variable {$anschlusssuche_header} mit festen und zufälligen Gesuchen füllen.';
$l['setting_anschlusssuche_header_count'] = 'Header: Anzahl Gesuche';
$l['setting_anschlusssuche_header_count_desc'] = 'Gesamtzahl der Gesuche im Header (fest + zufällig).';
$l['setting_anschlusssuche_header_forums'] = 'Header: Quellforen';
$l['setting_anschlusssuche_header_forums_desc'] = 'Foren, aus denen zufällige Gesuche gewählt werden. Foren ohne Gesuchstyp zeigen nur den Titel.';
$l['setting_anschlusssuche_header_loose'] = 'Header: Anschluss-Angebote einbeziehen';
$l['setting_anschlusssuche_header_loose_desc'] = 'Anschluss-Angebote in den Zufallspool aufnehmen.';
$l['setting_anschlusssuche_header_free_only'] = 'Header: nur offene Gesuche';
$l['setting_anschlusssuche_header_free_only_desc'] = 'Zufällig nur Gesuche mit Status „frei“ wählen.';
$l['setting_anschlusssuche_shop_enabled'] = 'NewPoints-Shop (vorbereitet)';
$l['setting_anschlusssuche_shop_enabled_desc'] = 'Feste Header-Plätze über den NewPoints-Shop kaufen. Für eine spätere Version vorbereitet – derzeit ohne Wirkung.';
$l['setting_anschlusssuche_shop_max_concurrent'] = 'Shop: max. gleichzeitige Header-Plätze';
$l['setting_anschlusssuche_shop_max_concurrent_desc'] = 'Höchstzahl gekaufter Header-Einträge zur selben Zeit (0 = Header-Anzahl).';

// Standardfelder, die bei der ersten Installation angelegt werden (Titel, Optionen je Zeile)
$l['anschlusssuche_default_age'] = 'Alter';
$l['anschlusssuche_default_gender'] = 'Geschlecht';
$l['anschlusssuche_default_gender_options'] = "männlich\nweiblich\ndivers\negal";
$l['anschlusssuche_default_relation'] = 'Beziehung';
$l['anschlusssuche_default_relation_options'] = "Familie\nFreundschaft\nRomanze\nFeindschaft\nArbeit\nSonstiges";
$l['anschlusssuche_default_tags'] = 'Sonstiges';
$l['anschlusssuche_default_links'] = 'Verbindungen';
