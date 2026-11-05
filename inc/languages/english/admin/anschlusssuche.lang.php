<?php
/**
 * Anschluss-Suchmaschine – ACP language strings (English).
 */

$l['anschlusssuche'] = 'Anschluss-Suchmaschine';
$l['anschlusssuche_can_manage'] = 'Can manage wanted-character ads?';
$l['anschlusssuche_not_installed'] = 'The plugin Anschluss-Suchmaschine is not installed.';

// Tabs
$l['anschlusssuche_tab_forums'] = 'Forums';
$l['anschlusssuche_tab_desc_forums'] = 'Choose the forums in which the plugin is active and assign an ad type to each of them.';
$l['anschlusssuche_tab_fields'] = 'Fields';
$l['anschlusssuche_tab_desc_fields'] = 'Define the fields of each ad type: order, field type, required, options and where they are shown.';
$l['anschlusssuche_tab_header'] = 'Header entries';
$l['anschlusssuche_tab_desc_header'] = 'Fixed ads shown in the header for a period of time. Remaining slots are filled randomly.';
$l['anschlusssuche_tab_field_edit'] = 'Edit field';
$l['anschlusssuche_tab_desc_field_edit'] = 'Add or change a field. The key is used internally and in search links.';

// Types
$l['anschlusssuche_type_single'] = 'Single ads';
$l['anschlusssuche_type_group'] = 'Group ads';
$l['anschlusssuche_type_loose'] = 'Loose ads';

// Common
$l['anschlusssuche_actions'] = 'Actions';
$l['anschlusssuche_edit'] = 'Edit';
$l['anschlusssuche_delete'] = 'Delete';
$l['anschlusssuche_save'] = 'Save';
$l['anschlusssuche_please_select'] = 'Please select';

// Forums
$l['anschlusssuche_forums_title'] = 'Active forums';
$l['anschlusssuche_no_forums'] = 'No forums assigned yet.';
$l['anschlusssuche_forum'] = 'Forum';
$l['anschlusssuche_forum_desc'] = 'Threads in this forum get the fields of the chosen ad type.';
$l['anschlusssuche_forum_missing'] = 'Forum {1} no longer exists';
$l['anschlusssuche_forum_add'] = 'Assign forum';
$l['anschlusssuche_forum_edit'] = 'Edit forum assignment';
$l['anschlusssuche_type'] = 'Ad type';
$l['anschlusssuche_type_desc'] = 'Single and group ads are threads with fields. A forum of type "loose" shows the loose ads block above its thread list; threads there stay normal threads.';
$l['anschlusssuche_archive'] = 'Archive';
$l['anschlusssuche_archive_desc'] = 'Ads in archive forums count as finished. They are hidden from header and search by default and shown greyed out.';
$l['anschlusssuche_filterbar'] = 'Filter bar';
$l['anschlusssuche_filterbar_desc'] = 'Show a filter bar above the thread list. It opens the search page with the selected filters.';
$l['anschlusssuche_loose_block'] = 'Loose ads block';
$l['anschlusssuche_loose_block_desc'] = 'Only for type "loose": show the cards of all loose ads above the thread list.';
$l['anschlusssuche_error_forum'] = 'Please choose a valid forum and ad type.';
$l['anschlusssuche_forum_saved'] = 'The forum assignment has been saved.';
$l['anschlusssuche_forum_deleted'] = 'The forum assignment has been removed. Stored ad data is kept.';
$l['anschlusssuche_forum_delete_confirm'] = 'Remove this forum assignment? Stored ad data is kept and reappears when the forum is assigned again.';

