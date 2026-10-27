<?php
/**
 * Einziger Zugang zum Relatives-Plugin: nur über dessen öffentliche API
 * (relatives_search_persons() + relatives_count_persons()), niemals über relatives_*-Tabellen.
 *
 * Personen werden auf "lose Gesuche" abgebildet (Pseudo-Einträge mit source = 'relatives').
 * Rückgabewerte sind roh; escaped wird im Renderer.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_RelativesAdapter
{
    public static function available(): bool
    {
        global $mybb, $cache;

        if (empty($mybb->settings['anschlusssuche_relatives_enabled'])) {
            return false;
        }
        if (!function_exists('relatives_search_persons') || !function_exists('relatives_count_persons')) {
            return false;
        }
        $plugins_cache = is_object($cache) ? $cache->read('plugins') : [];
        return !empty($plugins_cache['active']['relatives']);
    }

    /**
     * @param array $filters age_min, age_max, query, owner_uids, limit, offset
     * @return array list of pseudo entries
     */
    public static function search(array $filters): array
    {
        if (!self::available()) {
            return [];
        }
        $persons = relatives_search_persons(self::api_filters($filters, true));
        $out = [];
        foreach ((array)$persons as $person) {
            $mapped = self::map_person((array)$person);
            if ($mapped) {
                $out[] = $mapped;
            }
        }
        return $out;
    }

    public static function count(array $filters): int
    {
        if (!self::available()) {
            return 0;
        }
        return (int)relatives_count_persons(self::api_filters($filters, false));
    }

    /** @return array|null pseudo entry of one person */
    public static function get(int $person_id): ?array
    {
        $rows = self::search(['ids' => [$person_id], 'limit' => 1]);
        return $rows ? $rows[0] : null;
    }

    private static function api_filters(array $filters, bool $paged): array
    {
        $api = ['is_wanted' => true];
        foreach (['age_min', 'age_max'] as $k) {
            if (isset($filters[$k]) && $filters[$k] !== '' && $filters[$k] !== null) {
                $api[$k] = (int)$filters[$k];
            }
        }
        if (isset($filters['query']) && $filters['query'] !== '') {
            $api['query'] = (string)$filters['query'];
        }
        if (!empty($filters['owner_uids'])) {
            $api['owner_uids'] = array_map('intval', (array)$filters['owner_uids']);
        }
        if (!empty($filters['ids'])) {
            $api['ids'] = array_map('intval', (array)$filters['ids']);
        }
        if ($paged) {
            if (isset($filters['limit'])) {
                $api['limit'] = max(1, (int)$filters['limit']);
            }
            if (isset($filters['offset'])) {
                $api['offset'] = max(0, (int)$filters['offset']);
            }
            $api['order'] = isset($filters['order']) ? (string)$filters['order'] : 'newest';
        }
        return $api;
    }

    /** @return array<string, string> code => label */
    public static function gender_map(): array
    {
        global $mybb;
        $out = [];
        $raw = isset($mybb->settings['anschlusssuche_relatives_gender_map']) ? (string)$mybb->settings['anschlusssuche_relatives_gender_map'] : '';
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $out[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $out;
    }

    /**
     * Relatives-Person → Pseudo-Eintrag (fester Kern, ACP-Felder bleiben leer).
     */
    private static function map_person(array $p): ?array
    {
        if (empty($p['id']) || !isset($p['name'])) {
            return null;
        }
        $owners = isset($p['owners']) && is_array($p['owners']) ? array_values($p['owners']) : [];
        $first = $owners ? (array)$owners[0] : [];

        $description = '';
        if (!empty($first['relationship_description'])) {
            $description = (string)$first['relationship_description'];
        } elseif (!empty($p['description'])) {
            $description = (string)$p['description'];
        }

        $gender = '';
        $map = self::gender_map();
        if (isset($p['gender']) && isset($map[(string)(int)$p['gender']])) {
            $gender = $map[(string)(int)$p['gender']];
        }

        $owner_uids = [];
        foreach ($owners as $o) {
            if (!empty($o['owner_uid'])) {
                $owner_uids[] = (int)$o['owner_uid'];
            }
        }

        $age = isset($p['age']) && $p['age'] !== null && $p['age'] !== '' ? (int)$p['age'] : null;

        return [
            'id'            => 0,
            'source'        => 'relatives',
            'person_id'     => (int)$p['id'],
            'type'          => 'loose',
            'tid'           => 0,
            'fid'           => 0,
            'uid'           => $owner_uids ? $owner_uids[0] : 0,
            'owner_uids'    => $owner_uids,
            'title'         => (string)$p['name'],
            'status'        => 'free',
            'age_min'       => $age,
            'age_max'       => $age,
            'age_text'      => '',
            'gender'        => $gender,
            'relation'      => isset($first['computed_label']) ? (string)$first['computed_label'] : '',
            'description'   => $description,
            'link'          => self::safe_url(isset($p['wanted_thread_link']) ? (string)$p['wanted_thread_link'] : ''),
            'avatar'        => self::safe_url(isset($p['avatar']) ? (string)$p['avatar'] : ''),
            'values'        => [],
            'links'         => [],
            'created_at'    => 0,
        ];
    }

    /** Only http(s) URLs or forum-relative paths. */
    public static function safe_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
        }
        if (preg_match('#^[a-z0-9_\-./?=&%]+$#i', $url) && strpos($url, '//') === false && stripos($url, 'javascript:') === false) {
            return $url;
        }
        return '';
    }
}
