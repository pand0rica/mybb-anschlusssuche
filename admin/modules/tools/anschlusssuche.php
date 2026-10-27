<?php
/**
 * ACP-Modul der Anschluss-Suchmaschine: Foren/Typen, Felder je Typ, feste Header-Einträge.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$lang->load('anschlusssuche');

$page->add_breadcrumb_item($lang->anschlusssuche, 'index.php?module=tools-anschlusssuche');

if ($mybb->request_method === 'post') {
    verify_post_check($mybb->get_input('my_post_key'));
}

if (!function_exists('anschlusssuche_is_installed') || !anschlusssuche_is_installed()) {
    flash_message($lang->anschlusssuche_not_installed, 'error');
    admin_redirect('index.php?module=config-plugins');
}

$action = $mybb->get_input('action');
$types = ['single', 'group', 'loose'];

/**
 * Gemeinsame Tab-Navigation aller Seiten.
 */
function anschlusssuche_acp_nav_tabs(string $active): void
{
    global $page, $lang;

    $tabs = [
        'forums' => [
            'title'       => $lang->anschlusssuche_tab_forums,
            'link'        => 'index.php?module=tools-anschlusssuche',
            'description' => $lang->anschlusssuche_tab_desc_forums,
        ],
        'fields' => [
            'title'       => $lang->anschlusssuche_tab_fields,
            'link'        => 'index.php?module=tools-anschlusssuche&amp;action=fields',
            'description' => $lang->anschlusssuche_tab_desc_fields,
        ],
        'header' => [
            'title'       => $lang->anschlusssuche_tab_header,
            'link'        => 'index.php?module=tools-anschlusssuche&amp;action=header',
            'description' => $lang->anschlusssuche_tab_desc_header,
        ],
    ];
    if ($active === 'field_edit') {
        $tabs['field_edit'] = [
            'title'       => $lang->anschlusssuche_tab_field_edit,
            'link'        => '#',
            'description' => $lang->anschlusssuche_tab_desc_field_edit,
        ];
    }
    $page->output_nav_tabs($tabs, $active);
}

function anschlusssuche_acp_type_label(string $type): string
{
    global $lang;
    $key = 'anschlusssuche_type_' . $type;
    return isset($lang->$key) ? $lang->$key : $type;
}

/** "Y-m-d" (Board-Zeitzone egal: Serverzeit) → Timestamp 00:00 bzw. 0 */
function anschlusssuche_acp_parse_date(string $value): int
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($value), $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        return 0;
    }
    return (int)mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
}

/* ======================================================================
 * Forums
 * ==================================================================== */

if ($action === 'forum_save' && $mybb->request_method === 'post') {
    $fid = $mybb->get_input('fid', MyBB::INPUT_INT);
    $type = $mybb->get_input('type');
    if ($fid <= 0 || !get_forum($fid) || !in_array($type, $types, true)) {
        flash_message($lang->anschlusssuche_error_forum, 'error');
        admin_redirect('index.php?module=tools-anschlusssuche');
    }
    Anschlusssuche_Repository::save_forum(
        $fid,
        $type,
        $mybb->get_input('is_archive', MyBB::INPUT_INT),
        $mybb->get_input('show_filterbar', MyBB::INPUT_INT),
        $mybb->get_input('show_loose_block', MyBB::INPUT_INT)
    );
    log_admin_action(['fid' => $fid, 'type' => $type]);
    flash_message($lang->anschlusssuche_forum_saved, 'success');
    admin_redirect('index.php?module=tools-anschlusssuche');
}

if ($action === 'forum_delete') {
    $fid = $mybb->get_input('fid', MyBB::INPUT_INT);
    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=tools-anschlusssuche');
    }
    if ($mybb->request_method === 'post') {
        Anschlusssuche_Repository::delete_forum($fid);
        log_admin_action(['fid' => $fid]);
        flash_message($lang->anschlusssuche_forum_deleted, 'success');
        admin_redirect('index.php?module=tools-anschlusssuche');
    }
    $page->output_confirm_action('index.php?module=tools-anschlusssuche&amp;action=forum_delete&amp;fid=' . $fid, $lang->anschlusssuche_forum_delete_confirm);
}

