<?php
/**
 * Anschluss-Suchmaschine – front-end language strings (English).
 */

$l['anschlusssuche_disabled'] = 'This feature is currently disabled.';

// Types
$l['anschlusssuche_type_all'] = 'All';
$l['anschlusssuche_type_single'] = 'Single ads';
$l['anschlusssuche_type_group'] = 'Group ads';
$l['anschlusssuche_type_loose'] = 'Loose ads';

// Status
$l['anschlusssuche_status_free'] = 'free';
$l['anschlusssuche_status_reserved'] = 'reserved';
$l['anschlusssuche_status_taken'] = 'taken';
$l['anschlusssuche_status_done'] = 'done';

// Core fields
$l['anschlusssuche_core_title'] = 'Title';
$l['anschlusssuche_core_status'] = 'Status';
$l['anschlusssuche_core_status_manual'] = 'set status manually';
$l['anschlusssuche_core_status_group_desc'] = 'Without manual status the ad is set to "taken" automatically once all slots are filled.';
$l['anschlusssuche_core_slots'] = 'Slots (filled / total)';
$l['anschlusssuche_core_slots_desc'] = 'How many characters are wanted in total and how many have been found already.';
$l['anschlusssuche_core_slots_total'] = 'Wanted in total';
$l['anschlusssuche_core_slots_taken'] = 'Already found';
$l['anschlusssuche_core_slots_open'] = 'open-ended (more welcome)';
$l['anschlusssuche_core_age'] = 'Age';
$l['anschlusssuche_core_gender'] = 'Gender';
$l['anschlusssuche_core_relation'] = 'Relation';

// Age
$l['anschlusssuche_age_from'] = 'from';
$l['anschlusssuche_age_to'] = 'to';
$l['anschlusssuche_age_text_placeholder'] = 'optional text, e.g. "mid 20s"';
$l['anschlusssuche_age_hint'] = 'Same number twice = fixed age, leave "to" empty = "and older". The optional text replaces the number range in the display.';
$l['anschlusssuche_age_single'] = '{1} years';
$l['anschlusssuche_age_range'] = '{1} - {2} years';
$l['anschlusssuche_age_open'] = '{1}+ years';

// Connections
$l['anschlusssuche_links_add'] = '+ add connection';
$l['anschlusssuche_links_name'] = 'Name (account or free text)';
$l['anschlusssuche_links_role'] = 'Role, e.g. brother';
$l['anschlusssuche_links_count'] = 'Connected to {1} characters';
$l['anschlusssuche_links_count_one'] = 'Connected to 1 character';

// Forms
$l['anschlusssuche_form_heading'] = 'Ad details';
$l['anschlusssuche_please_select'] = 'Please select';
$l['anschlusssuche_error_generic'] = '{1}';
// PostDataHandler prefixes its error keys with "postdata_"
$l['postdata_anschlusssuche_error_generic'] = '{1}';
$l['anschlusssuche_error_required'] = 'Please fill in "{1}".';
$l['anschlusssuche_error_option'] = 'Please choose a valid option for "{1}".';
$l['anschlusssuche_error_number'] = '"{1}" must be a whole number.';
$l['anschlusssuche_error_age'] = '"{1}" needs a valid age (0-999); "to" requires "from".';
$l['anschlusssuche_error_age_order'] = '"{1}": the upper age must not be lower than the lower age.';
$l['anschlusssuche_error_length'] = '"{1}" is too long.';
$l['anschlusssuche_error_slots'] = 'More slots are filled than wanted in total.';

// Thread list + filters
$l['anschlusssuche_filterbar_title'] = 'Search ads';
$l['anschlusssuche_filter_any'] = 'any';
$l['anschlusssuche_filter_submit'] = 'Search';
$l['anschlusssuche_loose_block_title'] = 'Loose ads';
$l['anschlusssuche_loose_empty'] = 'There are no loose ads at the moment.';
$l['anschlusssuche_loose_new'] = 'New loose ad';
$l['anschlusssuche_loose_edit'] = 'Edit loose ad';
$l['anschlusssuche_source_relatives'] = 'from the family tree';

// Profile
$l['anschlusssuche_profile_section_single'] = 'Single ads';
$l['anschlusssuche_profile_section_group'] = 'Group ads';
$l['anschlusssuche_profile_section_loose'] = 'Loose ads';
$l['anschlusssuche_profile_section_done'] = 'Finished ads';
$l['anschlusssuche_profile_empty'] = 'This player has no ads at the moment.';

// User CP
$l['anschlusssuche_usercp_nav'] = 'Loose ads';
$l['anschlusssuche_usercp_title'] = 'My loose ads';
$l['anschlusssuche_usercp_intro'] = 'Loose ads are wanted characters without their own thread. They appear in the loose ads forum, in the search and in your profile.';
$l['anschlusssuche_usercp_updated'] = 'Last change';
$l['anschlusssuche_usercp_actions'] = 'Actions';
$l['anschlusssuche_usercp_empty'] = 'You have not created any loose ads yet.';
$l['anschlusssuche_usercp_limit'] = 'You can create up to {1} loose ads per account.';
$l['anschlusssuche_edit'] = 'Edit';
$l['anschlusssuche_delete'] = 'Delete';
$l['anschlusssuche_delete_confirm'] = 'Do you really want to delete this ad?';
$l['anschlusssuche_save'] = 'Save';
$l['anschlusssuche_cancel'] = 'Cancel';
$l['anschlusssuche_saved'] = 'The ad has been saved.';
$l['anschlusssuche_deleted'] = 'The ad has been deleted.';

// Search page
$l['anschlusssuche_search_title'] = 'Ad search';
$l['anschlusssuche_search_query'] = 'Text';
$l['anschlusssuche_search_archive'] = 'include finished ads';
$l['anschlusssuche_search_results'] = 'Results';
$l['anschlusssuche_search_noresults'] = 'No ads match your search.';
