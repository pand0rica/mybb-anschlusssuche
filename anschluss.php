<?php
/**
 * Anschluss-Suchmaschine: Such- und Filterseite über alle Gesuche
 * (Einzel-, Gruppen- und lose Gesuche inkl. Relatives).
 *
 * anschlusssuche-root-file (marker for migrations: replaces the old prototype)
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'anschluss.php');

require_once './global.php';

$lang->load('anschlusssuche');

if (!function_exists('anschlusssuche_enabled') || !anschlusssuche_enabled()) {
    error($lang->anschlusssuche_disabled);
}
if (empty($mybb->user['uid']) && empty($mybb->settings['anschlusssuche_search_guests'])) {
    error_no_permission();
}

add_breadcrumb($lang->anschlusssuche_search_title, 'anschluss.php');

$plugins->run_hooks('anschlusssuche_search_start');

$params = Anschlusssuche_SearchService::params_from_input();
$perpage = max(1, (int)$mybb->settings['anschlusssuche_results_per_page']);
$page = max(1, $mybb->get_input('page', MyBB::INPUT_INT));
$result = Anschlusssuche_SearchService::search($params, $page, $perpage);

$results = '';
foreach ($result['items'] as $entry) {
    $results .= Anschlusssuche_Renderer::card($entry);
}
if ($results === '') {
    $results = Anschlusssuche_Renderer::render('anschlusssuche_search_noresults');
}

$tabs = '';
foreach (['all', 'single', 'group', 'loose'] as $tab) {
    $tabs .= Anschlusssuche_Renderer::render('anschlusssuche_search_tab', [
        'tab_type'   => $tab,
        'tab_label'  => htmlspecialchars_uni($lang->{'anschlusssuche_type_' . $tab}),
        'tab_active' => $params['type'] === $tab ? ' anschlusssuche-tab--active' : '',
    ]);
}

$multipage = multipage($result['total'], $perpage, $page, 'anschluss.php?' . Anschlusssuche_SearchService::query_string($params) . '&amp;page={page}');

$plugins->run_hooks('anschlusssuche_search_end');

output_page(Anschlusssuche_Renderer::render('anschlusssuche_search', [
    'tabs'            => $tabs,
    'search_type'     => htmlspecialchars_uni($params['type']),
    'search_q'        => htmlspecialchars_uni($params['q']),
    'filter_rows'     => Anschlusssuche_Renderer::filter_rows($params['type'], $params, true),
    'archive_checked' => !empty($params['archive']) ? ' checked="checked"' : '',
    'results'         => $results,
    'result_total'    => (int)$result['total'],
    'multipage'       => $multipage,
    'jsurl'           => htmlspecialchars_uni($mybb->asset_url . '/jscripts/anschlusssuche.js'),
]));