if ($action === '' || $action === 'forum_edit') {
    $page->output_header($lang->anschlusssuche);
    anschlusssuche_acp_nav_tabs('forums');

    $table = new Table;
    $table->construct_header($lang->anschlusssuche_forum);
    $table->construct_header($lang->anschlusssuche_type, ['width' => '15%']);
    $table->construct_header($lang->anschlusssuche_archive, ['width' => '10%', 'class' => 'align_center']);
    $table->construct_header($lang->anschlusssuche_filterbar, ['width' => '10%', 'class' => 'align_center']);
    $table->construct_header($lang->anschlusssuche_loose_block, ['width' => '10%', 'class' => 'align_center']);
    $table->construct_header($lang->anschlusssuche_actions, ['width' => '15%', 'class' => 'align_center']);

    $yes = $lang->yes;
    $no = $lang->no;
    foreach (Anschlusssuche_Repository::forums() as $fid => $row) {
        $forum = get_forum($fid);
        $name = $forum ? htmlspecialchars_uni($forum['name']) : $lang->sprintf($lang->anschlusssuche_forum_missing, $fid);
        $table->construct_cell($name . ' <span class="smalltext">(fid ' . (int)$fid . ')</span>');
        $table->construct_cell(htmlspecialchars_uni(anschlusssuche_acp_type_label($row['type'])));
        $table->construct_cell(!empty($row['is_archive']) ? $yes : $no, ['class' => 'align_center']);
        $table->construct_cell(!empty($row['show_filterbar']) ? $yes : $no, ['class' => 'align_center']);
        $table->construct_cell($row['type'] === 'loose' ? (!empty($row['show_loose_block']) ? $yes : $no) : '-', ['class' => 'align_center']);
        $table->construct_cell(
            '<a href="index.php?module=tools-anschlusssuche&amp;action=forum_edit&amp;fid=' . (int)$fid . '">' . $lang->anschlusssuche_edit . '</a>'
            . ' | <a href="index.php?module=tools-anschlusssuche&amp;action=forum_delete&amp;fid=' . (int)$fid . '">' . $lang->anschlusssuche_delete . '</a>',
            ['class' => 'align_center']
        );
        $table->construct_row();
    }
    if ($table->num_rows() === 0) {
        $table->construct_cell($lang->anschlusssuche_no_forums, ['colspan' => 6]);
        $table->construct_row();
    }
    $table->output($lang->anschlusssuche_forums_title);

    // add / edit form
    $edit_fid = $mybb->get_input('fid', MyBB::INPUT_INT);
    $current = $action === 'forum_edit' ? Anschlusssuche_Repository::forum($edit_fid) : null;
    $form = new Form('index.php?module=tools-anschlusssuche&amp;action=forum_save', 'post');
    $container = new FormContainer($current ? $lang->anschlusssuche_forum_edit : $lang->anschlusssuche_forum_add);
    $container->output_row($lang->anschlusssuche_forum . ' <em>*</em>', $lang->anschlusssuche_forum_desc,
        $form->generate_forum_select('fid', $current ? (int)$current['fid'] : 0, ['main_option' => $lang->anschlusssuche_please_select]), 'fid');
    $type_options = [];
    foreach ($types as $t) {
        $type_options[$t] = anschlusssuche_acp_type_label($t);
    }
    $container->output_row($lang->anschlusssuche_type . ' <em>*</em>', $lang->anschlusssuche_type_desc,
        $form->generate_select_box('type', $type_options, $current ? $current['type'] : 'single'), 'type');
    $container->output_row($lang->anschlusssuche_archive, $lang->anschlusssuche_archive_desc,
        $form->generate_yes_no_radio('is_archive', $current ? (int)$current['is_archive'] : 0));
    $container->output_row($lang->anschlusssuche_filterbar, $lang->anschlusssuche_filterbar_desc,
        $form->generate_yes_no_radio('show_filterbar', $current ? (int)$current['show_filterbar'] : 1));
    $container->output_row($lang->anschlusssuche_loose_block, $lang->anschlusssuche_loose_block_desc,
        $form->generate_yes_no_radio('show_loose_block', $current ? (int)$current['show_loose_block'] : 1));
    $container->end();
    $buttons = [$form->generate_submit_button($lang->anschlusssuche_save)];
    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}

