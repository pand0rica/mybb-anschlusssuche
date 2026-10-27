<?php
/**
 * Suche über eigene Einträge (Threads + lose) und Relatives-Personen, mit gemeinsamer
 * Pagination: zuerst eigene Treffer, danach Relatives.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_SearchService
{
    /**
     * Normalisierte Suchparameter aus dem Request.
     */
    public static function params_from_input(): array
    {
        global $mybb;

        $type = $mybb->get_input('type');
        if (!in_array($type, Anschlusssuche_Repository::TYPES, true)) {
            $type = 'all';
        }
        $status = $mybb->get_input('status');
        if (!in_array($status, Anschlusssuche_EntryService::STATUSES, true)) {
            $status = '';
        }
        $age_min = trim($mybb->get_input('age_min'));
        $age_max = trim($mybb->get_input('age_max'));

        $params = [
            'type'    => $type,
            'status'  => $status,
            'age_min' => ctype_digit($age_min) ? $age_min : '',
            'age_max' => ctype_digit($age_max) ? $age_max : '',
            'q'       => my_substr(trim($mybb->get_input('q')), 0, 100),
            'archive' => $mybb->get_input('archive', MyBB::INPUT_INT) === 1 ? 1 : 0,
            'f'       => [],
        ];

        if ($type !== 'all') {
            $f_in = $mybb->get_input('f', MyBB::INPUT_ARRAY);
            foreach (Anschlusssuche_Repository::fields($type) as $field) {
                if (empty($field['filterable']) || $field['fieldtype'] === 'age_range') {
                    continue;
                }
                $raw = Anschlusssuche_FieldTypes::filter_from_input($field, $f_in);
                if (Anschlusssuche_FieldTypes::filter_is_set($field, $raw)) {
                    $params['f'][$field['fkey']] = $raw;
                }
            }
        }
        return $params;
    }

    /**
     * @return array ['items' => list<entry>, 'total' => int]
     */
    public static function search(array $params, int $page, int $perpage): array
    {
        $page = max(1, $page);
        $perpage = max(1, $perpage);
        $offset = ($page - 1) * $perpage;

        [$own_total, $own_sql] = self::own_query($params);

        $with_relatives = self::relatives_applicable($params);
        $rel_filters = [];
        $rel_total = 0;
        if ($with_relatives) {
            $rel_filters = [
                'age_min' => $params['age_min'] ?? '',
                'age_max' => $params['age_max'] ?? '',
                'query'   => $params['q'] ?? '',
            ];
            $rel_total = Anschlusssuche_RelativesAdapter::count($rel_filters);
        }

        $items = [];
        if ($offset < $own_total) {
            $items = self::own_page($own_sql, $offset, $perpage);
        }
        $remaining = $perpage - count($items);
        if ($with_relatives && $remaining > 0 && $rel_total > 0) {
            $rel_offset = max(0, $offset - $own_total);
            foreach (Anschlusssuche_RelativesAdapter::search($rel_filters + ['limit' => $remaining, 'offset' => $rel_offset]) as $person) {
                $items[] = $person;
            }
        }
        return ['items' => $items, 'total' => $own_total + $rel_total];
    }

    private static function relatives_applicable(array $params): bool
    {
        $type = $params['type'] ?? 'all';
        if ($type !== 'all' && $type !== 'loose') {
            return false;
        }
        if (!empty($params['f'])) {
            return false; // ACP field filters only apply to own entries
        }
        if (!empty($params['status']) && $params['status'] !== 'free') {
            return false;
        }
        return Anschlusssuche_RelativesAdapter::available();
    }

    /**
     * @return array [int total, string FROM/WHERE part]
     */
    private static function own_query(array $params): array
    {
        global $db;

        $p = TABLE_PREFIX;
        $where = [];

        $type = $params['type'] ?? 'all';
        if ($type !== 'all') {
            $where[] = "e.type = '" . $db->escape_string($type) . "'";
        }

        // Thread visibility
        $thread_cond = 't.visible = 1';
        $unviewable = get_unviewable_forums(true);
        if ($unviewable) {
            $thread_cond .= " AND t.fid NOT IN ({$unviewable})";
        }
        $inactive = get_inactive_forums();
        if ($inactive) {
            $thread_cond .= " AND t.fid NOT IN ({$inactive})";
        }
        $archive = Anschlusssuche_Repository::archive_fids();
        if ($archive && empty($params['archive'])) {
            $thread_cond .= ' AND t.fid NOT IN (' . implode(',', $archive) . ')';
        }
        $where[] = "(e.source = 'loose' OR (t.tid IS NOT NULL AND {$thread_cond}))";

        if (!empty($params['status'])) {
            $where[] = "e.status = '" . $db->escape_string($params['status']) . "'";
            if ($archive) {
                $where[] = "(e.source = 'loose' OR t.fid NOT IN (" . implode(',', $archive) . '))';
            }
        }
        if (($params['age_min'] ?? '') !== '' || ($params['age_max'] ?? '') !== '') {
            $where[] = Anschlusssuche_FieldTypes::age_overlap_sql($params['age_min'] ?? '', $params['age_max'] ?? '');
        }
        if (($params['q'] ?? '') !== '') {
            $like = $db->escape_string_like($params['q']);
            $where[] = "(e.title LIKE '%{$like}%' OR t.subject LIKE '%{$like}%' OR e.age_text LIKE '%{$like}%'"
                . " OR EXISTS (SELECT 1 FROM {$p}anschlusssuche_values qv WHERE qv.entry_id = e.id AND qv.value LIKE '%{$like}%')"
                . " OR EXISTS (SELECT 1 FROM {$p}anschlusssuche_links ql WHERE ql.entry_id = e.id AND (ql.name LIKE '%{$like}%' OR ql.role LIKE '%{$like}%')))";
        }
        if ($type !== 'all' && !empty($params['f'])) {
            foreach (Anschlusssuche_Repository::fields($type) as $field) {
                if (isset($params['f'][$field['fkey']])) {
                    $sql = Anschlusssuche_FieldTypes::filter_sql($field, $params['f'][$field['fkey']]);
                    if ($sql !== '') {
                        $where[] = $sql;
                    }
                }
            }
        }

        $from = "FROM {$p}anschlusssuche_entries e LEFT JOIN {$p}threads t ON (t.tid = e.tid) WHERE " . implode(' AND ', $where);
        $total = (int)$db->fetch_field($db->query("SELECT COUNT(*) AS c {$from}"), 'c');
        return [$total, $from];
    }

    private static function own_page(string $from, int $offset, int $limit): array
    {
        global $db;

        $query = $db->query("
            SELECT e.*, t.subject, t.fid AS thread_fid
            {$from}
            ORDER BY COALESCE(t.lastpost, e.updated_at) DESC, e.id DESC
            LIMIT " . (int)$offset . ', ' . (int)$limit);
        $rows = [];
        while ($row = $db->fetch_array($query)) {
            $rows[(int)$row['id']] = $row;
        }
        Anschlusssuche_Repository::attach_details($rows);
        return array_values($rows);
    }

    /** Query string (without page) for multipage links, HTML-escaped. */
    public static function query_string(array $params): string
    {
        $q = ['type' => $params['type']];
        foreach (['status', 'age_min', 'age_max', 'q'] as $k) {
            if (($params[$k] ?? '') !== '') {
                $q[$k] = $params[$k];
            }
        }
        if (!empty($params['archive'])) {
            $q['archive'] = 1;
        }
        if (!empty($params['f'])) {
            $q['f'] = $params['f'];
        }
        return htmlspecialchars_uni(http_build_query($q));
    }
}