// Fields
$l['anschlusssuche_fields_for'] = 'Fields for:';
$l['anschlusssuche_fields_title'] = 'Fields: {1}';
$l['anschlusssuche_field_add_link'] = 'Add field';
$l['anschlusssuche_no_fields'] = 'No fields defined for this type yet.';
$l['anschlusssuche_field_add'] = 'Add field ({1})';
$l['anschlusssuche_field_edit'] = 'Edit field ({1})';
$l['anschlusssuche_field_title'] = 'Title';
$l['anschlusssuche_field_fkey'] = 'Key';
$l['anschlusssuche_field_fkey_desc'] = 'Lowercase letters, digits and underscore, starting with a letter. Must be unique within the ad type.';
$l['anschlusssuche_field_description'] = 'Description';
$l['anschlusssuche_field_description_desc'] = 'Help text shown below the field title in the form.';
$l['anschlusssuche_field_fieldtype'] = 'Field type';
$l['anschlusssuche_field_fieldtype_desc'] = 'Age range: from-to plus optional display text (max. one per ad type). Connections: list of name + role, names can be linked to accounts.';
$l['anschlusssuche_field_options'] = 'Options';
$l['anschlusssuche_field_options_desc'] = 'One option per line (for select, radio and checkboxes). Renaming an option in the same line also updates stored values.';
$l['anschlusssuche_field_icon'] = 'Icon';
$l['anschlusssuche_field_icon_desc'] = 'CSS class of an icon, e.g. "fas fa-users" (Font Awesome). Leave empty for none.';
$l['anschlusssuche_field_disporder'] = 'Order';
$l['anschlusssuche_field_required'] = 'Required';
$l['anschlusssuche_field_show_list'] = 'Show in thread list';
$l['anschlusssuche_field_show_list_desc'] = 'Also used for cards (loose ads, search results, profile).';
$l['anschlusssuche_field_show_profile'] = 'Show in profile';
$l['anschlusssuche_field_show_header'] = 'Show in header';
$l['anschlusssuche_field_filterable'] = 'Filterable';
$l['anschlusssuche_field_filterable_desc'] = 'Offer this field as a filter in the filter bar and on the search page.';
$l['anschlusssuche_field_active'] = 'Active';
$l['anschlusssuche_field_active_desc'] = 'Inactive fields are hidden everywhere, their stored values are kept.';
$l['anschlusssuche_field_flags'] = 'Settings';
$l['anschlusssuche_flag_required'] = 'required';
$l['anschlusssuche_flag_show_list'] = 'list';
$l['anschlusssuche_flag_show_profile'] = 'profile';
$l['anschlusssuche_flag_show_header'] = 'header';
$l['anschlusssuche_flag_filterable'] = 'filter';
$l['anschlusssuche_flag_inactive'] = 'inactive';
$l['anschlusssuche_save_order'] = 'Save order';
$l['anschlusssuche_order_saved'] = 'The order has been saved.';
$l['anschlusssuche_field_saved'] = 'The field has been saved.';
$l['anschlusssuche_field_deleted'] = 'The field and all its stored values have been deleted.';
$l['anschlusssuche_field_delete_confirm'] = 'Delete the field "{1}"? All stored values of this field will be lost. Tip: set it inactive instead to keep the data.';
$l['anschlusssuche_error_field'] = 'The field does not exist.';
$l['anschlusssuche_error_type'] = 'Invalid ad type.';
$l['anschlusssuche_error_fieldtype'] = 'Invalid field type.';
$l['anschlusssuche_error_fkey'] = 'The key may only contain lowercase letters, digits and underscore and must start with a letter.';
$l['anschlusssuche_error_fkey_taken'] = 'This key is already used by another field of this ad type.';
$l['anschlusssuche_error_title'] = 'Please enter a title (max. 100 characters).';
$l['anschlusssuche_error_options'] = 'Please enter at least one option.';
$l['anschlusssuche_error_age_unique'] = 'This ad type already has an age range field.';
$l['anschlusssuche_error_fieldtype_change'] = 'Age range and connection fields store their data differently and cannot be converted to or from other field types.';
$l['anschlusssuche_fieldtype_text'] = 'Text (one line)';
$l['anschlusssuche_fieldtype_textarea'] = 'Text (multiple lines)';
$l['anschlusssuche_fieldtype_select'] = 'Select box';
$l['anschlusssuche_fieldtype_radio'] = 'Radio buttons';
$l['anschlusssuche_fieldtype_multi'] = 'Checkboxes (multiple)';
$l['anschlusssuche_fieldtype_number'] = 'Number';
$l['anschlusssuche_fieldtype_age_range'] = 'Age range';
$l['anschlusssuche_fieldtype_links'] = 'Connections';

// Header
$l['anschlusssuche_header_title'] = 'Fixed header entries';
$l['anschlusssuche_no_header'] = 'No fixed header entries.';
$l['anschlusssuche_header_target'] = 'Ad';
$l['anschlusssuche_header_period'] = 'Period';
$l['anschlusssuche_header_source'] = 'Source';
$l['anschlusssuche_header_note'] = 'Note';
$l['anschlusssuche_header_relatives_person'] = 'Relatives person #{1}';
$l['anschlusssuche_source_acp'] = 'ACP';
$l['anschlusssuche_source_shop'] = 'Shop';
$l['anschlusssuche_header_add'] = 'Add fixed header entry';
$l['anschlusssuche_header_edit'] = 'Edit fixed header entry';
$l['anschlusssuche_header_tid'] = 'Thread ID';
$l['anschlusssuche_header_tid_desc'] = 'ID of an ad thread (single or group ad).';
$l['anschlusssuche_header_entry_id'] = 'or loose ad ID';
$l['anschlusssuche_header_entry_id_desc'] = 'Only if no thread ID is given: ID of a loose ad.';
$l['anschlusssuche_header_start'] = 'First day';
$l['anschlusssuche_header_end'] = 'Last day';
$l['anschlusssuche_header_date_desc'] = 'Format YYYY-MM-DD.';
$l['anschlusssuche_header_end_desc'] = 'Format YYYY-MM-DD. The entry is shown until the end of this day.';
$l['anschlusssuche_header_saved'] = 'The header entry has been saved.';
$l['anschlusssuche_header_deleted'] = 'The header entry has been deleted.';
$l['anschlusssuche_header_delete_confirm'] = 'Delete this header entry?';
$l['anschlusssuche_error_period'] = 'Please enter a valid period (first day not after last day).';
$l['anschlusssuche_error_header_target'] = 'No ad found for this thread ID or loose ad ID.';