/* ======================================================================
 * Fields
 * ==================================================================== */

if ($action === 'fields_order' && $mybb->request_method === 'post') {
    $type = $mybb->get_input('type');
    foreach ($mybb->get_input('disporder', MyBB::INPUT_ARRAY) as $id => $order) {
        Anschlusssuche_Repository::save_field(['disporder' => (int)$order], (int)$id);
    }
    flash_message($lang->anschlusssuche_order_saved, 'success');
    admin_redirect('index.php?module=tools-anschlusssuche&action=fields&type=' . urlencode(in_array($type, $types, true) ? $type : 'single'));
}

if ($action === 'field_save' && $mybb->request_method === 'post') {
    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    $existing = $id > 0 ? Anschlusssuche_Repository::field($id) : null;
    if ($id > 0 && !$existing) {
        flash_message($lang->anschlusssuche_error_field, 'error');
        admin_redirect('index.php?module=tools-anschlusssuche&action=fields');
    }
    $type = $existing ? $existing['type'] : $mybb->get_input('type');
    $fieldtype = $mybb->get_input('fieldtype');
    $fkey = strtolower(trim($mybb->get_input('fkey')));
    $title = trim($mybb->get_input('title'));
    $options = trim(str_replace("\r", '', $mybb->get_input('options')));

    $errors = [];
    if (!in_array($type, $types, true)) {
        $errors[] = $lang->anschlusssuche_error_type;
    }
    if (!in_array($fieldtype, Anschlusssuche_FieldTypes::TYPES, true)) {
        $errors[] = $lang->anschlusssuche_error_fieldtype;
    }
    if (!preg_match('/^[a-z][a-z0-9_]{0,49}$/', $fkey)) {
        $errors[] = $lang->anschlusssuche_error_fkey;
    } else {
        $other = Anschlusssuche_Repository::field_by_key((string)$type, $fkey);
        if ($other && (int)$other['id'] !== $id) {
            $errors[] = $lang->anschlusssuche_error_fkey_taken;
        }
    }
    if ($title === '' || my_strlen($title) > 100) {
        $errors[] = $lang->anschlusssuche_error_title;
    }
    if (in_array($fieldtype, Anschlusssuche_FieldTypes::OPTION_TYPES, true) && $options === '') {
        $errors[] = $lang->anschlusssuche_error_options;
    }
    if ($fieldtype === 'age_range' && !$errors) {
        $age = Anschlusssuche_Repository::age_field((string)$type);
        foreach (Anschlusssuche_Repository::fields((string)$type, false) as $f) {
            if ($f['fieldtype'] === 'age_range' && (int)$f['id'] !== $id) {
                $errors[] = $lang->anschlusssuche_error_age_unique;
                break;
            }
        }
    }
    if ($existing && $existing['fieldtype'] !== $fieldtype
        && in_array($existing['fieldtype'], ['links', 'age_range'], true) !== in_array($fieldtype, ['links', 'age_range'], true)) {
        $errors[] = $lang->anschlusssuche_error_fieldtype_change;
    }

    if ($errors) {
        $mybb->input['action'] = 'field_edit';
        $action = 'field_edit';
        $field_errors = $errors;
    } else {
        // Renamed options: old value line i → new value line i (same position).
        if ($existing && in_array($fieldtype, Anschlusssuche_FieldTypes::OPTION_TYPES, true) && $existing['fieldtype'] === $fieldtype) {
            $old = Anschlusssuche_FieldTypes::options($existing);
            $new = Anschlusssuche_FieldTypes::options(['options' => $options]);
            if (count($old) === count($new)) {
                foreach ($old as $i => $old_value) {
                    if ($old_value !== $new[$i]) {
                        Anschlusssuche_Repository::rename_option_value($id, $old_value, $new[$i], $fieldtype === 'multi');
                    }
                }
            }
        }
        $saved_id = Anschlusssuche_Repository::save_field([
            'type'         => $type,
            'fkey'         => $fkey,
            'title'        => $title,
            'description'  => trim($mybb->get_input('description')),
            'fieldtype'    => $fieldtype,
            'required'     => $mybb->get_input('required', MyBB::INPUT_INT) ? 1 : 0,
            'disporder'    => $mybb->get_input('disporder', MyBB::INPUT_INT),
            'options'      => $options,
            'icon'         => preg_replace('/[^a-zA-Z0-9 _-]/', '', $mybb->get_input('icon')),
            'show_list'    => $mybb->get_input('show_list', MyBB::INPUT_INT) ? 1 : 0,
            'show_profile' => $mybb->get_input('show_profile', MyBB::INPUT_INT) ? 1 : 0,
            'show_header'  => $mybb->get_input('show_header', MyBB::INPUT_INT) ? 1 : 0,
            'filterable'   => $mybb->get_input('filterable', MyBB::INPUT_INT) ? 1 : 0,
            'active'       => $mybb->get_input('active', MyBB::INPUT_INT) ? 1 : 0,
        ], $id);
        log_admin_action(['id' => $saved_id, 'type' => $type, 'fkey' => $fkey]);
        flash_message($lang->anschlusssuche_field_saved, 'success');
        admin_redirect('index.php?module=tools-anschlusssuche&action=fields&type=' . urlencode($type));
    }
}

