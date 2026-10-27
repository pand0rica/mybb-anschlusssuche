<?php
/**
 * CLI lifecycle helper (dev only, not deployed to live):
 *   php tests/cli-plugin.php install|uninstall|status
 * Run from the forum root copy (tests/ lies next to inc/).
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only.');
}
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'cli-plugin.php');
define('NO_PLUGINS', 1); // other plugins (e.g. xThreads) may not run under every PHP version
chdir(dirname(__DIR__));
require_once './inc/init.php';

if (!defined('MYBB_ADMIN_DIR')) {
    $admin_dir = isset($config['admin_dir']) ? $config['admin_dir'] : 'admin';
    define('MYBB_ADMIN_DIR', MYBB_ROOT . $admin_dir . '/');
}
require_once MYBB_ROOT . 'inc/plugins/anschlusssuche.php';

function anschlusssuche_cli_set_active(bool $active): void
{
    global $cache;
    $c = $cache->read('plugins');
    if (!is_array($c)) {
        $c = [];
    }
    if (!isset($c['active']) || !is_array($c['active'])) {
        $c['active'] = [];
    }
    if ($active) {
        $c['active']['anschlusssuche'] = 'anschlusssuche';
    } else {
        unset($c['active']['anschlusssuche']);
    }
    $cache->update('plugins', $c);
}

function anschlusssuche_cli_status(): void
{
    global $db;

    foreach (Anschlusssuche_Installer::TABLES as $table) {
        echo str_pad($table, 32) . ($db->table_exists($table) ? 'EXISTS' : 'MISSING') . "\n";
    }
    $count = function (string $table, string $where): int {
        global $db;
        return (int)$db->fetch_field($db->simple_select($table, 'COUNT(*) AS c', $where), 'c');
    };
    echo 'settings:          ' . $count('settings', "name LIKE 'anschlusssuche\\_%'") . "\n";
    echo 'settinggroups:     ' . $count('settinggroups', "name = 'anschlusssuche'") . "\n";
    echo 'templates:         ' . $count('templates', "title LIKE 'anschlusssuche\\_%'") . "\n";
    echo 'stylesheets:       ' . $count('themestylesheets', "name = 'anschlusssuche.css'") . "\n";
    echo 'form markers:      ' . $count('templates', "title IN ('newthread','editpost') AND template LIKE '%anschlusssuche_fields%'") . "\n";
    echo 'usercp nav links:  ' . $count('templates', "title = 'usercp_nav_misc' AND template LIKE '%anschlusssuche_loose%'") . "\n";
    if ($db->table_exists('anschlusssuche_fields')) {
        echo 'fields:            ' . $count('anschlusssuche_fields', '1=1') . "\n";
    }
    $c = $GLOBALS['cache']->read('plugins');
    echo 'active in cache:   ' . (!empty($c['active']['anschlusssuche']) ? 'yes' : 'no') . "\n";
}

$cmd = isset($argv[1]) ? $argv[1] : 'status';
if ($cmd === 'install') {
    if (!anschlusssuche_is_installed()) {
        anschlusssuche_install();
    }
    anschlusssuche_activate();
    anschlusssuche_cli_set_active(true);
} elseif ($cmd === 'uninstall') {
    anschlusssuche_deactivate();
    anschlusssuche_uninstall();
    anschlusssuche_cli_set_active(false);
} elseif ($cmd === 'deactivate') {
    anschlusssuche_deactivate();
    anschlusssuche_cli_set_active(false);
} elseif ($cmd === 'activate') {
    anschlusssuche_activate();
    anschlusssuche_cli_set_active(true);
}
anschlusssuche_cli_status();