// Settings
$l['setting_group_anschlusssuche'] = 'Anschluss-Suchmaschine';
$l['setting_group_anschlusssuche_desc'] = 'Settings for wanted-character ads (single, group, loose), profile, header and search.';
$l['setting_anschlusssuche_enabled'] = 'Enable plugin';
$l['setting_anschlusssuche_enabled_desc'] = 'Master switch for all front-end features.';
$l['setting_anschlusssuche_search_guests'] = 'Search for guests';
$l['setting_anschlusssuche_search_guests_desc'] = 'Allow guests to use the search page anschluss.php.';
$l['setting_anschlusssuche_results_per_page'] = 'Search results per page';
$l['setting_anschlusssuche_results_per_page_desc'] = 'Number of results per page on the search page.';
$l['setting_anschlusssuche_loose_per_page'] = 'Loose ads per page';
$l['setting_anschlusssuche_loose_per_page_desc'] = 'Number of cards per page in the loose ads block of a forum.';
$l['setting_anschlusssuche_loose_groups'] = 'Groups allowed to create loose ads';
$l['setting_anschlusssuche_loose_groups_desc'] = 'User groups that may create loose ads in the User CP.';
$l['setting_anschlusssuche_loose_max_per_user'] = 'Max. loose ads per account';
$l['setting_anschlusssuche_loose_max_per_user_desc'] = 'Maximum number of loose ads per account (0 = unlimited).';
$l['setting_anschlusssuche_relatives_enabled'] = 'Include Relatives';
$l['setting_anschlusssuche_relatives_enabled_desc'] = 'Show persons marked as wanted in the Relatives plugin as loose ads (requires a Relatives version with relatives_count_persons()).';
$l['setting_anschlusssuche_relatives_gender_map'] = 'Relatives gender mapping';
$l['setting_anschlusssuche_relatives_gender_map_desc'] = 'One line per code: code=label. Maps the Relatives gender code to the label used for display and filters.';
$l['setting_anschlusssuche_profile_enabled'] = 'Show in profile';
$l['setting_anschlusssuche_profile_enabled_desc'] = 'Show the ads block in user profiles (variable {$anschlusssuche_profile}).';
$l['setting_anschlusssuche_profile_show_done'] = 'Show finished ads in profile';
$l['setting_anschlusssuche_profile_show_done_desc'] = 'Show ads from archive forums as their own section in the profile.';
$l['setting_anschlusssuche_profile_hide_relatives'] = 'Hide Relatives persons in profile';
$l['setting_anschlusssuche_profile_hide_relatives_desc'] = 'Do not list persons from the Relatives plugin in the profile block (useful when the Relatives wanted box is shown in the profile as well).';
$l['setting_anschlusssuche_header_enabled'] = 'Header teaser';
$l['setting_anschlusssuche_header_enabled_desc'] = 'Fill the variable {$anschlusssuche_header} with fixed and random ads.';
$l['setting_anschlusssuche_header_count'] = 'Header: number of ads';
$l['setting_anschlusssuche_header_count_desc'] = 'Total number of ads in the header (fixed + random).';
$l['setting_anschlusssuche_header_forums'] = 'Header: source forums';
$l['setting_anschlusssuche_header_forums_desc'] = 'Forums from which random ads are picked. Forums without an assigned ad type only show the subject.';
$l['setting_anschlusssuche_header_loose'] = 'Header: include loose ads';
$l['setting_anschlusssuche_header_loose_desc'] = 'Add own loose ads to the random pool.';
$l['setting_anschlusssuche_header_free_only'] = 'Header: only open ads';
$l['setting_anschlusssuche_header_free_only_desc'] = 'Only pick random ads with status "free".';
$l['setting_anschlusssuche_shop_enabled'] = 'NewPoints shop (prepared)';
$l['setting_anschlusssuche_shop_enabled_desc'] = 'Allow buying fixed header slots via the NewPoints shop. Prepared for a later version – currently without effect.';
$l['setting_anschlusssuche_shop_max_concurrent'] = 'Shop: max. concurrent header slots';
$l['setting_anschlusssuche_shop_max_concurrent_desc'] = 'Maximum number of purchased header entries at the same time (0 = header count).';

// Default fields created on first installation (title, options one per line)
$l['anschlusssuche_default_age'] = 'Age';
$l['anschlusssuche_default_gender'] = 'Gender';
$l['anschlusssuche_default_gender_options'] = "male\nfemale\ndiverse\nany";
$l['anschlusssuche_default_relation'] = 'Relation';
$l['anschlusssuche_default_relation_options'] = "Family\nFriendship\nRomance\nEnmity\nWork\nOther";
$l['anschlusssuche_default_tags'] = 'Other';
$l['anschlusssuche_default_links'] = 'Connections';