if ($action === 'field_delete') {
    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    $field = Anschlusssuche_Repository::field($id);
    if (!$field) {
        flash_message($lang->anschlusssuche_error_field, 'error');
        admin_redirect('index.php?module=tools-anschlusssuche&action=fields');
    }
    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=tools-anschlusssuche&action=fields&type=' . urlencode($field['type']));
    }
    if ($mybb->request_method === 'post') {
        Anschlusssuche_Repository::delete_field($id);
        log_admin_action(['id' => $id, 'type' => $field['type'], 'fkey' => $field['fkey']]);
        flash_message($lang->anschlusssuche_field_deleted, 'success');
        admin_redirect('index.php?module=tools-anschlusssuche&action=fields&type=' . urlencode($field['type']));
    }
    $page->output_confirm_action('index.php?module=tools-anschlusssuche&amp;action=field_delete&amp;id=' . $id,
        $lang->sprintf($lang->anschlusssuche_field_delete_confirm, htmlspecialchars_uni($field['title'])));
}

if ($action === 'field_edit' || $action === 'field_add') {
    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    $field = $id > 0 ? Anschlusssuche_Repository::field($id) : null;
    $type = $field ? $field['type'] : $mybb->get_input('type');
    if (!in_array($type, $types, true)) {
        $type = 'single';
    }
    // After a failed save, show the submitted values.
    if (!empty($field_errors)) {
        $field = [
            'id'           => $id,
            'type'         => $type,
            'fkey'         => $mybb->get_input('fkey'),
            'title'        => $mybb->get_input('title'),
            'description'  => $mybb->get_input('description'),
            'fieldtype'    => $mybb->get_input('fieldtype'),
            'required'     => $mybb->get_input('required', MyBB::INPUT_INT),
            'disporder'    => $mybb->get_input('disporder', MyBB::INPUT_INT),
            'options'      => $mybb->get_input('options'),
            'icon'         => $mybb->get_input('icon'),
            'show_list'    => $mybb->get_input('show_list', MyBB::INPUT_INT),
            'show_profile' => $mybb->get_input('show_profile', MyBB::INPUT_INT),
            'show_header'  => $mybb->get_input('show_header', MyBB::INPUT_INT),
            'filterable'   => $mybb->get_input('filterable', MyBB::INPUT_INT),
            'active'       => $mybb->get_input('active', MyBB::INPUT_INT),
        ];
    }

    $page->add_breadcrumb_item($lang->anschlusssuche_tab_fields, 'index.php?module=tools-anschlusssuche&amp;action=fields&amp;type=' . $type);
    $page->output_header($lang->anschlusssuche);
    anschlusssuche_acp_nav_tabs('field_edit');
    if (!empty($field_errors)) {
        $page->output_inline_error($field_errors);
    }

    $form = new Form('index.php?module=tools-anschlusssuche&amp;action=field_save', 'post');
    echo $form->generate_hidden_field('id', $id);
    echo $form->generate_hidden_field('type', $type);
    $title = $lang->sprintf($id > 0 ? $lang->anschlusssuche_field_edit : $lang->anschlusssuche_field_add, anschlusssuche_acp_type_label($type));
    $container = new FormContainer($title);

    $fieldtypes = [];
    foreach (Anschlusssuche_FieldTypes::TYPES as $ft) {
        $fieldtypes[$ft] = $lang->{'anschlusssuche_fieldtype_' . $ft};
    }
    $v = function (string $key, $default = '') use ($field) {
        return $field && isset($field[$key]) ? $field[$key] : $default;
    };
    $container->output_row($lang->anschlusssuche_field_title . ' <em>*</em>', '', $form->generate_text_box('title', $v('title')), 'title');
    $container->output_row($lang->anschlusssuche_field_fkey . ' <em>*</em>', $lang->anschlusssuche_field_fkey_desc, $form->generate_text_box('fkey', $v('fkey')), 'fkey');
    $container->output_row($lang->anschlusssuche_field_description, $lang->anschlusssuche_field_description_desc, $form->generate_text_area('description', $v('description')), 'description');
    $container->output_row($lang->anschlusssuche_field_fieldtype . ' <em>*</em>', $lang->anschlusssuche_field_fieldtype_desc, $form->generate_select_box('fieldtype', $fieldtypes, $v('fieldtype', 'text')), 'fieldtype');
    $container->output_row($lang->anschlusssuche_field_options, $lang->anschlusssuche_field_options_desc, $form->generate_text_area('options', $v('options')), 'options');
    $container->output_row($lang->anschlusssuche_field_icon, $lang->anschlusssuche_field_icon_desc, $form->generate_text_box('icon', $v('icon')), 'icon');
    $container->output_row($lang->anschlusssuche_field_disporder, '', $form->generate_numeric_field('disporder', (int)$v('disporder', 0), ['min' => 0]), 'disporder');
    $container->output_row($lang->anschlusssuche_field_required, '', $form->generate_yes_no_radio('required', (int)$v('required', 0)));
    $container->output_row($lang->anschlusssuche_field_show_list, $lang->anschlusssuche_field_show_list_desc, $form->generate_yes_no_radio('show_list', (int)$v('show_list', 1)));
    $container->output_row($lang->anschlusssuche_field_show_profile, '', $form->generate_yes_no_radio('show_profile', (int)$v('show_profile', 1)));
    $container->output_row($lang->anschlusssuche_field_show_header, '', $form->generate_yes_no_radio('show_header', (int)$v('show_header', 0)));
    $container->output_row($lang->anschlusssuche_field_filterable, $lang->anschlusssuche_field_filterable_desc, $form->generate_yes_no_radio('filterable', (int)$v('filterable', 0)));
    $container->output_row($lang->anschlusssuche_field_active, $lang->anschlusssuche_field_active_desc, $form->generate_yes_no_radio('active', (int)$v('active', 1)));
    $container->end();
    $form->output_submit_wrapper([$form->generate_submit_button($lang->anschlusssuche_save)]);
    $form->end();
    $page->output_footer();
}

