<?php
/**
 * Header-Teaser: feste (zeitgesteuerte) Einträge + Zufallsauffüllung, sowie die
 * öffentliche Anlage-Funktion für feste Einträge (ACP, später NewPoints-Shop).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_HeaderService
{
    public const SOURCES = ['acp', 'shop'];

    /**
     * @return int neue ID oder 0
     */
    public static function add(array $data): int
    {
        global $db;

        $entry_id = isset($data['entry_id']) ? (int)$data['entry_id'] : 0;
        $person_id = isset($data['relatives_person_id']) ? (int)$data['relatives_person_id'] : 0;
        if (($entry_id > 0) === ($person_id > 0)) {
            return 0; // exactly one target
        }
        if ($entry_id > 0 && !Anschlusssuche_Repository::entry($entry_id)) {
            return 0;
        }
        $source = isset($data['source']) && in_array($data['source'], self::SOURCES, true) ? $data['source'] : 'acp';
        $start = isset($data['start_at']) ? (int)$data['start_at'] : 0;
        $end = isset($data['end_at']) ? (int)$data['end_at'] : 0;
        if ($start <= 0 || $end <= $start) {
            return 0;
        }

        $row = [
            'source'     => $source,
            'owner_uid'  => isset($data['owner_uid']) ? max(0, (int)$data['owner_uid']) : 0,
            'start_at'   => $start,
            'end_at'     => $end,
            'note'       => $db->escape_string(my_substr(isset($data['note']) ? (string)$data['note'] : '', 0, 255)),
            'created_by' => isset($data['created_by']) ? max(0, (int)$data['created_by']) : 0,
            'created_at' => TIME_NOW,
        ];
        if ($entry_id > 0) {
            $row['entry_id'] = $entry_id;
        } else {
            $row['relatives_person_id'] = $person_id;
        }
        if (!empty($data['credit_id'])) {
            $row['credit_id'] = (int)$data['credit_id'];
        }
        return (int)$db->insert_query('anschlusssuche_header', $row);
    }

    public static function update(int $id, int $start, int $end, string $note): bool
    {
        global $db;
        if ($id <= 0 || $start <= 0 || $end <= $start) {
            return false;
        }
        $db->update_query('anschlusssuche_header', [
            'start_at' => $start,
            'end_at'   => $end,
            'note'     => $db->escape_string(my_substr($note, 0, 255)),
        ], 'id = ' . $id);
        return true;
    }

    public static function delete(int $id): void
    {
        global $db;
        $db->delete_query('anschlusssuche_header', 'id = ' . $id);
    }

    /**
     * Aktive feste Einträge zum Zeitpunkt $now (start inklusive, end exklusive).
     *
     * @return array list of header rows, ordered by start
     */
    public static function active_fixed(int $now): array
    {
        global $db;
        $out = [];
        $query = $db->simple_select('anschlusssuche_header', '*', "start_at <= {$now} AND end_at > {$now}", ['order_by' => 'start_at, id']);
        while ($row = $db->fetch_array($query)) {
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Wählt die anzuzeigenden Items.
     *
     * @return array list of ['kind' => entry|thread|relatives, ...]
     */
    public static function pick(int $now): array
    {
        global $mybb, $db;

        $count = max(0, (int)$mybb->settings['anschlusssuche_header_count']);
        if ($count === 0) {
            return [];
        }
        $items = [];
        $used_entries = [];
        $used_tids = [];

        // 1. fixed entries (capped)
        foreach (self::active_fixed($now) as $fixed) {
            if (count($items) >= $count) {
                break;
            }
            if (!empty($fixed['entry_id'])) {
                $eid = (int)$fixed['entry_id'];
                if (isset($used_entries[$eid])) {
                    continue;
                }
                $used_entries[$eid] = true;
                $items[] = ['kind' => 'entry', 'entry_id' => $eid, 'fixed' => true];
            } elseif (!empty($fixed['relatives_person_id'])) {
                $items[] = ['kind' => 'relatives', 'person_id' => (int)$fixed['relatives_person_id'], 'fixed' => true];
            }
        }

        // 2. random pool
        $missing = $count - count($items);
        if ($missing > 0) {
            $pool = self::random_pool();
            foreach ($pool as $key => $cand) {
                if (($cand['kind'] === 'entry' && isset($used_entries[$cand['entry_id']]))
                    || ($cand['kind'] === 'thread' && isset($used_tids[$cand['tid']]))) {
                    unset($pool[$key]);
                }
            }
            $pool = array_values($pool);
            if ($pool) {
                $picked = (array)array_rand($pool, min($missing, count($pool)));
                shuffle($picked);
                foreach ($picked as $idx) {
                    $items[] = $pool[$idx] + ['fixed' => false];
                }
            }
        }
        return $items;
    }

    /**
     * Kandidaten-Liste (nur IDs, klein): Threads der Quellforen + optional lose Gesuche.
     */
    private static function random_pool(): array
    {
        global $mybb, $db;

        $pool = [];
        $fids = array_filter(array_map('intval', explode(',', (string)$mybb->settings['anschlusssuche_header_forums'])));
        if ((string)$mybb->settings['anschlusssuche_header_forums'] === '-1') {
            $fids = []; // "all forums" is not meaningful here
        }
        $archive = Anschlusssuche_Repository::archive_fids();
        $fids = array_diff($fids, $archive);

        if ($fids) {
            $where = 't.fid IN (' . implode(',', $fids) . ") AND t.visible = 1 AND t.closed NOT LIKE 'moved|%'";
            $unviewable = get_unviewable_forums(true);
            if ($unviewable) {
                $where .= " AND t.fid NOT IN ({$unviewable})";
            }
            if (!empty($mybb->settings['anschlusssuche_header_free_only'])) {
                $where .= " AND (e.id IS NULL OR e.status = 'free')";
            }
            $query = $db->query("
                SELECT t.tid, e.id AS entry_id
                FROM " . TABLE_PREFIX . "threads t
                LEFT JOIN " . TABLE_PREFIX . "anschlusssuche_entries e ON (e.tid = t.tid)
                WHERE {$where}
            ");
            while ($row = $db->fetch_array($query)) {
                if (!empty($row['entry_id'])) {
                    $pool[] = ['kind' => 'entry', 'entry_id' => (int)$row['entry_id'], 'tid' => (int)$row['tid']];
                } else {
                    $pool[] = ['kind' => 'thread', 'tid' => (int)$row['tid']];
                }
            }
        }
        if (!empty($mybb->settings['anschlusssuche_header_loose'])) {
            $where = "source = 'loose'";
            if (!empty($mybb->settings['anschlusssuche_header_free_only'])) {
                $where .= " AND status = 'free'";
            }
            $query = $db->simple_select('anschlusssuche_entries', 'id', $where);
            while ($row = $db->fetch_array($query)) {
                $pool[] = ['kind' => 'entry', 'entry_id' => (int)$row['id']];
            }
        }
        return $pool;
    }

    /**
     * Rendert $anschlusssuche_header.
     */
    public static function render(): string
    {
        global $mybb, $db, $plugins;

        if (empty($mybb->settings['anschlusssuche_header_enabled']) || !$db->table_exists('anschlusssuche_header')) {
            return '';
        }
        $items = self::pick(TIME_NOW);

        $args = ['items' => &$items];
        if (is_object($plugins)) {
            $plugins->run_hooks('anschlusssuche_header_collect', $args);
        }
        if (!$items) {
            return '';
        }

        // bulk load
        $entry_ids = [];
        $tids = [];
        foreach ($items as $item) {
            if ($item['kind'] === 'entry') {
                $entry_ids[] = (int)$item['entry_id'];
            } elseif ($item['kind'] === 'thread') {
                $tids[] = (int)$item['tid'];
            }
        }
        $entries = Anschlusssuche_Repository::entries_by_ids($entry_ids);
        $threads = [];
        $thread_ids = $tids;
        foreach ($entries as $e) {
            if (!empty($e['tid'])) {
                $thread_ids[] = (int)$e['tid'];
            }
        }
        if ($thread_ids) {
            $query = $db->simple_select('threads', 'tid, subject, fid, visible', 'tid IN (' . implode(',', array_unique($thread_ids)) . ')');
            while ($t = $db->fetch_array($query)) {
                $threads[(int)$t['tid']] = $t;
            }
        }

        $html = '';
        foreach ($items as $item) {
            $html .= self::render_item($item, $entries, $threads);
        }
        if ($html === '') {
            return '';
        }
        return Anschlusssuche_Renderer::render('anschlusssuche_header', ['header_items' => $html]);
    }

    private static function forum_viewable(int $fid): bool
    {
        static $hidden = null;
        if ($hidden === null) {
            $hidden = array_flip(array_filter(array_map('intval', explode(',', str_replace("'", '', (string)get_unviewable_forums(true))))));
        }
        return !isset($hidden[$fid]);
    }

    private static function render_item(array $item, array $entries, array $threads): string
    {
        $title = '';
        $url = '';
        $fields = '';
        $item_class = !empty($item['fixed']) ? 'anschlusssuche-header__item--fixed' : 'anschlusssuche-header__item--random';

        if ($item['kind'] === 'entry') {
            if (!isset($entries[$item['entry_id']])) {
                return '';
            }
            $entry = $entries[$item['entry_id']];
            if (!empty($entry['tid'])) {
                $thread = isset($threads[(int)$entry['tid']]) ? $threads[(int)$entry['tid']] : null;
                if (!$thread || (int)$thread['visible'] !== 1 || !self::forum_viewable((int)$thread['fid'])) {
                    return '';
                }
                $title = (string)$threads[(int)$entry['tid']]['subject'];
                $url = get_thread_link((int)$entry['tid']);
            } else {
                $title = (string)$entry['title'];
                $url = get_profile_link((int)$entry['uid']);
            }
            $fields = Anschlusssuche_Renderer::fieldline($entry, 'show_header');
        } elseif ($item['kind'] === 'thread') {
            if (!isset($threads[$item['tid']])) {
                return '';
            }
            $title = (string)$threads[$item['tid']]['subject'];
            $url = get_thread_link((int)$item['tid']);
        } elseif ($item['kind'] === 'relatives') {
            $person = Anschlusssuche_RelativesAdapter::get((int)$item['person_id']);
            if (!$person) {
                return '';
            }
            $title = (string)$person['title'];
            $url = $person['link'] !== '' ? htmlspecialchars_uni($person['link']) : 'anschluss.php?type=loose';
            $fields = Anschlusssuche_Renderer::fieldline($person, 'show_header');
        }

        return Anschlusssuche_Renderer::render('anschlusssuche_header_item', [
            'item_title'  => htmlspecialchars_uni($title),
            'item_url'    => $url,
            'item_fields' => $fields,
            'item_class'  => $item_class,
        ]);
    }
}
