<?php
/**
 * Alle DB-Zugriffe auf die eigenen Tabellen. Werte werden roh gelesen/geschrieben –
 * Escaping für die Ausgabe passiert ausschließlich im Renderer.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_Repository
{
    public const TYPES = ['single', 'group', 'loose'];

    /** @var array<int, array>|null */
    private static $forums = null;

    /** @var array<string, array<int, array>> */
    private static $fields = [];

    /* ------------------------------------------------------------------ */
    /* Forums                                                               */
    /* ------------------------------------------------------------------ */

    /** @return array<int, array> fid => row */
    public static function forums(): array
    {
        global $db;

        if (self::$forums === null) {
            self::$forums = [];
            if ($db->table_exists('anschlusssuche_forums')) {
                $query = $db->simple_select('anschlusssuche_forums', '*');
                while ($row = $db->fetch_array($query)) {
                    $row['fid'] = (int)$row['fid'];
                    self::$forums[$row['fid']] = $row;
                }
            }
        }
        return self::$forums;
    }

    public static function forum(int $fid): ?array
    {
        $forums = self::forums();
        return isset($forums[$fid]) ? $forums[$fid] : null;
    }

    /** @return int[] fids of archive forums */
    public static function archive_fids(): array
    {
        $out = [];
        foreach (self::forums() as $fid => $row) {
            if (!empty($row['is_archive'])) {
                $out[] = (int)$fid;
            }
        }
        return $out;
    }

    public static function save_forum(int $fid, string $type, int $is_archive, int $show_filterbar, int $show_loose_block): void
    {
        global $db;

        if (!in_array($type, self::TYPES, true) || $fid <= 0) {
            return;
        }
        $data = [
            'fid'              => $fid,
            'type'             => $db->escape_string($type),
            'is_archive'       => $is_archive ? 1 : 0,
            'show_filterbar'   => $show_filterbar ? 1 : 0,
            'show_loose_block' => $show_loose_block ? 1 : 0,
        ];
        $db->replace_query('anschlusssuche_forums', $data, 'fid');
        self::$forums = null;
    }

    public static function delete_forum(int $fid): void
    {
        global $db;
        $db->delete_query('anschlusssuche_forums', 'fid = ' . $fid);
        self::$forums = null;
    }

    /* ------------------------------------------------------------------ */
    /* Fields                                                               */
    /* ------------------------------------------------------------------ */

    /**
     * @param bool $active_only
     * @return array<int, array> id => row, ordered by disporder
     */
    public static function fields(string $type, bool $active_only = true): array
    {
        global $db;

        $cache_key = $type . ($active_only ? ':a' : ':all');
        if (!isset(self::$fields[$cache_key])) {
            self::$fields[$cache_key] = [];
            $where = "type = '" . $db->escape_string($type) . "'";
            if ($active_only) {
                $where .= ' AND active = 1';
            }
            $query = $db->simple_select('anschlusssuche_fields', '*', $where, ['order_by' => 'disporder, id']);
            while ($row = $db->fetch_array($query)) {
                $row['id'] = (int)$row['id'];
                self::$fields[$cache_key][$row['id']] = $row;
            }
        }
        return self::$fields[$cache_key];
    }

    public static function field(int $id): ?array
    {
        global $db;
        $row = $db->fetch_array($db->simple_select('anschlusssuche_fields', '*', 'id = ' . $id));
        return $row ?: null;
    }

    /** Field of a type by key, regardless of active flag. */
    public static function field_by_key(string $type, string $fkey): ?array
    {
        foreach (self::fields($type, false) as $field) {
            if ($field['fkey'] === $fkey) {
                return $field;
            }
        }
        return null;
    }

    /** The age_range field of a type (max. one), active only. */
    public static function age_field(string $type): ?array
    {
        foreach (self::fields($type) as $field) {
            if ($field['fieldtype'] === 'age_range') {
                return $field;
            }
        }
        return null;
    }

    public static function save_field(array $data, int $id = 0): int
    {
        global $db;

        $row = [];
        foreach (['type', 'fkey', 'title', 'description', 'fieldtype', 'options', 'icon'] as $key) {
            if (isset($data[$key])) {
                $row[$key] = $db->escape_string((string)$data[$key]);
            }
        }
        foreach (['required', 'disporder', 'show_list', 'show_profile', 'show_header', 'filterable', 'active'] as $key) {
            if (isset($data[$key])) {
                $row[$key] = (int)$data[$key];
            }
        }
        if ($id > 0) {
            $db->update_query('anschlusssuche_fields', $row, 'id = ' . $id);
        } else {
            $id = (int)$db->insert_query('anschlusssuche_fields', $row);
        }
        self::$fields = [];
        return $id;
    }

    public static function delete_field(int $id): void
    {
        global $db;
        $db->delete_query('anschlusssuche_values', 'field_id = ' . $id);
        $db->delete_query('anschlusssuche_links', 'field_id = ' . $id);
        $db->delete_query('anschlusssuche_fields', 'id = ' . $id);
        self::$fields = [];
    }

    /** Renames a stored option value of a field (select/radio/multi) in all entries. */
    public static function rename_option_value(int $field_id, string $old, string $new, bool $multi): void
    {
        global $db;

        if ($old === '' || $old === $new) {
            return;
        }
        if (!$multi) {
            $db->update_query('anschlusssuche_values', ['value' => $db->escape_string($new)],
                'field_id = ' . $field_id . " AND value = '" . $db->escape_string($old) . "'");
            return;
        }
        $query = $db->simple_select('anschlusssuche_values', 'entry_id, value', 'field_id = ' . $field_id);
        while ($row = $db->fetch_array($query)) {
            $parts = explode("\n", (string)$row['value']);
            $changed = false;
            foreach ($parts as $i => $part) {
                if ($part === $old) {
                    $parts[$i] = $new;
                    $changed = true;
                }
            }
            if ($changed) {
                $db->update_query('anschlusssuche_values', ['value' => $db->escape_string(implode("\n", array_unique($parts)))],
                    'field_id = ' . $field_id . ' AND entry_id = ' . (int)$row['entry_id']);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Entries                                                              */
    /* ------------------------------------------------------------------ */

    public static function entry(int $id): ?array
    {
        global $db;
        $row = $db->fetch_array($db->simple_select('anschlusssuche_entries', '*', 'id = ' . $id));
        return $row ?: null;
    }

    public static function entry_by_tid(int $tid): ?array
    {
        global $db;
        $row = $db->fetch_array($db->simple_select('anschlusssuche_entries', '*', 'tid = ' . $tid));
        return $row ?: null;
    }

    /**
     * Lädt Einträge zu Thread-IDs inkl. Werte und Links (3 Queries).
     *
     * @param int[] $tids
     * @return array<int, array> tid => entry
     */
    public static function entries_by_tids(array $tids): array
    {
        global $db;

        $tids = array_values(array_filter(array_map('intval', $tids)));
        if (!$tids || !$db->table_exists('anschlusssuche_entries')) {
            return [];
        }
        $by_id = [];
        $query = $db->simple_select('anschlusssuche_entries', '*', 'tid IN (' . implode(',', $tids) . ')');
        while ($row = $db->fetch_array($query)) {
            $by_id[(int)$row['id']] = $row;
        }
        self::attach_details($by_id);

        $out = [];
        foreach ($by_id as $row) {
            $out[(int)$row['tid']] = $row;
        }
        return $out;
    }

    /**
     * @param int[] $ids
     * @return array<int, array> id => entry (inkl. values/links)
     */
    public static function entries_by_ids(array $ids): array
    {
        global $db;

        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $by_id = [];
        $query = $db->simple_select('anschlusssuche_entries', '*', 'id IN (' . implode(',', $ids) . ')');
        while ($row = $db->fetch_array($query)) {
            $by_id[(int)$row['id']] = $row;
        }
        self::attach_details($by_id);

        // keep requested order
        $out = [];
        foreach ($ids as $id) {
            if (isset($by_id[$id])) {
                $out[$id] = $by_id[$id];
            }
        }
        return $out;
    }

    /**
     * Hängt 'values' (field_id => row) und 'links' (field_id => list) an.
     *
     * @param array<int, array> $entries
     */
    public static function attach_details(array &$entries): void
    {
        global $db;

        foreach ($entries as $id => $entry) {
            $entries[$id]['values'] = [];
            $entries[$id]['links'] = [];
        }
        if (!$entries) {
            return;
        }
        $in = implode(',', array_map('intval', array_keys($entries)));

        $query = $db->simple_select('anschlusssuche_values', '*', "entry_id IN ({$in})");
        while ($row = $db->fetch_array($query)) {
            $entries[(int)$row['entry_id']]['values'][(int)$row['field_id']] = $row;
        }
        $query = $db->simple_select('anschlusssuche_links', '*', "entry_id IN ({$in})", ['order_by' => 'disporder, id']);
        while ($row = $db->fetch_array($query)) {
            $entries[(int)$row['entry_id']]['links'][(int)$row['field_id']][] = $row;
        }
    }

    /**
     * Einträge mehrerer Accounts, für Profil und Fremdplugins.
     *
     * @param int[] $uids
     * @param array $opts include_done (bool), viewer_check (bool)
     * @return array<int, array> id => entry (inkl. values/links, thread subject als 'subject')
     */
    public static function entries_for_uids(array $uids, array $opts = []): array
    {
        global $db;

        $uids = array_values(array_filter(array_map('intval', $uids)));
        if (!$uids) {
            return [];
        }
        $include_done = !empty($opts['include_done']);
        $viewer_check = !isset($opts['viewer_check']) || $opts['viewer_check'];

        $where = ['e.uid IN (' . implode(',', $uids) . ')'];
        $thread_cond = 't.visible = 1';
        if ($viewer_check) {
            $unviewable = get_unviewable_forums(true);
            if ($unviewable) {
                $thread_cond .= " AND t.fid NOT IN ({$unviewable})";
            }
            $inactive = get_inactive_forums();
            if ($inactive) {
                $thread_cond .= " AND t.fid NOT IN ({$inactive})";
            }
        }
        $archive = self::archive_fids();
        if (!$include_done && $archive) {
            $thread_cond .= ' AND t.fid NOT IN (' . implode(',', $archive) . ')';
        }
        $where[] = "(e.source = 'loose' OR ({$thread_cond}))";

        $query = $db->query("
            SELECT e.*, t.subject, t.fid AS thread_fid, t.dateline AS thread_dateline
            FROM " . TABLE_PREFIX . "anschlusssuche_entries e
            LEFT JOIN " . TABLE_PREFIX . "threads t ON (t.tid = e.tid)
            WHERE " . implode(' AND ', $where) . "
            ORDER BY e.created_at DESC
        ");
        $out = [];
        while ($row = $db->fetch_array($query)) {
            $out[(int)$row['id']] = $row;
        }
        self::attach_details($out);
        return $out;
    }

    /** @return array<int, array> id => row of the loose entries of one account */
    public static function loose_entries_of(int $uid): array
    {
        global $db;
        $out = [];
        $query = $db->simple_select('anschlusssuche_entries', '*', "source = 'loose' AND uid = {$uid}", ['order_by' => 'created_at', 'order_dir' => 'DESC']);
        while ($row = $db->fetch_array($query)) {
            $out[(int)$row['id']] = $row;
        }
        self::attach_details($out);
        return $out;
    }

    public static function count_loose_of(int $uid): int
    {
        global $db;
        return (int)$db->fetch_field($db->simple_select('anschlusssuche_entries', 'COUNT(*) AS c', "source = 'loose' AND uid = {$uid}"), 'c');
    }

    public static function delete_entry(int $id): void
    {
        global $db;
        $db->delete_query('anschlusssuche_values', 'entry_id = ' . $id);
        $db->delete_query('anschlusssuche_links', 'entry_id = ' . $id);
        $db->delete_query('anschlusssuche_header', 'entry_id = ' . $id);
        $db->delete_query('anschlusssuche_entries', 'id = ' . $id);
    }

    public static function delete_by_tid(int $tid): void
    {
        global $db;
        if ($tid <= 0 || !$db->table_exists('anschlusssuche_entries')) {
            return;
        }
        $entry = self::entry_by_tid($tid);
        if ($entry) {
            self::delete_entry((int)$entry['id']);
        }
    }

    /** @param int[] $tids */
    public static function update_fid(array $tids, int $fid): void
    {
        global $db;
        $tids = array_values(array_filter(array_map('intval', $tids)));
        if (!$tids || $fid <= 0 || !$db->table_exists('anschlusssuche_entries')) {
            return;
        }
        $db->update_query('anschlusssuche_entries', ['fid' => $fid], 'tid IN (' . implode(',', $tids) . ')');
    }

    /**
     * Gelöschte Accounts: lose Gesuche + Gutscheine weg, Verknüpfungen in Anschlusslisten
     * lösen (Name bleibt stehen).
     *
     * @param int[] $uids
     */
    public static function delete_for_users(array $uids): void
    {
        global $db;

        $uids = array_values(array_filter(array_map('intval', $uids)));
        if (!$uids || !$db->table_exists('anschlusssuche_entries')) {
            return;
        }
        $in = implode(',', $uids);
        $query = $db->simple_select('anschlusssuche_entries', 'id', "source = 'loose' AND uid IN ({$in})");
        while ($row = $db->fetch_array($query)) {
            self::delete_entry((int)$row['id']);
        }
        $db->delete_query('anschlusssuche_credits', "uid IN ({$in})");
        $db->write_query('UPDATE ' . TABLE_PREFIX . "anschlusssuche_links SET uid = NULL WHERE uid IN ({$in})");
    }

    /** Resets the request caches (used by tests and the ACP after writes). */
    public static function reset_cache(): void
    {
        self::$forums = null;
        self::$fields = [];
    }
}
