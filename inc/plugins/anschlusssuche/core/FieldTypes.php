<?php
/**
 * Feldtyp-Registry. Jeder Typ kennt: Eingabe rendern, Eingabe lesen, validieren,
 * in Speicherform bringen, anzeigen und als Suchfilter übersetzen.
 *
 * Rohwerte je Typ:
 *  - text, textarea, select, radio, number: string
 *  - multi: string[]
 *  - age_range: ['min' => string, 'max' => string, 'text' => string]
 *  - links: list<['name' => string, 'role' => string, 'uid' => int]>
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_FieldTypes
{
    public const TYPES = ['text', 'textarea', 'select', 'radio', 'multi', 'number', 'age_range', 'links'];

    /** Types with an option list. */
    public const OPTION_TYPES = ['select', 'radio', 'multi'];

    public const AGE_LIMIT = 999;

    /** @return string[] */
    public static function options(array $field): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', (string)$field['options']) as $line) {
            $line = trim($line);
            if ($line !== '' && !in_array($line, $out, true)) {
                $out[] = $line;
            }
        }
        return $out;
    }

    /* ------------------------------------------------------------------ */
    /* Reading                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Liest den Rohwert eines Feldes aus dem Formular-Array anschlusssuche[fkey].
     *
     * @param array $input bereits per get_input(..., INPUT_ARRAY) gelesen
     * @return mixed
     */
    public static function from_input(array $field, array $input)
    {
        $key = $field['fkey'];
        $raw = isset($input[$key]) ? $input[$key] : null;

        switch ($field['fieldtype']) {
            case 'multi':
                $out = [];
                foreach ((array)$raw as $v) {
                    if (is_string($v) && trim($v) !== '') {
                        $out[] = trim($v);
                    }
                }
                return $out;

            case 'age_range':
                $raw = is_array($raw) ? $raw : [];
                return [
                    'min'  => isset($raw['min']) && is_string($raw['min']) ? trim($raw['min']) : '',
                    'max'  => isset($raw['max']) && is_string($raw['max']) ? trim($raw['max']) : '',
                    'text' => isset($raw['text']) && is_string($raw['text']) ? trim($raw['text']) : '',
                ];

            case 'links':
                $raw = is_array($raw) ? $raw : [];
                $names = isset($raw['name']) && is_array($raw['name']) ? array_values($raw['name']) : [];
                $roles = isset($raw['role']) && is_array($raw['role']) ? array_values($raw['role']) : [];
                $uids = isset($raw['uid']) && is_array($raw['uid']) ? array_values($raw['uid']) : [];
                $rows = [];
                foreach ($names as $i => $name) {
                    $rows[] = [
                        'name' => is_string($name) ? trim($name) : '',
                        'role' => isset($roles[$i]) && is_string($roles[$i]) ? trim($roles[$i]) : '',
                        'uid'  => isset($uids[$i]) ? (int)$uids[$i] : 0,
                    ];
                }
                return self::clean_links($rows);

            default:
                return is_string($raw) ? trim($raw) : '';
        }
    }

    /**
     * Rohwert eines Feldes aus einem gespeicherten Eintrag.
     *
     * @return mixed
     */
    public static function from_entry(array $field, ?array $entry)
    {
        $id = (int)$field['id'];
        switch ($field['fieldtype']) {
            case 'age_range':
                if (!$entry) {
                    return ['min' => '', 'max' => '', 'text' => ''];
                }
                return [
                    'min'  => $entry['age_min'] === null ? '' : (string)(int)$entry['age_min'],
                    'max'  => $entry['age_max'] === null ? '' : (string)(int)$entry['age_max'],
                    'text' => (string)$entry['age_text'],
                ];

            case 'links':
                $rows = [];
                if ($entry && isset($entry['links'][$id])) {
                    foreach ($entry['links'][$id] as $row) {
                        $rows[] = ['name' => (string)$row['name'], 'role' => (string)$row['role'], 'uid' => (int)$row['uid']];
                    }
                }
                return $rows;

            case 'multi':
                if ($entry && isset($entry['values'][$id]) && (string)$entry['values'][$id]['value'] !== '') {
                    return explode("\n", (string)$entry['values'][$id]['value']);
                }
                return [];

            default:
                return $entry && isset($entry['values'][$id]) ? (string)$entry['values'][$id]['value'] : '';
        }
    }

    /** @return mixed empty raw value of a field */
    public static function empty_value(array $field)
    {
        return self::from_entry($field, null);
    }

    public static function is_empty(array $field, $raw): bool
    {
        switch ($field['fieldtype']) {
            case 'multi':
            case 'links':
                return empty($raw);
            case 'age_range':
                return $raw['min'] === '' && $raw['max'] === '';
            default:
                return (string)$raw === '';
        }
    }

    /* ------------------------------------------------------------------ */
    /* Validation + normalisation                                           */
    /* ------------------------------------------------------------------ */

    /**
     * @return string Fehlermeldung (Klartext, unescaped) oder '' wenn gültig
     */
    public static function validate(array $field, $raw): string
    {
        global $lang;

        $title = (string)$field['title'];
        if (self::is_empty($field, $raw)) {
            return !empty($field['required']) ? $lang->sprintf($lang->anschlusssuche_error_required, $title) : '';
        }

        switch ($field['fieldtype']) {
            case 'select':
            case 'radio':
                if (!in_array($raw, self::options($field), true)) {
                    return $lang->sprintf($lang->anschlusssuche_error_option, $title);
                }
                break;

            case 'multi':
                $options = self::options($field);
                foreach ($raw as $v) {
                    if (!in_array($v, $options, true)) {
                        return $lang->sprintf($lang->anschlusssuche_error_option, $title);
                    }
                }
                break;

            case 'number':
                if (!preg_match('/^-?\d{1,9}$/', $raw)) {
                    return $lang->sprintf($lang->anschlusssuche_error_number, $title);
                }
                break;

            case 'age_range':
                foreach (['min', 'max'] as $k) {
                    if ($raw[$k] !== '' && (!ctype_digit($raw[$k]) || (int)$raw[$k] > self::AGE_LIMIT)) {
                        return $lang->sprintf($lang->anschlusssuche_error_age, $title);
                    }
                }
                if ($raw['min'] === '' && $raw['max'] !== '') {
                    return $lang->sprintf($lang->anschlusssuche_error_age, $title);
                }
                if ($raw['max'] !== '' && (int)$raw['max'] < (int)$raw['min']) {
                    return $lang->sprintf($lang->anschlusssuche_error_age_order, $title);
                }
                if (my_strlen($raw['text']) > 100) {
                    return $lang->sprintf($lang->anschlusssuche_error_length, $title);
                }
                break;

            case 'links':
                foreach ($raw as $row) {
                    if (my_strlen($row['name']) > 120 || my_strlen($row['role']) > 120) {
                        return $lang->sprintf($lang->anschlusssuche_error_length, $title);
                    }
                }
                break;

            case 'text':
                if (my_strlen($raw) > 255) {
                    return $lang->sprintf($lang->anschlusssuche_error_length, $title);
                }
                break;

            case 'textarea':
                if (my_strlen($raw) > 10000) {
                    return $lang->sprintf($lang->anschlusssuche_error_length, $title);
                }
                break;
        }
        return '';
    }

    /**
     * Speicherform für anschlusssuche_values (nicht für age_range/links).
     *
     * @return array|null ['value' => string, 'num_min' => int|null, 'num_max' => int|null] oder null wenn leer
     */
    public static function to_value_row(array $field, $raw): ?array
    {
        if (self::is_empty($field, $raw)) {
            return null;
        }
        switch ($field['fieldtype']) {
            case 'multi':
                $values = array_values(array_unique(array_map('strval', (array)$raw)));
                return ['value' => implode("\n", $values), 'num_min' => null, 'num_max' => null];
            case 'number':
                $n = (int)$raw;
                return ['value' => (string)$n, 'num_min' => $n, 'num_max' => $n];
            default:
                return ['value' => (string)$raw, 'num_min' => null, 'num_max' => null];
        }
    }

    /**
     * Normalisiert eine Altersspanne in Entry-Spalten.
     *
     * @return array ['age_min' => int|null, 'age_max' => int|null, 'age_text' => string]
     */
    public static function to_age_columns($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $min = isset($raw['min']) && $raw['min'] !== '' ? max(0, min(self::AGE_LIMIT, (int)$raw['min'])) : null;
        $max = isset($raw['max']) && $raw['max'] !== '' ? max(0, min(self::AGE_LIMIT, (int)$raw['max'])) : null;
        if ($min === null && $max !== null) {
            $min = $max;
        }
        if ($min !== null && $max !== null && $max < $min) {
            [$min, $max] = [$max, $min];
        }
        $text = isset($raw['text']) ? trim((string)$raw['text']) : '';
        return ['age_min' => $min, 'age_max' => $max, 'age_text' => my_substr($text, 0, 100)];
    }

    /**
     * Bereinigt Anschluss-Zeilen: leere Namen raus, trimmen, uid nur bei existierendem Account.
     *
     * @param array $rows
     * @return array
     */
    public static function clean_links(array $rows): array
    {
        $out = [];
        $uids = [];
        foreach ($rows as $row) {
            $name = isset($row['name']) ? trim((string)$row['name']) : '';
            if ($name === '') {
                continue;
            }
            $uid = isset($row['uid']) ? (int)$row['uid'] : 0;
            $out[] = [
                'name' => my_substr($name, 0, 120),
                'role' => my_substr(isset($row['role']) ? trim((string)$row['role']) : '', 0, 120),
                'uid'  => $uid > 0 ? $uid : 0,
            ];
            if ($uid > 0) {
                $uids[$uid] = true;
            }
        }
        if ($uids) {
            $existing = self::existing_uids(array_keys($uids));
            foreach ($out as $i => $row) {
                if ($row['uid'] > 0 && !isset($existing[$row['uid']])) {
                    $out[$i]['uid'] = 0;
                }
            }
        }
        return $out;
    }

    /** @return array<int, string> uid => username */
    public static function existing_uids(array $uids): array
    {
        global $db;
        $uids = array_values(array_filter(array_map('intval', $uids)));
        $out = [];
        if (!$uids) {
            return $out;
        }
        $query = $db->simple_select('users', 'uid, username', 'uid IN (' . implode(',', $uids) . ')');
        while ($row = $db->fetch_array($query)) {
            $out[(int)$row['uid']] = (string)$row['username'];
        }
        return $out;
    }

    /* ------------------------------------------------------------------ */
    /* Rendering                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * HTML des Eingabeelements (ohne Zeilen-Wrapper).
     */
    public static function render_input(array $field, $raw): string
    {
        global $lang;

        $input_name = 'anschlusssuche[' . htmlspecialchars_uni($field['fkey']) . ']';
        $input_id = 'anschlusssuche_' . htmlspecialchars_uni($field['fkey']);
        $vars = ['input_name' => $input_name, 'input_id' => $input_id];

        switch ($field['fieldtype']) {
            case 'textarea':
                $vars['input_value'] = htmlspecialchars_uni((string)$raw);
                return Anschlusssuche_Renderer::render('anschlusssuche_input_textarea', $vars);

            case 'number':
                $vars['input_value'] = htmlspecialchars_uni((string)$raw);
                return Anschlusssuche_Renderer::render('anschlusssuche_input_number', $vars);

            case 'select':
                $options = Anschlusssuche_Renderer::render('anschlusssuche_input_select_option', [
                    'option_value' => '', 'option_label' => htmlspecialchars_uni($lang->anschlusssuche_please_select), 'selected' => '',
                ]);
                foreach (self::options($field) as $opt) {
                    $options .= Anschlusssuche_Renderer::render('anschlusssuche_input_select_option', [
                        'option_value' => htmlspecialchars_uni($opt),
                        'option_label' => htmlspecialchars_uni($opt),
                        'selected'     => $opt === $raw ? ' selected="selected"' : '',
                    ]);
                }
                $vars['options'] = $options;
                return Anschlusssuche_Renderer::render('anschlusssuche_input_select', $vars);

            case 'radio':
                $html = '';
                foreach (self::options($field) as $opt) {
                    $html .= Anschlusssuche_Renderer::render('anschlusssuche_input_radio_option', $vars + [
                        'option_value' => htmlspecialchars_uni($opt),
                        'option_label' => htmlspecialchars_uni($opt),
                        'checked'      => $opt === $raw ? ' checked="checked"' : '',
                    ]);
                }
                return $html;

            case 'multi':
                $html = '';
                foreach (self::options($field) as $opt) {
                    $html .= Anschlusssuche_Renderer::render('anschlusssuche_input_checkbox_option', $vars + [
                        'option_value' => htmlspecialchars_uni($opt),
                        'option_label' => htmlspecialchars_uni($opt),
                        'checked'      => in_array($opt, (array)$raw, true) ? ' checked="checked"' : '',
                    ]);
                }
                return $html;

            case 'age_range':
                $vars['age_min'] = htmlspecialchars_uni((string)$raw['min']);
                $vars['age_max'] = htmlspecialchars_uni((string)$raw['max']);
                $vars['age_text'] = htmlspecialchars_uni((string)$raw['text']);
                return Anschlusssuche_Renderer::render('anschlusssuche_input_age_range', $vars);

            case 'links':
                $rows = '';
                $raw = (array)$raw;
                $raw[] = ['name' => '', 'role' => '', 'uid' => 0]; // always one empty row
                foreach ($raw as $row) {
                    $rows .= Anschlusssuche_Renderer::render('anschlusssuche_input_links_row', $vars + [
                        'link_name' => htmlspecialchars_uni((string)$row['name']),
                        'link_role' => htmlspecialchars_uni((string)$row['role']),
                        'link_uid'  => (int)$row['uid'] > 0 ? (int)$row['uid'] : '',
                    ]);
                }
                $vars['rows'] = $rows;
                return Anschlusssuche_Renderer::render('anschlusssuche_input_links', $vars);

            default:
                $vars['input_value'] = htmlspecialchars_uni((string)$raw);
                return Anschlusssuche_Renderer::render('anschlusssuche_input_text', $vars);
        }
    }

    /**
     * Escapter Anzeigetext eines Feldes ('' wenn leer).
     */
    public static function render_display(array $field, array $entry): string
    {
        global $lang;

        $raw = self::from_entry($field, $entry);
        if (self::is_empty($field, $raw)) {
            return '';
        }
        switch ($field['fieldtype']) {
            case 'multi':
                return htmlspecialchars_uni(implode($lang->comma, $raw));
            case 'age_range':
                return htmlspecialchars_uni(self::age_label($entry));
            case 'links':
                $parts = [];
                foreach ($raw as $row) {
                    $parts[] = self::link_html($row);
                }
                return implode($lang->comma, $parts);
            case 'textarea':
                return nl2br(htmlspecialchars_uni($raw));
            default:
                return htmlspecialchars_uni((string)$raw);
        }
    }

    /** Unescaped age label: text if set, else "a - b", "a", "a+" */
    public static function age_label(array $entry): string
    {
        global $lang;

        if (isset($entry['age_text']) && (string)$entry['age_text'] !== '') {
            return (string)$entry['age_text'];
        }
        if (!isset($entry['age_min']) || $entry['age_min'] === null || $entry['age_min'] === '') {
            return '';
        }
        $min = (int)$entry['age_min'];
        if ($entry['age_max'] === null || $entry['age_max'] === '') {
            return $lang->sprintf($lang->anschlusssuche_age_open, $min);
        }
        $max = (int)$entry['age_max'];
        if ($max === $min) {
            return $lang->sprintf($lang->anschlusssuche_age_single, $min);
        }
        return $lang->sprintf($lang->anschlusssuche_age_range, $min, $max);
    }

    /** One connection as HTML (profile link when linked). */
    public static function link_html(array $row): string
    {
        $name = htmlspecialchars_uni((string)$row['name']);
        if (!empty($row['uid'])) {
            $name = '<a href="' . get_profile_link((int)$row['uid']) . '">' . $name . '</a>';
        }
        if ((string)$row['role'] !== '') {
            $name .= ' (' . htmlspecialchars_uni((string)$row['role']) . ')';
        }
        return $name;
    }

    /* ------------------------------------------------------------------ */
    /* Filters                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Rohwert eines Suchfilters aus f[fkey].
     *
     * @return mixed '' bzw. [] wenn nicht gesetzt
     */
    public static function filter_from_input(array $field, array $input)
    {
        $key = $field['fkey'];
        $raw = isset($input[$key]) ? $input[$key] : '';
        if ($field['fieldtype'] === 'age_range' || $field['fieldtype'] === 'number') {
            $raw = is_array($raw) ? $raw : [];
            return [
                'min' => isset($raw['min']) && is_string($raw['min']) && ctype_digit(trim($raw['min'])) ? trim($raw['min']) : '',
                'max' => isset($raw['max']) && is_string($raw['max']) && ctype_digit(trim($raw['max'])) ? trim($raw['max']) : '',
            ];
        }
        if (!is_string($raw)) {
            return '';
        }
        $raw = trim($raw);
        if (in_array($field['fieldtype'], self::OPTION_TYPES, true) && !in_array($raw, self::options($field), true)) {
            return '';
        }
        return my_substr($raw, 0, 100);
    }

    public static function filter_is_set(array $field, $raw): bool
    {
        if (is_array($raw)) {
            return $raw['min'] !== '' || $raw['max'] !== '';
        }
        return $raw !== '';
    }

    /**
     * SQL-Bedingung (auf Alias e = entries) für einen gesetzten Filter.
     */
    public static function filter_sql(array $field, $raw): string
    {
        global $db;

        if (!self::filter_is_set($field, $raw)) {
            return '';
        }
        $fid = (int)$field['id'];
        $p = TABLE_PREFIX;

        switch ($field['fieldtype']) {
            case 'age_range':
                return self::age_overlap_sql($raw['min'], $raw['max']);

            case 'number':
                $cond = [];
                if ($raw['min'] !== '') {
                    $cond[] = 'v.num_min >= ' . (int)$raw['min'];
                }
                if ($raw['max'] !== '') {
                    $cond[] = 'v.num_min <= ' . (int)$raw['max'];
                }
                return "EXISTS (SELECT 1 FROM {$p}anschlusssuche_values v WHERE v.entry_id = e.id AND v.field_id = {$fid} AND " . implode(' AND ', $cond) . ')';

            case 'multi':
                $val = $db->escape_string($raw);
                return "EXISTS (SELECT 1 FROM {$p}anschlusssuche_values v WHERE v.entry_id = e.id AND v.field_id = {$fid}"
                    . " AND CONCAT('\\n', v.value, '\\n') LIKE '%\\n" . $db->escape_string_like($raw) . "\\n%')";

            case 'select':
            case 'radio':
                $val = $db->escape_string($raw);
                return "EXISTS (SELECT 1 FROM {$p}anschlusssuche_values v WHERE v.entry_id = e.id AND v.field_id = {$fid} AND v.value = '{$val}')";

            case 'links':
                $like = $db->escape_string_like($raw);
                return "EXISTS (SELECT 1 FROM {$p}anschlusssuche_links l WHERE l.entry_id = e.id AND l.field_id = {$fid} AND l.name LIKE '%{$like}%')";

            default:
                $like = $db->escape_string_like($raw);
                return "EXISTS (SELECT 1 FROM {$p}anschlusssuche_values v WHERE v.entry_id = e.id AND v.field_id = {$fid} AND v.value LIKE '%{$like}%')";
        }
    }

    /**
     * Überlappung der gespeicherten Spanne (e.age_min..e.age_max, max NULL = offen) mit
     * der gesuchten Spanne. Einträge ohne Altersangabe fallen heraus.
     *
     * @param string|int $min
     * @param string|int $max
     */
    public static function age_overlap_sql($min, $max): string
    {
        $cond = ['e.age_min IS NOT NULL'];
        if ((string)$min !== '') {
            $cond[] = '(e.age_max IS NULL OR e.age_max >= ' . (int)$min . ')';
        }
        if ((string)$max !== '') {
            $cond[] = 'e.age_min <= ' . (int)$max;
        }
        return '(' . implode(' AND ', $cond) . ')';
    }
}
