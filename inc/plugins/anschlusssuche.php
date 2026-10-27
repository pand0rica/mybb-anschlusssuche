<?php
/**
 * Anschluss-Suchmaschine – zentrale Gesuche-Verwaltung (Einzel-, Gruppen- und lose Gesuche).
 *
 * Diese Datei enthält nur info/Lifecycle, die öffentliche API und dünne Hook-Handler.
 * Die eigentliche Logik liegt in inc/plugins/anschlusssuche/core/.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

define('ANSCHLUSSSUCHE_DIR', MYBB_ROOT . 'inc/plugins/anschlusssuche/');

require_once ANSCHLUSSSUCHE_DIR . 'core/Installer.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/Repository.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/FieldTypes.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/EntryService.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/Renderer.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/AccountResolver.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/RelativesAdapter.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/HeaderService.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/SearchService.php';
require_once ANSCHLUSSSUCHE_DIR . 'core/FormHandler.php';

if (isset($plugins) && is_object($plugins)) {
    if (defined('IN_ADMINCP')) {
        $plugins->add_hook('admin_tools_menu', 'anschlusssuche_admin_menu');
        $plugins->add_hook('admin_tools_action_handler', 'anschlusssuche_admin_action_handler');
        $plugins->add_hook('admin_tools_permissions', 'anschlusssuche_admin_permissions');
    } else {
        $plugins->add_hook('global_start', 'anschlusssuche_handle_global_start');
        $plugins->add_hook('global_intermediate', 'anschlusssuche_handle_header');

        $plugins->add_hook('newthread_start', 'anschlusssuche_handle_newthread_form');
        $plugins->add_hook('newthread_do_newthread_end', 'anschlusssuche_handle_newthread_save');
        $plugins->add_hook('editpost_action_start', 'anschlusssuche_handle_editpost_form');
        $plugins->add_hook('editpost_do_editpost_end', 'anschlusssuche_handle_editpost_save');

        $plugins->add_hook('forumdisplay_start', 'anschlusssuche_handle_forumdisplay_start');
        $plugins->add_hook('forumdisplay_before_thread', 'anschlusssuche_handle_forumdisplay_bulk');
        $plugins->add_hook('forumdisplay_thread_end', 'anschlusssuche_handle_forumdisplay_thread');
        $plugins->add_hook('forumdisplay_end', 'anschlusssuche_handle_forumdisplay_end');

        $plugins->add_hook('member_profile_end', 'anschlusssuche_handle_profile');

        $plugins->add_hook('usercp_menu', 'anschlusssuche_handle_usercp_menu', 40);
        $plugins->add_hook('usercp_start', 'anschlusssuche_handle_usercp');
    }

    // Validation runs inside the post datahandler (front end only in practice).
    $plugins->add_hook('datahandler_post_validate_thread', 'anschlusssuche_handle_validate_thread');
    $plugins->add_hook('datahandler_post_validate_post', 'anschlusssuche_handle_validate_post');

    // Moderation + user deletion can also run from the ACP.
    $plugins->add_hook('class_moderation_delete_thread', 'anschlusssuche_handle_delete_thread');
    $plugins->add_hook('class_moderation_move_simple', 'anschlusssuche_handle_move_single');
    $plugins->add_hook('class_moderation_move_thread_redirect', 'anschlusssuche_handle_move_single');
    $plugins->add_hook('class_moderation_move_threads', 'anschlusssuche_handle_move_threads');
    $plugins->add_hook('class_moderation_merge_threads', 'anschlusssuche_handle_merge_threads');
    $plugins->add_hook('datahandler_user_delete_start', 'anschlusssuche_handle_user_delete');
}

/* -------------------------------------------------------------------------
 * Plugin info + lifecycle
 * ---------------------------------------------------------------------- */

function anschlusssuche_info(): array
{
    return [
        'name'          => 'Anschluss-Suchmaschine',
        'description'   => 'Central management for wanted-character ads: single, group and loose ads with configurable fields, thread list, profile overview, header teaser and search.',
        'website'       => 'https://github.com/pand0rica/anschlusssuche',
        'author'        => 'pand0rica',
        'authorsite'    => 'https://github.com/pand0rica',
        'version'       => '1.0.0',
        'guid'          => '',
        'codename'      => 'anschlusssuche',
        'compatibility' => '18*',
    ];
}