if ($action === 'fields') {
    $type = $mybb->get_input('type');
    if (!in_array($type, $types, true)) {
        $type = 'single';
    }
    $page->add_breadcrumb_item($lang->anschlusssuche_tab_fields);
    $page->output_header($lang->anschlusssuche);
    anschlusssuche_acp_nav_tabs('fields');

    $links = [];
    foreach ($types as $t) {
        $label = htmlspecialchars_uni(anschlusssuche_acp_type_label($t));
        $links[] = $t === $type ? '<strong>' . $label . '</strong>'
            : '<a href="index.php?module=tools-anschlusssuche&amp;action=fields&amp;type=' . $t . '">' . $label . '</a>';
    }
    echo '<p>' . $lang->anschlusssuche_fields_for . ' ' . implode(' | ', $links)
        . ' &mdash; <a href="index.php?module=tools-anschlusssuche&amp;action=field_add&amp;type=' . $type . '">' . $lang->anschlusssuche_field_add_link . '</a></p>';

    $form = new Form('index.php?module=tools-anschlusssuche&amp;action=fields_order', 'post');
    echo $form->generate_hidden_field('type', $type);
    $table = new Table;
    $table->construct_header($lang->anschlusssuche_field_title);
    $table->construct_header($lang->anschlusssuche_field_fkey, ['width' => '12%']);
    $table->construct_header($lang->anschlusssuche_field_fieldtype, ['width' => '12%']);
    $table->construct_header($lang->anschlusssuche_field_flags, ['width' => '25%']);
    $table->construct_header($lang->anschlusssuche_field_disporder, ['width' => '8%', 'class' => 'align_center']);
    $table->construct_header($lang->anschlusssuche_actions, ['width' => '15%', 'class' => 'align_center']);

    foreach (Anschlusssuche_Repository::fields($type, false) as $field) {
        $flags = [];
        foreach (['required', 'show_list', 'show_profile', 'show_header', 'filterable'] as $flag) {
            if (!empty($field[$flag])) {
                $flags[] = $lang->{'anschlusssuche_flag_' . $flag};
            }
        }
        if (empty($field['active'])) {
            $flags[] = '<em>' . $lang->anschlusssuche_flag_inactive . '</em>';
        }
        $icon = $field['icon'] !== '' ? '<i class="' . htmlspecialchars_uni($field['icon']) . '"></i> ' : '';
        $table->construct_cell($icon . htmlspecialchars_uni($field['title']));
        $table->construct_cell('<code>' . htmlspecialchars_uni($field['fkey']) . '</code>');
        $table->construct_cell(htmlspecialchars_uni($lang->{'anschlusssuche_fieldtype_' . $field['fieldtype']} ?? $field['fieldtype']));
        $table->construct_cell(implode(', ', $flags), ['class' => 'smalltext']);
        $table->construct_cell($form->generate_numeric_field('disporder[' . (int)$field['id'] . ']', (int)$field['disporder'], ['style' => 'width: 50px;', 'min' => 0]), ['class' => 'align_center']);
        $table->construct_cell(
            '<a href="index.php?module=tools-anschlusssuche&amp;action=field_edit&amp;id=' . (int)$field['id'] . '">' . $lang->anschlusssuche_edit . '</a>'
            . ' | <a href="index.php?module=tools-anschlusssuche&amp;action=field_delete&amp;id=' . (int)$field['id'] . '">' . $lang->anschlusssuche_delete . '</a>',
            ['class' => 'align_center']
        );
        $table->construct_row();
    }
    if ($table->num_rows() === 0) {
        $table->construct_cell($lang->anschlusssuche_no_fields, ['colspan' => 6]);
        $table->construct_row();
    }
    $table->output($lang->sprintf($lang->anschlusssuche_fields_title, htmlspecialchars_uni(anschlusssuche_acp_type_label($type))));
    $form->output_submit_wrapper([$form->generate_submit_button($lang->anschlusssuche_save_order)]);
    $form->end();
    $page->output_footer();
}

