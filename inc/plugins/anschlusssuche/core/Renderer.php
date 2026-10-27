<?php
/**
 * Template-Ausgabe: Threadlisten-Zeile, Status, Feldzeile, Karten, Profil, Filterleiste.
 * Alle Werte werden hier escaped (htmlspecialchars_uni), nie beim Speichern.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_Renderer
{
    /** @var array<int, array> tid => entry (forumdisplay preload) */
    private static $thread_entries = [];

    /**
     * Wertet ein Template mit lokalen Variablen aus. Häufige globale Objekte sind im
     * Template ebenfalls verfügbar ($mybb, $lang, $theme, $headerinclude, $header, $footer,
     * $usercpnav).
     */
    public static function render(string $__tpl, array $__vars = []): string
    {
        global $mybb, $lang, $theme, $templates, $headerinclude, $header, $footer, $usercpnav;

        extract($__vars, EXTR_SKIP);
        $__out = '';
        eval('$__out = "' . $templates->get($__tpl) . '";');
        return $__out;
    }

    /** @return string[] Templates to preload per script */
    public static function templates_for_script(string $script): array
    {
        $common = ['anschlusssuche_status', 'anschlusssuche_field_display', 'anschlusssuche_links_count'];
        $inputs = [
            'anschlusssuche_form_fields', 'anschlusssuche_form_row', 'anschlusssuche_form_status', 'anschlusssuche_form_slots',
            'anschlusssuche_input_text', 'anschlusssuche_input_textarea', 'anschlusssuche_input_number', 'anschlusssuche_input_select',
            'anschlusssuche_input_select_option', 'anschlusssuche_input_radio_option', 'anschlusssuche_input_checkbox_option',
            'anschlusssuche_input_age_range', 'anschlusssuche_input_links', 'anschlusssuche_input_links_row',
        ];
        $cards = ['anschlusssuche_card', 'anschlusssuche_card_link'];
        $filters = ['anschlusssuche_filter_select', 'anschlusssuche_filter_text', 'anschlusssuche_filter_range', 'anschlusssuche_filter_row'];
        $header = ['anschlusssuche_header', 'anschlusssuche_header_item'];

        switch ($script) {
            case 'forumdisplay.php':
                return array_merge($common, $cards, $filters, $header, [
                    'anschlusssuche_forumdisplay_thread', 'anschlusssuche_forumdisplay_filterbar',
                    'anschlusssuche_forumdisplay_loose_block', 'anschlusssuche_loose_empty',
                ]);
            case 'newthread.php':
            case 'editpost.php':
                return array_merge($common, $inputs, $header);
            case 'member.php':
                return array_merge($common, $cards, $header, ['anschlusssuche_profile', 'anschlusssuche_profile_section', 'anschlusssuche_profile_empty']);
            case 'usercp.php':
                return array_merge($common, $inputs, $header, [
                    'anschlusssuche_usercp_loose_list', 'anschlusssuche_usercp_loose_row', 'anschlusssuche_usercp_loose_empty',
                    'anschlusssuche_usercp_loose_edit', 'anschlusssuche_usercp_loose_new',
                ]);
            case 'anschluss.php':
                return array_merge($common, $cards, $filters, $header, [
                    'anschlusssuche_search', 'anschlusssuche_search_tab', 'anschlusssuche_search_noresults',
                ]);
            default:
                return $header;
        }
    }

    /* ------------------------------------------------------------------ */
    /* Status + fields                                                      */
    /* ------------------------------------------------------------------ */

    public static function status_badge(array $entry): string
    {
        global $lang;

        $status = Anschlusssuche_EntryService::effective_status($entry);
        $status_class = htmlspecialchars_uni($status);
        $key = 'anschlusssuche_status_' . $status;
        $status_label = htmlspecialchars_uni(isset($lang->$key) ? $lang->$key : $status);

        if ($entry['type'] === 'group' && (int)$entry['slots_total'] > 0) {
            $status_label = (int)$entry['slots_taken'] . '/' . (int)$entry['slots_total'] . (!empty($entry['slots_open_end']) ? '+' : '');
            if ($status === 'done' || $status === 'reserved') {
                $status_label .= ' · ' . htmlspecialchars_uni($lang->{'anschlusssuche_status_' . $status});
            }
        }
        return self::render('anschlusssuche_status', ['status_class' => $status_class, 'status_label' => $status_label]);
    }

    /**
     * Feldzeile für Liste/Profil/Header.
     *
     * @param string $flag show_list | show_profile | show_header
     * @param bool   $with_links Anschlusslisten-Felder mit ausgeben
     */
    public static function fieldline(array $entry, string $flag, bool $with_links = false): string
    {
        if (isset($entry['source']) && $entry['source'] === 'relatives') {
            return self::relatives_fieldline($entry);
        }
        $html = '';
        foreach (Anschlusssuche_Repository::fields((string)$entry['type']) as $field) {
            if (empty($field[$flag]) || ($field['fieldtype'] === 'links' && !$with_links)) {
                continue;
            }
            $value = Anschlusssuche_FieldTypes::render_display($field, $entry);
            if ($value === '') {
                continue;
            }
            $html .= self::render('anschlusssuche_field_display', [
                'field_key'   => htmlspecialchars_uni($field['fkey']),
                'field_title' => htmlspecialchars_uni($field['title']),
                'field_icon'  => htmlspecialchars_uni($field['icon']),
                'field_value' => $value,
            ]);
        }
        return $html;
    }

    private static function relatives_fieldline(array $entry): string
    {
        global $lang;

        $html = '';
        $parts = [
            'age'      => ['fas fa-birthday-cake', $lang->anschlusssuche_core_age, Anschlusssuche_FieldTypes::age_label($entry)],
            'gender'   => ['fas fa-venus-mars', $lang->anschlusssuche_core_gender, (string)$entry['gender']],
            'relation' => ['fas fa-street-view', $lang->anschlusssuche_core_relation, (string)$entry['relation']],
        ];
        foreach ($parts as $key => $part) {
            if ($part[2] === '') {
                continue;
            }
            $html .= self::render('anschlusssuche_field_display', [
                'field_key'   => $key,
                'field_title' => htmlspecialchars_uni($part[1]),
                'field_icon'  => $part[0],
                'field_value' => htmlspecialchars_uni($part[2]),
            ]);
        }
        return $html;
    }

    /** Number of linked characters over all links fields. */
    public static function links_count(array $entry): int
    {
        $n = 0;
        if (!empty($entry['links'])) {
            foreach ($entry['links'] as $rows) {
                $n += count($rows);
            }
        }
        return $n;
    }

    public static function links_count_html(array $entry): string
    {
        global $lang;
        $n = self::links_count($entry);
        if ($n <= 0) {
            return '';
        }
        return self::render('anschlusssuche_links_count', [
            'links_label' => htmlspecialchars_uni($n === 1 ? $lang->anschlusssuche_links_count_one : $lang->sprintf($lang->anschlusssuche_links_count, $n)),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Thread list                                                          */
    /* ------------------------------------------------------------------ */

    /** @param int[] $tids */
    public static function preload_threads(array $tids): void
    {
        self::$thread_entries = Anschlusssuche_Repository::entries_by_tids($tids);
    }

    /** @return array{status: string, fieldline: string, links: string, rowclass: string} */
    public static function thread_row_vars(int $tid): array
    {
        $out = ['status' => '', 'fieldline' => '', 'links' => '', 'rowclass' => ''];
        if (!isset(self::$thread_entries[$tid])) {
            return $out;
        }
        $entry = self::$thread_entries[$tid];
        $out['status'] = self::status_badge($entry);
        $out['fieldline'] = self::fieldline($entry, 'show_list');
        $out['links'] = self::links_count_html($entry);
        $out['rowclass'] = 'anschlusssuche-row anschlusssuche-row--' . htmlspecialchars_uni(Anschlusssuche_EntryService::effective_status($entry));
        return $out;
    }

    /* ------------------------------------------------------------------ */
    /* Cards (loose ads, search results)                                    */
    /* ------------------------------------------------------------------ */

    public static function card(array $entry): string
    {
        global $lang, $mybb;

        $title = htmlspecialchars_uni(isset($entry['subject']) && $entry['subject'] !== null && $entry['source'] === 'thread' ? (string)$entry['subject'] : (string)$entry['title']);
        $url = '';
        $source = (string)$entry['source'];
        if ($source === 'thread' && !empty($entry['tid'])) {
            $url = get_thread_link((int)$entry['tid']);
        } elseif ($source === 'relatives' && $entry['link'] !== '') {
            $url = htmlspecialchars_uni($entry['link']);
        }
        $card_title = $url !== '' ? '<a href="' . $url . '">' . $title . '</a>' : $title;

        $type_key = 'anschlusssuche_type_' . $entry['type'];
        $card_type = htmlspecialchars_uni(isset($lang->$type_key) ? $lang->$type_key : $entry['type']);
        $card_status = self::status_badge($entry);
        $card_fields = self::fieldline($entry, 'show_list', true);

        $card_description = '';
        if ($source === 'relatives' && $entry['description'] !== '') {
            $card_description = nl2br(htmlspecialchars_uni(my_substr((string)$entry['description'], 0, 500)));
        }

        $card_owner = '';
        if (!empty($entry['uid'])) {
            $user = get_user((int)$entry['uid']);
            if ($user) {
                $card_owner = build_profile_link(htmlspecialchars_uni($user['username']), (int)$user['uid']);
            }
        }
        $card_source = $source === 'relatives' ? htmlspecialchars_uni($lang->anschlusssuche_source_relatives) : '';
        $card_class = 'anschlusssuche-card--' . htmlspecialchars_uni($source) . ' anschlusssuche-card--' . htmlspecialchars_uni($entry['type']);

        return self::render('anschlusssuche_card', [
            'card_title'       => $card_title,
            'card_type'        => $card_type,
            'card_status'      => $card_status,
            'card_fields'      => $card_fields,
            'card_description' => $card_description,
            'card_owner'       => $card_owner,
            'card_source'      => $card_source,
            'card_class'       => $card_class,
        ]);
    }

    /**
     * Kartenblock "lose Gesuche" über der Threadliste eines Forums vom Typ loose.
     */
    public static function loose_block(array $forum): string
    {
        global $mybb, $lang;

        $perpage = max(1, (int)$mybb->settings['anschlusssuche_loose_per_page']);
        $page = max(1, $mybb->get_input('lpage', MyBB::INPUT_INT));
        $result = Anschlusssuche_SearchService::search(['type' => 'loose'], $page, $perpage);

        $cards = '';
        foreach ($result['items'] as $entry) {
            $cards .= self::card($entry);
        }
        if ($cards === '') {
            $cards = self::render('anschlusssuche_loose_empty');
        }
        $base = 'forumdisplay.php?fid=' . (int)$forum['fid'];
        $multipage = multipage($result['total'], $perpage, $page, $base . '&amp;lpage={page}');
        $loose_total = (int)$result['total'];
        $new_link = '';
        if (Anschlusssuche_Renderer::can_create_loose()) {
            $new_link = self::render('anschlusssuche_usercp_loose_new');
        }
        return self::render('anschlusssuche_forumdisplay_loose_block', [
            'cards'       => $cards,
            'multipage'   => $multipage,
            'loose_total' => $loose_total,
            'new_link'    => $new_link,
        ]);
    }

    public static function can_create_loose(): bool
    {
        global $mybb;
        if (empty($mybb->user['uid'])) {
            return false;
        }
        $groups = (string)$mybb->settings['anschlusssuche_loose_groups'];
        if ($groups === '-1') {
            return true; // groupselect "all groups"
        }
        return $groups !== '' && !empty(is_member($groups));
    }

    /* ------------------------------------------------------------------ */
    /* Filters                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Filterzeilen für einen Typ (Kernfilter Status/Alter + filterbare ACP-Felder).
     *
     * @param array $current aktuelle Werte: status, age_min, age_max, f => [fkey => raw]
     */
    public static function filter_rows(string $type, array $current, bool $with_status = true): string
    {
        global $lang;

        $rows = '';
        if ($with_status) {
            $options = self::filter_option('', $lang->anschlusssuche_filter_any, (string)($current['status'] ?? ''));
            foreach (['free', 'reserved', 'taken'] as $st) {
                $options .= self::filter_option($st, $lang->{'anschlusssuche_status_' . $st}, (string)($current['status'] ?? ''));
            }
            $rows .= self::filter_row($lang->anschlusssuche_core_status,
                self::render('anschlusssuche_filter_select', ['filter_name' => 'status', 'options' => $options]));
        }
        $rows .= self::filter_row($lang->anschlusssuche_core_age, self::render('anschlusssuche_filter_range', [
            'name_min'  => 'age_min',
            'name_max'  => 'age_max',
            'value_min' => htmlspecialchars_uni((string)($current['age_min'] ?? '')),
            'value_max' => htmlspecialchars_uni((string)($current['age_max'] ?? '')),
        ]));

        if ($type === '' || $type === 'all') {
            return $rows;
        }
        $f = isset($current['f']) && is_array($current['f']) ? $current['f'] : [];
        foreach (Anschlusssuche_Repository::fields($type) as $field) {
            if (empty($field['filterable']) || $field['fieldtype'] === 'age_range') {
                continue;
            }
            $raw = isset($f[$field['fkey']]) ? $f[$field['fkey']] : (in_array($field['fieldtype'], ['number'], true) ? ['min' => '', 'max' => ''] : '');
            $name = 'f[' . htmlspecialchars_uni($field['fkey']) . ']';
            if (in_array($field['fieldtype'], Anschlusssuche_FieldTypes::OPTION_TYPES, true)) {
                $options = self::filter_option('', $lang->anschlusssuche_filter_any, (string)$raw);
                foreach (Anschlusssuche_FieldTypes::options($field) as $opt) {
                    $options .= self::filter_option($opt, $opt, (string)$raw);
                }
                $input = self::render('anschlusssuche_filter_select', ['filter_name' => $name, 'options' => $options]);
            } elseif ($field['fieldtype'] === 'number') {
                $input = self::render('anschlusssuche_filter_range', [
                    'name_min'  => $name . '[min]',
                    'name_max'  => $name . '[max]',
                    'value_min' => htmlspecialchars_uni((string)$raw['min']),
                    'value_max' => htmlspecialchars_uni((string)$raw['max']),
                ]);
            } else {
                $input = self::render('anschlusssuche_filter_text', ['filter_name' => $name, 'filter_value' => htmlspecialchars_uni((string)$raw)]);
            }
            $rows .= self::filter_row($field['title'], $input);
        }
        return $rows;
    }

    private static function filter_option(string $value, string $label, string $current): string
    {
        return '<option value="' . htmlspecialchars_uni($value) . '"' . ($value === $current ? ' selected="selected"' : '') . '>'
            . htmlspecialchars_uni($label) . '</option>';
    }

    private static function filter_row(string $title, string $input): string
    {
        return self::render('anschlusssuche_filter_row', ['filter_title' => htmlspecialchars_uni($title), 'filter_input' => $input]);
    }

    public static function filterbar(array $forum): string
    {
        $type = (string)$forum['type'];
        $filter_rows = self::filter_rows($type, [], $type !== 'loose');
        return self::render('anschlusssuche_forumdisplay_filterbar', [
            'filter_rows' => $filter_rows,
            'filter_type' => htmlspecialchars_uni($type),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Profile                                                              */
    /* ------------------------------------------------------------------ */

    public static function profile(int $uid): string
    {
        global $mybb, $lang;

        $uids = Anschlusssuche_AccountResolver::all_uids($uid);
        $include_done = !empty($mybb->settings['anschlusssuche_profile_show_done']);
        $entries = Anschlusssuche_Repository::entries_for_uids($uids, ['include_done' => $include_done]);

        $sections = ['single' => [], 'group' => [], 'loose' => [], 'done' => []];
        foreach ($entries as $entry) {
            $key = Anschlusssuche_EntryService::effective_status($entry) === 'done' ? 'done' : (string)$entry['type'];
            if (isset($sections[$key])) {
                $sections[$key][] = $entry;
            }
        }
        if (empty($mybb->settings['anschlusssuche_profile_hide_relatives']) && Anschlusssuche_RelativesAdapter::available()) {
            foreach (Anschlusssuche_RelativesAdapter::search(['owner_uids' => $uids, 'limit' => 100]) as $person) {
                $sections['loose'][] = $person;
            }
        }

        $html = '';
        $total = 0;
        foreach ($sections as $key => $list) {
            if (!$list) {
                continue;
            }
            $cards = '';
            foreach ($list as $entry) {
                $cards .= self::card($entry);
                $total++;
            }
            $section_title = htmlspecialchars_uni($lang->{'anschlusssuche_profile_section_' . $key});
            $section_key = $key;
            $html .= self::render('anschlusssuche_profile_section', [
                'section_title' => $section_title,
                'section_key'   => $section_key,
                'cards'         => $cards,
                'section_count' => count($list),
            ]);
        }
        if ($html === '') {
            $html = self::render('anschlusssuche_profile_empty');
        }
        return self::render('anschlusssuche_profile', ['sections' => $html, 'profile_total' => $total]);
    }
}