function anschlusssuche_install(): void
{
    Anschlusssuche_Installer::install();
}

function anschlusssuche_is_installed(): bool
{
    return Anschlusssuche_Installer::is_installed();
}

function anschlusssuche_uninstall(): void
{
    Anschlusssuche_Installer::uninstall();
}

function anschlusssuche_activate(): void
{
    Anschlusssuche_Installer::activate();
}

function anschlusssuche_deactivate(): void
{
    Anschlusssuche_Installer::deactivate();
}

/* -------------------------------------------------------------------------
 * Public API (stable signatures – used by migrations and other plugins)
 * ---------------------------------------------------------------------- */

/**
 * Legt einen Eintrag an oder aktualisiert ihn (über id oder tid).
 *
 * @param array $entry  Kernfelder (source, tid, fid, uid, type, title, status, status_manual,
 *                      slots_total, slots_taken, slots_open_end, age_min, age_max, age_text, created_at)
 * @param array $values fkey => Rohwert (string bzw. array bei Typ multi) für alle Nicht-links-Felder
 * @param array $links  fkey => Liste von ['name' => …, 'role' => …, 'uid' => …]
 * @return int Eintrags-ID (0 bei Fehler)
 */
function anschlusssuche_entry_save(array $entry, array $values = [], array $links = []): int
{
    return Anschlusssuche_EntryService::save($entry, $values, $links);
}

/**
 * Legt einen festen Header-Eintrag an.
 *
 * @param array $data entry_id|relatives_person_id, source (acp|shop), owner_uid, start_at, end_at,
 *                    note, credit_id, created_by
 * @return int neue ID (0 bei ungültigen Daten)
 */
function anschlusssuche_header_add(array $data): int
{
    return Anschlusssuche_HeaderService::add($data);
}

/**
 * Liefert alle Einträge der angegebenen Accounts (inkl. Werte/Links), gruppiert nach nichts.
 *
 * @param int[] $uids
 * @param array $opts include_done (bool), viewer_check (bool, default true)
 */