/* ======================================================================
 * Header entries
 * ==================================================================== */

if ($action === 'header_save' && $mybb->request_method === 'post') {
    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    $start = anschlusssuche_acp_parse_date($mybb->get_input('start'));
    $end_day = anschlusssuche_acp_parse_date($mybb->get_input('end'));
    $end = $end_day > 0 ? $end_day + 86400 : 0; // end date inclusive
    $note = trim($mybb->get_input('note'));

    if ($start <= 0 || $end <= $start) {
        flash_message($lang->anschlusssuche_error_period, 'error');
        admin_redirect('index.php?module=tools-anschlusssuche&action=header');
    }
    if ($id > 0) {
        Anschlusssuche_HeaderService::update($id, $start, $end, $note);
        log_admin_action(['id' => $id]);
    } else {
        $entry = null;
        $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
        $entry_id = $mybb->get_input('entry_id', MyBB::INPUT_INT);
        if ($tid > 0) {
            $entry = Anschlusssuche_Repository::entry_by_tid($tid);
        } elseif ($entry_id > 0) {
            $entry = Anschlusssuche_Repository::entry($entry_id);
        }
        if (!$entry) {
            flash_message($lang->anschlusssuche_error_header_target, 'error');
            admin_redirect('index.php?module=tools-anschlusssuche&action=header');
        }
        $new_id = Anschlusssuche_HeaderService::add([
            'entry_id'   => (int)$entry['id'],
            'source'     => 'acp',
            'owner_uid'  => (int)$entry['uid'],
            'start_at'   => $start,
            'end_at'     => $end,
            'note'       => $note,
            'created_by' => (int)$mybb->user['uid'],
        ]);
        log_admin_action(['id' => $new_id, 'entry_id' => (int)$entry['id']]);
    }
    flash_message($lang->anschlusssuche_header_saved, 'success');
    admin_redirect('index.php?module=tools-anschlusssuche&action=header');
}

