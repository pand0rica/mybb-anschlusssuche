<?php
/**
 * CLI self test (dev only): php tests/cli-selftest.php
 * Requires the plugin to be installed. Creates and removes its own test data
 * (field keys / titles prefixed with "selftest").
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only.');
}
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'cli-selftest.php');
define('NO_PLUGINS', 1);
chdir(dirname(__DIR__));
require_once './inc/init.php';
require_once MYBB_ROOT . 'inc/plugins/anschlusssuche.php';

if (!isset($mybb->user) || !is_array($mybb->user)) {
    $mybb->user = [];
}
$mybb->user += ['uid' => 0, 'usergroup' => 1, 'additionalgroups' => '', 'timezone' => $mybb->settings['timezoneoffset'] ?? 0,
    'dst' => 0, 'dateformat' => '', 'timeformat' => ''];
$lang->set_language('english');
$lang->load('anschlusssuche');

$pass = 0;
$fail = 0;
function check(string $name, $expected, $actual): void
{
    global $pass, $fail;
    if ($expected === $actual) {
        $pass++;
        echo "  ok   {$name}\n";
    } else {
        $fail++;
        echo "  FAIL {$name}: expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . "\n";
    }
}

if (!anschlusssuche_is_installed()) {
    echo "Plugin not installed.\n";
    exit(1);
}

echo "FieldTypes\n";
$radio = ['id' => 0, 'fkey' => 'g', 'title' => 'Gender', 'fieldtype' => 'radio', 'required' => 1, 'options' => "a\nb\n\nb"];
check('options dedupe/trim', ['a', 'b'], Anschlusssuche_FieldTypes::options($radio));
check('radio valid', '', Anschlusssuche_FieldTypes::validate($radio, 'a'));
check('radio invalid option', true, Anschlusssuche_FieldTypes::validate($radio, 'x') !== '');
check('radio required empty', true, Anschlusssuche_FieldTypes::validate($radio, '') !== '');
$multi = ['id' => 0, 'fkey' => 'm', 'title' => 'M', 'fieldtype' => 'multi', 'required' => 0, 'options' => "x\ny"];
check('multi from_input', ['x', 'y'], Anschlusssuche_FieldTypes::from_input($multi, ['m' => ['x', ' y ', '']]));
check('multi invalid', true, Anschlusssuche_FieldTypes::validate($multi, ['x', 'z']) !== '');
check('multi value row', "x\ny", Anschlusssuche_FieldTypes::to_value_row($multi, ['x', 'y', 'x'])['value']);
$age = ['id' => 0, 'fkey' => 'age', 'title' => 'Age', 'fieldtype' => 'age_range', 'required' => 0, 'options' => ''];
check('age valid open end', '', Anschlusssuche_FieldTypes::validate($age, ['min' => '20', 'max' => '', 'text' => '']));
check('age max without min', true, Anschlusssuche_FieldTypes::validate($age, ['min' => '', 'max' => '20', 'text' => '']) !== '');
check('age order', true, Anschlusssuche_FieldTypes::validate($age, ['min' => '30', 'max' => '20', 'text' => '']) !== '');
check('age non-numeric', true, Anschlusssuche_FieldTypes::validate($age, ['min' => '2a', 'max' => '', 'text' => '']) !== '');
check('age columns', ['age_min' => 20, 'age_max' => 39, 'age_text' => 'x'], Anschlusssuche_FieldTypes::to_age_columns(['min' => '20', 'max' => '39', 'text' => ' x ']));
check('age label range', '20 - 39 years', Anschlusssuche_FieldTypes::age_label(['age_min' => 20, 'age_max' => 39, 'age_text' => '']));
check('age label single', '25 years', Anschlusssuche_FieldTypes::age_label(['age_min' => 25, 'age_max' => 25, 'age_text' => '']));
check('age label open', '60+ years', Anschlusssuche_FieldTypes::age_label(['age_min' => 60, 'age_max' => null, 'age_text' => '']));
check('age label text wins', 'mid 20s', Anschlusssuche_FieldTypes::age_label(['age_min' => 24, 'age_max' => 26, 'age_text' => 'mid 20s']));
$links = ['id' => 0, 'fkey' => 'l', 'title' => 'L', 'fieldtype' => 'links', 'required' => 0, 'options' => ''];
$rows = Anschlusssuche_FieldTypes::from_input($links, ['l' => ['name' => ['A', '', 'B'], 'role' => ['r', 'x', ''], 'uid' => ['999999', '', '']]]);
check('links drop empty names', 2, count($rows));
check('links unknown uid reset', 0, $rows[0]['uid']);
check('age overlap sql', '(e.age_min IS NOT NULL AND (e.age_max IS NULL OR e.age_max >= 20) AND e.age_min <= 39)', Anschlusssuche_FieldTypes::age_overlap_sql('20', '39'));

echo "Status logic\n";
check('group full -> taken', 'taken', Anschlusssuche_EntryService::compute_status('group', 'free', 0, 3, 3, 0));
check('group full open end -> free', 'free', Anschlusssuche_EntryService::compute_status('group', 'free', 0, 3, 3, 1));
check('group partial -> free', 'free', Anschlusssuche_EntryService::compute_status('group', 'reserved', 0, 3, 1, 0));
check('group manual keeps', 'reserved', Anschlusssuche_EntryService::compute_status('group', 'reserved', 1, 3, 3, 0));
check('single keeps', 'reserved', Anschlusssuche_EntryService::compute_status('single', 'reserved', 0, 0, 0, 0));
check('invalid status -> free', 'free', Anschlusssuche_EntryService::compute_status('single', 'bogus', 1, 0, 0, 0));

echo "DB roundtrip\n";
$fid_text = Anschlusssuche_Repository::save_field(['type' => 'loose', 'fkey' => 'selftest_text', 'title' => 'selftest text', 'fieldtype' => 'text', 'disporder' => 99, 'active' => 1, 'filterable' => 1]);
$fid_multi = Anschlusssuche_Repository::save_field(['type' => 'loose', 'fkey' => 'selftest_multi', 'title' => 'selftest multi', 'fieldtype' => 'multi', 'options' => "red\ngreen", 'disporder' => 99, 'active' => 1, 'filterable' => 1]);
$fid_links = Anschlusssuche_Repository::save_field(['type' => 'loose', 'fkey' => 'selftest_links', 'title' => 'selftest links', 'fieldtype' => 'links', 'disporder' => 99, 'active' => 1]);
Anschlusssuche_Repository::reset_cache();

$some_user = $db->fetch_array($db->simple_select('users', 'uid, username', '', ['limit' => 1, 'order_by' => 'uid']));
$entry = ['source' => 'loose', 'type' => 'loose', 'uid' => (int)$some_user['uid'], 'title' => 'SELFTEST ' . TIME_NOW, 'status' => 'reserved', 'status_manual' => 1];
$values = ['selftest_text' => 'hello', 'selftest_multi' => ['red', 'green'], 'age' => ['min' => '30', 'max' => '', 'text' => '']];
$link_rows = ['selftest_links' => [['name' => $some_user['username'], 'role' => 'friend', 'uid' => (int)$some_user['uid']], ['name' => 'Nobody', 'role' => '', 'uid' => 0]]];
$id = anschlusssuche_entry_save($entry, $values, $link_rows);
check('entry saved', true, $id > 0);
$entry['id'] = $id;
$id2 = anschlusssuche_entry_save($entry, $values, $link_rows);
check('save again same id', $id, $id2);
$loaded = Anschlusssuche_Repository::entries_by_ids([$id])[$id];
check('status stored', 'reserved', $loaded['status']);
check('age stored (open end)', [30, null], [(int)$loaded['age_min'], $loaded['age_max']]);
check('value text', 'hello', $loaded['values'][$fid_text]['value']);
check('value multi', "red\ngreen", $loaded['values'][$fid_multi]['value']);
check('links count (idempotent)', 2, count($loaded['links'][$fid_links]));
check('link uid kept', (int)$some_user['uid'], (int)$loaded['links'][$fid_links][0]['uid']);
check('link without uid is NULL', null, $loaded['links'][$fid_links][1]['uid']);

$params = ['type' => 'loose', 'status' => '', 'age_min' => '25', 'age_max' => '35', 'q' => 'SELFTEST', 'archive' => 0, 'f' => ['selftest_multi' => 'green']];
$res = Anschlusssuche_SearchService::search($params, 1, 50);
$found = false;
foreach ($res['items'] as $item) {
    if ((int)$item['id'] === $id) {
        $found = true;
    }
}
check('search finds entry (age overlap + multi + q)', true, $found);
$params['age_min'] = '10';
$params['age_max'] = '20';
$res = Anschlusssuche_SearchService::search($params, 1, 50);
$found = false;
foreach ($res['items'] as $item) {
    if ((int)$item['id'] === $id) {
        $found = true;
    }
}
check('search excludes non-overlapping age', false, $found);
$params['f'] = ['selftest_multi' => "gre'en"];
$params['age_min'] = $params['age_max'] = '';
$res = Anschlusssuche_SearchService::search($params, 1, 50);
check('search with quote in filter is safe', true, is_array($res['items']));

echo "Header\n";
$h_now = TIME_NOW;
$hid = anschlusssuche_header_add(['entry_id' => $id, 'source' => 'acp', 'start_at' => $h_now - 10, 'end_at' => $h_now + 3600]);
check('header add', true, $hid > 0);
check('header invalid period', 0, anschlusssuche_header_add(['entry_id' => $id, 'start_at' => $h_now, 'end_at' => $h_now - 1]));
check('header needs exactly one target', 0, anschlusssuche_header_add(['start_at' => $h_now, 'end_at' => $h_now + 1]));
$active = Anschlusssuche_HeaderService::active_fixed($h_now);
$ids = array_map(function ($r) { return (int)$r['id']; }, $active);
check('header active now', true, in_array($hid, $ids, true));
$ids = array_map(function ($r) { return (int)$r['id']; }, Anschlusssuche_HeaderService::active_fixed($h_now + 3600));
check('header end exclusive', false, in_array($hid, $ids, true));
$mybb->settings['anschlusssuche_header_count'] = 1;
$picked = Anschlusssuche_HeaderService::pick($h_now);
check('header fixed first + capped', [1, 'entry', $id], [count($picked), $picked[0]['kind'] ?? '', $picked[0]['entry_id'] ?? 0]);

echo "Cleanup\n";
Anschlusssuche_Repository::delete_entry($id);
check('entry deleted incl. header', 0, (int)$db->fetch_field($db->simple_select('anschlusssuche_header', 'COUNT(*) AS c', 'entry_id = ' . $id), 'c'));
foreach ([$fid_text, $fid_multi, $fid_links] as $f) {
    Anschlusssuche_Repository::delete_field($f);
}
check('values gone', 0, (int)$db->fetch_field($db->simple_select('anschlusssuche_values', 'COUNT(*) AS c', 'entry_id = ' . $id), 'c'));

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
