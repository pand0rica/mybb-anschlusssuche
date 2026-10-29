<?php
/**
 * Anschluss-Suchmaschine – Frontend-Sprachdatei (Deutsch, du).
 */

$l['anschlusssuche_disabled'] = 'Diese Funktion ist zurzeit deaktiviert.';

// Typen
$l['anschlusssuche_type_all'] = 'Alle';
$l['anschlusssuche_type_single'] = 'Einzelgesuche';
$l['anschlusssuche_type_group'] = 'Gruppengesuche';
$l['anschlusssuche_type_loose'] = 'Anschluss-Angebote';

// Status
$l['anschlusssuche_status_free'] = 'frei';
$l['anschlusssuche_status_reserved'] = 'reserviert';
$l['anschlusssuche_status_taken'] = 'vergeben';
$l['anschlusssuche_status_done'] = 'erledigt';

// Kernfelder
$l['anschlusssuche_core_title'] = 'Titel';
$l['anschlusssuche_core_status'] = 'Status';
$l['anschlusssuche_core_status_manual'] = 'Status manuell setzen';
$l['anschlusssuche_core_status_group_desc'] = 'Ohne manuellen Status wird das Gesuch automatisch „vergeben“, sobald alle Plätze belegt sind.';
$l['anschlusssuche_core_slots'] = 'Belegung (gefunden / gesamt)';
$l['anschlusssuche_core_slots_desc'] = 'Wie viele Charaktere insgesamt gesucht werden und wie viele schon gefunden wurden.';
$l['anschlusssuche_core_slots_total'] = 'Insgesamt gesucht';
$l['anschlusssuche_core_slots_taken'] = 'Schon gefunden';
$l['anschlusssuche_core_slots_open'] = 'offen (weitere willkommen)';
$l['anschlusssuche_core_age'] = 'Alter';
$l['anschlusssuche_core_gender'] = 'Geschlecht';
$l['anschlusssuche_core_relation'] = 'Beziehung';

// Alter
$l['anschlusssuche_age_from'] = 'von';
$l['anschlusssuche_age_to'] = 'bis';
$l['anschlusssuche_age_text_placeholder'] = 'optionaler Text, z. B. „Mitte 20“';
$l['anschlusssuche_age_hint'] = 'Zweimal dieselbe Zahl = festes Alter, „bis“ leer = „und älter“. Der optionale Text ersetzt in der Anzeige die Zahlen.';
$l['anschlusssuche_age_single'] = '{1} Jahre';
$l['anschlusssuche_age_range'] = '{1} - {2} Jahre';
$l['anschlusssuche_age_open'] = '{1}+ Jahre';

// Anschlüsse
$l['anschlusssuche_links_add'] = '+ Anschluss hinzufügen';
$l['anschlusssuche_links_name'] = 'Name (Account oder freier Text)';
$l['anschlusssuche_links_role'] = 'Rolle, z. B. Bruder';
$l['anschlusssuche_links_count'] = 'Anschluss an {1} Charaktere';
$l['anschlusssuche_links_count_one'] = 'Anschluss an 1 Charakter';

// Formulare
$l['anschlusssuche_form_heading'] = 'Angaben zum Gesuch';
$l['anschlusssuche_please_select'] = 'Bitte auswählen';
$l['anschlusssuche_error_generic'] = '{1}';
// PostDataHandler prefixes its error keys with "postdata_"
$l['postdata_anschlusssuche_error_generic'] = '{1}';
$l['anschlusssuche_error_required'] = 'Bitte fülle „{1}“ aus.';
$l['anschlusssuche_error_option'] = 'Bitte wähle bei „{1}“ eine gültige Option.';
$l['anschlusssuche_error_number'] = '„{1}“ muss eine ganze Zahl sein.';
$l['anschlusssuche_error_age'] = '„{1}“ braucht ein gültiges Alter (0–999); „bis“ geht nur zusammen mit „von“.';
$l['anschlusssuche_error_age_order'] = '„{1}“: Das obere Alter darf nicht kleiner als das untere sein.';
$l['anschlusssuche_error_length'] = '„{1}“ ist zu lang.';
$l['anschlusssuche_error_slots'] = 'Es sind mehr Plätze belegt als insgesamt gesucht werden.';

// Threadliste + Filter
$l['anschlusssuche_filterbar_title'] = 'Gesuche durchsuchen';
$l['anschlusssuche_filter_any'] = 'egal';
$l['anschlusssuche_filter_submit'] = 'Suchen';
$l['anschlusssuche_loose_block_title'] = 'Anschluss-Angebote';
$l['anschlusssuche_loose_empty'] = 'Zurzeit gibt es keine Anschluss-Angebote.';
$l['anschlusssuche_loose_new'] = 'Neues Anschluss-Angebot';
$l['anschlusssuche_loose_edit'] = 'Anschluss-Angebot bearbeiten';
$l['anschlusssuche_source_relatives'] = 'aus dem Stammbaum';

// Profil
$l['anschlusssuche_profile_section_single'] = 'Einzelgesuche';
$l['anschlusssuche_profile_section_group'] = 'Gruppengesuche';
$l['anschlusssuche_profile_section_loose'] = 'Anschluss-Angebote';
$l['anschlusssuche_profile_section_done'] = 'Erledigte Gesuche';
$l['anschlusssuche_profile_empty'] = 'Diese:r Spieler:in hat aktuell keine Gesuche.';

// UserCP
$l['anschlusssuche_usercp_nav'] = 'Anschluss-Angebote';
$l['anschlusssuche_usercp_title'] = 'Meine Anschluss-Angebote';
$l['anschlusssuche_usercp_intro'] = 'Anschluss-Angebote sind gesuchte Charaktere ohne eigenen Thread. Sie erscheinen im Forum „Anschluss-Angebote“, in der Suche und im Profil des Charakters, mit dem du sie angelegt hast.';
$l['anschlusssuche_usercp_updated'] = 'Letzte Änderung';
$l['anschlusssuche_usercp_actions'] = 'Aktionen';
$l['anschlusssuche_usercp_empty'] = 'Du hast noch keine Anschluss-Angebote angelegt.';
$l['anschlusssuche_usercp_limit'] = 'Du kannst pro Account bis zu {1} Anschluss-Angebote anlegen.';
$l['anschlusssuche_edit'] = 'Bearbeiten';
$l['anschlusssuche_delete'] = 'Löschen';
$l['anschlusssuche_delete_confirm'] = 'Willst du dieses Gesuch wirklich löschen?';
$l['anschlusssuche_save'] = 'Speichern';
$l['anschlusssuche_cancel'] = 'Abbrechen';
$l['anschlusssuche_saved'] = 'Das Gesuch wurde gespeichert.';
$l['anschlusssuche_deleted'] = 'Das Gesuch wurde gelöscht.';

// Suchseite
$l['anschlusssuche_search_title'] = 'Gesuche-Suche';
$l['anschlusssuche_search_query'] = 'Text';
$l['anschlusssuche_search_archive'] = 'erledigte Gesuche einbeziehen';
$l['anschlusssuche_search_results'] = 'Treffer';
$l['anschlusssuche_search_noresults'] = 'Keine Gesuche passen zu deiner Suche.';