if ($action === 'header_delete') {
    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=tools-anschlusssuche&action=header');
    }
    if ($mybb->request_method === 'post') {
        Anschlusssuche_HeaderService::delete($id);
        log_admin_action(['id' => $id]);
        flash_message($lang->anschlusssuche_header_deleted, 'success');
        admin_redirect('index.php?module=tools-anschlusssuche&action=header');
    }
    $page->output_confirm_action('index.php?module=tools-anschlusssuche&amp;action=header_delete&amp;id=' . $id, $lang->anschlusssuche_header_delete_confirm);
}

if ($action === 'header' || $action === 'header_edit') {
    $page->add_breadcrumb_item($lang->anschlusssuche_tab_header);
    $page->output_header($lang->anschlusssuche);
    anschlusssuche_acp_nav_tabs('header');

    $rows = [];
    $entry_ids = [];
    $query = $db->simple_select('anschlusssuche_header', '*', '', ['order_by' => 'start_at', 'order_dir' => 'DESC', 'limit' => 200]);
    while ($row = $db->fetch_array($query)) {
        $rows[] = $row;
        if (!empty($row['entry_id'])) {
            $entry_ids[] = (int)$row['entry_id'];
        }
    }
    $entries = Anschlusssuche_Repository::entries_by_ids($entry_ids);

    $table = new Table;
    $table->construct_header($lang->anschlusssuche_header_target);
    $table->construct_header($lang->anschlusssuche_header_period, ['width' => '22%']);
    $table->construct_header($lang->anschlusssuche_header_source, ['width' => '10%']);
    $table->construct_header($lang->anschlusssuche_header_note, ['width' => '20%']);
    $table->construct_header($lang->anschlusssuche_actions, ['width' => '12%', 'class' => 'align_center']);

    foreach ($rows as $row) {
        $target = '-';
        if (!empty($row['entry_id']) && isset($entries[(int)$row['entry_id']])) {
            $e = $entries[(int)$row['entry_id']];
            if (!empty($e['tid'])) {
                $thread = get_thread((int)$e['tid']);
                $target = $thread ? '<a href="../' . get_thread_link((int)$e['tid']) . '" target="_blank">' . htmlspecialchars_uni($thread['subject']) . '</a>' : '#' . (int)$e['tid'];
            } else {
                $target = htmlspecialchars_uni($e['title']) . ' <span class="smalltext">(' . $lang->anschlusssuche_type_loose . ')</span>';
            }
        } elseif (!empty($row['relatives_person_id'])) {
            $target = $lang->sprintf($lang->anschlusssuche_header_relatives_person, (int)$row['relatives_person_id']);
        }
        $period = my_date($mybb->settings['dateformat'], (int)$row['start_at']) . ' &ndash; '
            . my_date($mybb->settings['dateformat'], (int)$row['end_at'] - 1);
        if ((int)$row['end_at'] <= TIME_NOW) {
            $period = '<span style="opacity: 0.6;">' . $period . '</span>';
        } elseif ((int)$row['start_at'] <= TIME_NOW) {
            $period = '<strong>' . $period . '</strong>';
        }
        $actions = '<a href="index.php?module=tools-anschlusssuche&amp;action=header_edit&amp;id=' . (int)$row['id'] . '">' . $lang->anschlusssuche_edit . '</a>'
            . ' | <a href="index.php?module=tools-anschlusssuche&amp;action=header_delete&amp;id=' . (int)$row['id'] . '">' . $lang->anschlusssuche_delete . '</a>';
        $table->construct_cell($target);
        $table->construct_cell($period);
        $table->construct_cell(htmlspecialchars_uni($lang->{'anschlusssuche_source_' . $row['source']} ?? $row['source']));
        $table->construct_cell(htmlspecialchars_uni($row['note']), ['class' => 'smalltext']);
        $table->construct_cell($actions, ['class' => 'align_center']);
        $table->construct_row();
    }
    if ($table->num_rows() === 0) {
        $table->construct_cell($lang->anschlusssuche_no_header, ['colspan' => 5]);
        $table->construct_row();
    }
    $table->output($lang->anschlusssuche_header_title);

    $edit = null;
    if ($action === 'header_edit') {
        $edit = $db->fetch_array($db->simple_select('anschlusssuche_header', '*', 'id = ' . $mybb->get_input('id', MyBB::INPUT_INT)));
    }
    $form = new Form('index.php?module=tools-anschlusssuche&amp;action=header_save', 'post');
    $container = new FormContainer($edit ? $lang->anschlusssuche_header_edit : $lang->anschlusssuche_header_add);
    if ($edit) {
        echo $form->generate_hidden_field('id', (int)$edit['id']);
    } else {
        $container->output_row($lang->anschlusssuche_header_tid, $lang->anschlusssuche_header_tid_desc, $form->generate_numeric_field('tid', '', ['min' => 0]), 'tid');
        $container->output_row($lang->anschlusssuche_header_entry_id, $lang->anschlusssuche_header_entry_id_desc, $form->generate_numeric_field('entry_id', '', ['min' => 0]), 'entry_id');
    }
    $container->output_row($lang->anschlusssuche_header_start . ' <em>*</em>', $lang->anschlusssuche_header_date_desc,
        $form->generate_text_box('start', $edit ? date('Y-m-d', (int)$edit['start_at']) : date('Y-m-d'), ['type' => 'date']), 'start');
    $container->output_row($lang->anschlusssuche_header_end . ' <em>*</em>', $lang->anschlusssuche_header_end_desc,
        $form->generate_text_box('end', $edit ? date('Y-m-d', (int)$edit['end_at'] - 1) : date('Y-m-d', TIME_NOW + 6 * 86400)), 'end');
    $container->output_row($lang->anschlusssuche_header_note, '', $form->generate_text_box('note', $edit ? $edit['note'] : ''), 'note');
    $container->end();
    $form->output_submit_wrapper([$form->generate_submit_button($lang->anschlusssuche_save)]);
    $form->end();

    $page->output_footer();
}

// Unknown action → overview
admin_redirect('index.php?module=tools-anschlusssuche');