function anschlusssuche_get_entries_for_uids(array $uids, array $opts = []): array
{
    return Anschlusssuche_Repository::entries_for_uids($uids, $opts);
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

function anschlusssuche_enabled(): bool
{
    global $mybb;
    return !empty($mybb->settings['anschlusssuche_enabled']);
}

function anschlusssuche_lang(): void
{
    global $lang;
    static $loaded = false;
    if (!$loaded && is_object($lang)) {
        $lang->load('anschlusssuche');
        $loaded = true;
    }
}

/* -------------------------------------------------------------------------
 * Front-end hook handlers
 * ---------------------------------------------------------------------- */

function anschlusssuche_handle_global_start(): void
{
    global $templatelist;

    if (!anschlusssuche_enabled()) {
        return;
    }
    anschlusssuche_lang();

    $list = Anschlusssuche_Renderer::templates_for_script(defined('THIS_SCRIPT') ? THIS_SCRIPT : '');
    if ($list) {
        $templatelist = (isset($templatelist) && $templatelist !== '' ? $templatelist . ',' : '') . implode(',', $list);
    }
}

function anschlusssuche_handle_header(): void
{
    global $anschlusssuche_header;

    $anschlusssuche_header = '';
    if (!anschlusssuche_enabled()) {
        return;
    }
    $anschlusssuche_header = Anschlusssuche_HeaderService::render();
}

function anschlusssuche_handle_newthread_form(): void
{
    global $anschlusssuche_fields, $fid;

    $anschlusssuche_fields = '';
    if (!anschlusssuche_enabled()) {
        return;
    }
    $anschlusssuche_fields = Anschlusssuche_FormHandler::thread_form((int)$fid, null);
}

function anschlusssuche_handle_editpost_form(): void
{
    global $anschlusssuche_fields, $thread, $post;

    $anschlusssuche_fields = '';
    if (!anschlusssuche_enabled() || empty($thread) || empty($post)) {
        return;
    }
    if ((int)$thread['firstpost'] !== (int)$post['pid']) {
        return;
    }
    $anschlusssuche_fields = Anschlusssuche_FormHandler::thread_form((int)$thread['fid'], (int)$thread['tid']);
}

/**
 * @param PostDataHandler $handler
 */
function anschlusssuche_handle_validate_thread($handler): void
{
    if (!anschlusssuche_enabled() || !defined('THIS_SCRIPT') || THIS_SCRIPT !== 'newthread.php') {
        return;
    }
    $fid = isset($handler->data['fid']) ? (int)$handler->data['fid'] : 0;
    Anschlusssuche_FormHandler::validate_thread($handler, $fid);
}

/**
 * @param PostDataHandler $handler
 */
function anschlusssuche_handle_validate_post($handler): void
{
    if (!anschlusssuche_enabled() || !defined('THIS_SCRIPT') || THIS_SCRIPT !== 'editpost.php') {
        return;
    }
    if (empty($handler->first_post) || !Anschlusssuche_FormHandler::form_submitted()) {
        return;
    }
    $fid = isset($handler->data['fid']) ? (int)$handler->data['fid'] : 0;
    if ($fid <= 0 && isset($handler->data['tid'])) {
        $thread = get_thread((int)$handler->data['tid']);
        $fid = $thread ? (int)$thread['fid'] : 0;
    }
    Anschlusssuche_FormHandler::validate_thread($handler, $fid);
}

function anschlusssuche_handle_newthread_save(): void
{
    global $tid, $fid, $mybb;

    if (!anschlusssuche_enabled() || empty($tid) || !Anschlusssuche_FormHandler::form_submitted()) {
        return;
    }
    Anschlusssuche_FormHandler::save_thread((int)$tid, (int)$fid, (int)$mybb->user['uid']);
}

function anschlusssuche_handle_editpost_save(): void
{
    global $thread, $post;

    if (!anschlusssuche_enabled() || empty($thread) || empty($post)) {
        return;
    }
    if ((int)$thread['firstpost'] !== (int)$post['pid'] || !Anschlusssuche_FormHandler::form_submitted()) {
        return;
    }
    Anschlusssuche_FormHandler::save_thread((int)$thread['tid'], (int)$thread['fid'], (int)$thread['uid']);
}

function anschlusssuche_handle_forumdisplay_start(): void
{
    global $templates, $mybb;

    if (!anschlusssuche_enabled()) {
        return;
    }
    $fid = $mybb->get_input('fid', MyBB::INPUT_INT);
    $forum = Anschlusssuche_Repository::forum($fid);
    if (!$forum || $forum['type'] === 'loose') {
        return;
    }
    // Runtime-only swap of the row template (same principle as xThreads template prefixes).
    // get() fills the cache with the raw, theme-resolved template; copy that raw value
    // (get() itself returns an escaped string, which would be escaped twice later).
    $templates->get('anschlusssuche_forumdisplay_thread');
    if (isset($templates->cache['anschlusssuche_forumdisplay_thread'])) {
        $templates->cache['forumdisplay_thread'] = $templates->cache['anschlusssuche_forumdisplay_thread'];
    }
}

/**
 * @param array $args ['threadcache' => &array, 'tids' => &array|string]
 */
function anschlusssuche_handle_forumdisplay_bulk(&$args): void
{
    if (!anschlusssuche_enabled() || !is_array($args)) {
        return;
    }
    $tids = [];
    if (isset($args['threadcache']) && is_array($args['threadcache'])) {
        $tids = array_keys($args['threadcache']);
    }
    Anschlusssuche_Renderer::preload_threads($tids);
}

function anschlusssuche_handle_forumdisplay_thread(): void
{
    global $thread, $anschlusssuche_status, $anschlusssuche_fieldline, $anschlusssuche_links, $anschlusssuche_rowclass;

    $anschlusssuche_status = $anschlusssuche_fieldline = $anschlusssuche_links = $anschlusssuche_rowclass = '';
    if (!anschlusssuche_enabled() || empty($thread['tid'])) {
        return;
    }
    $row = Anschlusssuche_Renderer::thread_row_vars((int)$thread['tid']);
    $anschlusssuche_status = $row['status'];
    $anschlusssuche_fieldline = $row['fieldline'];
    $anschlusssuche_links = $row['links'];
    $anschlusssuche_rowclass = $row['rowclass'];
}

function anschlusssuche_handle_forumdisplay_end(): void
{
    global $threadslist, $fid;

    if (!anschlusssuche_enabled()) {
        return;
    }
    $forum = Anschlusssuche_Repository::forum((int)$fid);
    if (!$forum) {
        return;
    }
    $block = '';
    if ($forum['type'] === 'loose' && !empty($forum['show_loose_block'])) {
        $block .= Anschlusssuche_Renderer::loose_block($forum);
    }
    if (!empty($forum['show_filterbar']) && empty($forum['is_archive'])) {
        $block = Anschlusssuche_Renderer::filterbar($forum) . $block;
    }
    if ($block !== '') {
        $threadslist = $block . (string)$threadslist;
    }
}

function anschlusssuche_handle_profile(): void
{
    global $anschlusssuche_profile, $memprofile, $mybb;

    $anschlusssuche_profile = '';
    if (!anschlusssuche_enabled() || empty($mybb->settings['anschlusssuche_profile_enabled']) || empty($memprofile['uid'])) {
        return;
    }
    $anschlusssuche_profile = Anschlusssuche_Renderer::profile((int)$memprofile['uid']);
}

function anschlusssuche_handle_usercp_menu(): void
{
    anschlusssuche_lang();
}

function anschlusssuche_handle_usercp(): void
{
    global $mybb;

    $action = $mybb->get_input('action');
    if (strpos($action, 'anschlusssuche_') !== 0 && strpos($action, 'do_anschlusssuche_') !== 0) {
        return;
    }
    require_once ANSCHLUSSSUCHE_DIR . 'modules/usercp.php';
    anschlusssuche_usercp_dispatch($action);
}

/* -------------------------------------------------------------------------
 * Moderation / cleanup handlers
 * ---------------------------------------------------------------------- */

function anschlusssuche_handle_delete_thread($tid)
{
    Anschlusssuche_Repository::delete_by_tid((int)$tid);
    return $tid;
}

function anschlusssuche_handle_move_single(&$args): void
{
    if (is_array($args) && isset($args['tid'], $args['new_fid'])) {
        Anschlusssuche_Repository::update_fid([(int)$args['tid']], (int)$args['new_fid']);
    }
}

function anschlusssuche_handle_move_threads(&$args): void
{
    if (is_array($args) && isset($args['tids'], $args['moveto'])) {
        Anschlusssuche_Repository::update_fid(array_map('intval', (array)$args['tids']), (int)$args['moveto']);
    }
}

function anschlusssuche_handle_merge_threads(&$args): void
{
    // The thread "mergetid" is merged into "tid" and deleted afterwards.
    if (is_array($args) && isset($args['mergetid'])) {
        Anschlusssuche_Repository::delete_by_tid((int)$args['mergetid']);
    }
}

/**
 * @param UserDataHandler $handler
 */
function anschlusssuche_handle_user_delete($handler)
{
    if (is_object($handler) && !empty($handler->delete_uids)) {
        Anschlusssuche_Repository::delete_for_users(array_map('intval', (array)$handler->delete_uids));
    }
    return $handler;
}

/* -------------------------------------------------------------------------
 * ACP integration
 * ---------------------------------------------------------------------- */

function anschlusssuche_admin_menu(&$sub_menu): void
{
    global $lang;
    $lang->load('anschlusssuche');
    $sub_menu[] = [
        'id'    => 'anschlusssuche',
        'title' => $lang->anschlusssuche,
        'link'  => 'index.php?module=tools-anschlusssuche',
    ];
}

function anschlusssuche_admin_action_handler(&$actions): void
{
    $actions['anschlusssuche'] = ['active' => 'anschlusssuche', 'file' => 'anschlusssuche.php'];
}

function anschlusssuche_admin_permissions(&$admin_permissions): void
{
    global $lang;
    $lang->load('anschlusssuche');
    $admin_permissions['anschlusssuche'] = $lang->anschlusssuche_can_manage;
}
