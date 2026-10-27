<?php
/**
 * UserCP: eigene lose Gesuche anlegen, bearbeiten, löschen.
 * Aufgerufen aus anschlusssuche_handle_usercp() (Hook usercp_start).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function anschlusssuche_usercp_dispatch(string $action): void
{
    global $mybb, $lang;

    anschlusssuche_lang();
    if (!anschlusssuche_enabled()) {
        error($lang->anschlusssuche_disabled);
    }
    if (empty($mybb->user['uid'])) {
        error_no_permission();
    }

    switch ($action) {
        case 'anschlusssuche_loose':
            anschlusssuche_usercp_list();
            break;
        case 'anschlusssuche_loose_edit':
            anschlusssuche_usercp_edit_form(null, []);
            break;
        case 'do_anschlusssuche_loose_save':
            anschlusssuche_usercp_save();
            break;
        case 'do_anschlusssuche_loose_delete':
            anschlusssuche_usercp_delete();
            break;
        default:
            return;
    }
    exit;
}

/** Eintrag laden, den der aktuelle User bearbeiten darf (eigener oder Admin). */
function anschlusssuche_usercp_load_own(int $id): array
{
    global $mybb;

    $entry = Anschlusssuche_Repository::entry($id);
    if (!$entry || $entry['source'] !== 'loose') {
        error_no_permission();
    }
    if ((int)$entry['uid'] !== (int)$mybb->user['uid'] && empty($mybb->usergroup['cancp'])) {
        error_no_permission();
    }
    $list = [$id => $entry];
    Anschlusssuche_Repository::attach_details($list);
    return $list[$id];
}

function anschlusssuche_usercp_list(): void
{
    global $mybb, $lang, $templates, $theme;

    add_breadcrumb($lang->nav_usercp, 'usercp.php');
    add_breadcrumb($lang->anschlusssuche_usercp_title, 'usercp.php?action=anschlusssuche_loose');

    $rows = '';
    $trow = 'trow1';
    foreach (Anschlusssuche_Repository::loose_entries_of((int)$mybb->user['uid']) as $entry) {
        $rows .= Anschlusssuche_Renderer::render('anschlusssuche_usercp_loose_row', [
            'trow'            => $trow,
            'row_id'          => (int)$entry['id'],
            'row_title'       => htmlspecialchars_uni((string)$entry['title']),
            'row_fields'      => Anschlusssuche_Renderer::fieldline($entry, 'show_list'),
            'row_status'      => Anschlusssuche_Renderer::status_badge($entry),
            'row_updated'     => my_date('relative', (int)$entry['updated_at']),
            'confirm_message' => htmlspecialchars_uni($lang->anschlusssuche_delete_confirm),
        ]);
        $trow = alt_trow();
    }
    if ($rows === '') {
        $rows = Anschlusssuche_Renderer::render('anschlusssuche_usercp_loose_empty');
    }

    $new_button = Anschlusssuche_Renderer::can_create_loose() && !anschlusssuche_usercp_limit_reached()
        ? Anschlusssuche_Renderer::render('anschlusssuche_usercp_loose_new') : '';
    $max = (int)$mybb->settings['anschlusssuche_loose_max_per_user'];
    $limit_note = $max > 0 ? htmlspecialchars_uni($lang->sprintf($lang->anschlusssuche_usercp_limit, $max)) : '';

    output_page(Anschlusssuche_Renderer::render('anschlusssuche_usercp_loose_list', [
        'rows'       => $rows,
        'new_button' => $new_button,
        'limit_note' => $limit_note,
        'jsurl'      => htmlspecialchars_uni($mybb->asset_url . '/jscripts/anschlusssuche.js'),
    ]));
}

function anschlusssuche_usercp_limit_reached(): bool
{
    global $mybb;
    $max = (int)$mybb->settings['anschlusssuche_loose_max_per_user'];
    return $max > 0 && Anschlusssuche_Repository::count_loose_of((int)$mybb->user['uid']) >= $max;
}

/**
 * @param array|null $input  Formularwerte nach Fehlern, sonst null
 * @param string[]   $errors
 */
function anschlusssuche_usercp_edit_form(?array $input, array $errors): void
{
    global $mybb, $lang;

    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    if ($id > 0) {
        $entry = anschlusssuche_usercp_load_own($id);
        $page_title = htmlspecialchars_uni($lang->anschlusssuche_loose_edit);
    } else {
        if (!Anschlusssuche_Renderer::can_create_loose()) {
            error_no_permission();
        }
        if (anschlusssuche_usercp_limit_reached()) {
            error($lang->sprintf($lang->anschlusssuche_usercp_limit, (int)$mybb->settings['anschlusssuche_loose_max_per_user']));
        }
        $entry = null;
        $page_title = htmlspecialchars_uni($lang->anschlusssuche_loose_new);
    }
    if ($input === null) {
        $input = Anschlusssuche_FormHandler::input_from_entry('loose', $entry);
    }

    add_breadcrumb($lang->nav_usercp, 'usercp.php');
    add_breadcrumb($lang->anschlusssuche_usercp_title, 'usercp.php?action=anschlusssuche_loose');
    add_breadcrumb($page_title);

    output_page(Anschlusssuche_Renderer::render('anschlusssuche_usercp_loose_edit', [
        'page_title' => $page_title,
        'entry_id'   => $id,
        'form_rows'  => Anschlusssuche_FormHandler::render_rows('loose', $input, true),
        'errors'     => $errors ? inline_error(array_map('htmlspecialchars_uni', $errors)) : '',
    ]));
}

function anschlusssuche_usercp_save(): void
{
    global $mybb, $lang;

    if ($mybb->request_method !== 'post') {
        error_no_permission();
    }
    verify_post_check($mybb->get_input('my_post_key'));

    $id = $mybb->get_input('id', MyBB::INPUT_INT);
    $existing = null;
    if ($id > 0) {
        $existing = anschlusssuche_usercp_load_own($id);
    } else {
        if (!Anschlusssuche_Renderer::can_create_loose()) {
            error_no_permission();
        }
        if (anschlusssuche_usercp_limit_reached()) {
            error($lang->sprintf($lang->anschlusssuche_usercp_limit, (int)$mybb->settings['anschlusssuche_loose_max_per_user']));
        }
    }

    $input = Anschlusssuche_FormHandler::read_input('loose');
    $errors = Anschlusssuche_FormHandler::validate_input('loose', $input, true);
    if ($errors) {
        anschlusssuche_usercp_edit_form($input, $errors);
        return;
    }

    $entry = $input['core'] + [
        'source' => 'loose',
        'type'   => 'loose',
        'uid'    => $existing ? (int)$existing['uid'] : (int)$mybb->user['uid'],
    ];
    if ($existing) {
        $entry['id'] = (int)$existing['id'];
    }
    Anschlusssuche_EntryService::save($entry, $input['values'], $input['links']);
    redirect('usercp.php?action=anschlusssuche_loose', $lang->anschlusssuche_saved);
}

function anschlusssuche_usercp_delete(): void
{
    global $mybb, $lang;

    if ($mybb->request_method !== 'post') {
        error_no_permission();
    }
    verify_post_check($mybb->get_input('my_post_key'));
    $entry = anschlusssuche_usercp_load_own($mybb->get_input('id', MyBB::INPUT_INT));
    Anschlusssuche_Repository::delete_entry((int)$entry['id']);
    redirect('usercp.php?action=anschlusssuche_loose', $lang->anschlusssuche_deleted);
}
