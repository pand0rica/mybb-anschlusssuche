<?php
/**
 * Eingabeformulare (Thread erstellen/bearbeiten, lose Gesuche) inkl. Kernfelder,
 * Validierung und Speichern.
 *
 * Formularnamen:
 *  - anschlusssuche_present = 1             (Marker: Feldblock war im Formular)
 *  - anschlusssuche_core[status|status_manual|slots_total|slots_taken|slots_open_end|title]
 *  - anschlusssuche[<fkey>]                 (ACP-Felder, siehe FieldTypes)
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_FormHandler
{
    public static function form_submitted(): bool
    {
        global $mybb;
        return $mybb->get_input('anschlusssuche_present', MyBB::INPUT_INT) === 1;
    }

    /** Typ eines Forums für Thread-Formulare (nur single/group), sonst ''. */
    public static function thread_type(int $fid): string
    {
        $forum = Anschlusssuche_Repository::forum($fid);
        if (!$forum || $forum['type'] === 'loose') {
            return '';
        }
        return (string)$forum['type'];
    }

    /* ------------------------------------------------------------------ */
    /* Reading                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Liest Kernfelder + Feldwerte aus dem Request.
     *
     * @return array ['core' => array, 'values' => fkey => raw, 'links' => fkey => rows]
     */
    public static function read_input(string $type): array
    {
        global $mybb;

        $core_in = $mybb->get_input('anschlusssuche_core', MyBB::INPUT_ARRAY);
        $fields_in = $mybb->get_input('anschlusssuche', MyBB::INPUT_ARRAY);

        $status = isset($core_in['status']) && is_string($core_in['status']) ? $core_in['status'] : 'free';
        $core = [
            'title'          => isset($core_in['title']) && is_string($core_in['title']) ? trim($core_in['title']) : '',
            'status'         => in_array($status, Anschlusssuche_EntryService::STATUSES, true) ? $status : 'free',
            'status_manual'  => !empty($core_in['status_manual']) ? 1 : 0,
            'slots_total'    => isset($core_in['slots_total']) ? max(0, (int)$core_in['slots_total']) : 0,
            'slots_taken'    => isset($core_in['slots_taken']) ? max(0, (int)$core_in['slots_taken']) : 0,
            'slots_open_end' => !empty($core_in['slots_open_end']) ? 1 : 0,
        ];
        if ($type !== 'group') {
            $core['status_manual'] = 1;
        }

        $values = [];
        $links = [];
        foreach (Anschlusssuche_Repository::fields($type) as $field) {
            $raw = Anschlusssuche_FieldTypes::from_input($field, $fields_in);
            if ($field['fieldtype'] === 'links') {
                $links[$field['fkey']] = $raw;
            } else {
                $values[$field['fkey']] = $raw;
            }
        }
        return ['core' => $core, 'values' => $values, 'links' => $links];
    }

    /** @return string[] Fehlermeldungen (unescaped) */
    public static function validate_input(string $type, array $input, bool $with_title = false): array
    {
        global $lang;

        $errors = [];
        $core = $input['core'];
        if ($with_title && $core['title'] === '') {
            $errors[] = $lang->sprintf($lang->anschlusssuche_error_required, $lang->anschlusssuche_core_title);
        }
        if ($with_title && my_strlen($core['title']) > 200) {
            $errors[] = $lang->sprintf($lang->anschlusssuche_error_length, $lang->anschlusssuche_core_title);
        }
        if ($type === 'group') {
            if ($core['slots_total'] < 1) {
                $errors[] = $lang->sprintf($lang->anschlusssuche_error_required, $lang->anschlusssuche_core_slots_total);
            } elseif ($core['slots_taken'] > $core['slots_total'] && !$core['slots_open_end']) {
                $errors[] = $lang->anschlusssuche_error_slots;
            }
        }
        foreach (Anschlusssuche_Repository::fields($type) as $field) {
            $raw = $field['fieldtype'] === 'links'
                ? (isset($input['links'][$field['fkey']]) ? $input['links'][$field['fkey']] : [])
                : (isset($input['values'][$field['fkey']]) ? $input['values'][$field['fkey']] : Anschlusssuche_FieldTypes::empty_value($field));
            $error = Anschlusssuche_FieldTypes::validate($field, $raw);
            if ($error !== '') {
                $errors[] = $error;
            }
        }
        return $errors;
    }

    /* ------------------------------------------------------------------ */
    /* Thread forms                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Feldblock für newthread/editpost. $tid = null bei neuem Thread.
     */
    public static function thread_form(int $fid, ?int $tid): string
    {
        $type = self::thread_type($fid);
        if ($type === '') {
            return '';
        }
        anschlusssuche_lang();

        if (self::form_submitted()) {
            $input = self::read_input($type);
        } else {
            $entry = null;
            if ($tid) {
                $entries = Anschlusssuche_Repository::entries_by_tids([$tid]);
                $entry = isset($entries[$tid]) ? $entries[$tid] : null;
            }
            $input = self::input_from_entry($type, $entry);
        }
        return self::render_rows($type, $input, false);
    }

    /** Formularwerte aus einem gespeicherten Eintrag (oder Defaults). */
    public static function input_from_entry(string $type, ?array $entry): array
    {
        $core = [
            'title'          => $entry ? (string)$entry['title'] : '',
            'status'         => $entry ? (string)$entry['status'] : 'free',
            'status_manual'  => $entry ? (int)$entry['status_manual'] : 0,
            'slots_total'    => $entry ? (int)$entry['slots_total'] : 0,
            'slots_taken'    => $entry ? (int)$entry['slots_taken'] : 0,
            'slots_open_end' => $entry ? (int)$entry['slots_open_end'] : 0,
        ];
        $values = [];
        $links = [];
        foreach (Anschlusssuche_Repository::fields($type) as $field) {
            $raw = Anschlusssuche_FieldTypes::from_entry($field, $entry);
            if ($field['fieldtype'] === 'links') {
                $links[$field['fkey']] = $raw;
            } else {
                $values[$field['fkey']] = $raw;
            }
        }
        return ['core' => $core, 'values' => $values, 'links' => $links];
    }

    /**
     * Rendert Kernfelder + ACP-Felder als Formularzeilen.
     */
    public static function render_rows(string $type, array $input, bool $with_title): string
    {
        global $lang, $mybb;

        $core = $input['core'];
        $rows = '';

        if ($with_title) {
            $rows .= self::row($lang->anschlusssuche_core_title, '', true,
                Anschlusssuche_Renderer::render('anschlusssuche_input_text', [
                    'input_name'  => 'anschlusssuche_core[title]',
                    'input_id'    => 'anschlusssuche_core_title',
                    'input_value' => htmlspecialchars_uni($core['title']),
                ]));
        }

        // Status
        $status_options = '';
        foreach (Anschlusssuche_EntryService::STATUSES as $st) {
            $status_options .= '<option value="' . $st . '"' . ($core['status'] === $st ? ' selected="selected"' : '') . '>'
                . htmlspecialchars_uni($lang->{'anschlusssuche_status_' . $st}) . '</option>';
        }
        $status_manual_checked = !empty($core['status_manual']) ? ' checked="checked"' : '';
        $is_group = $type === 'group';
        $rows .= self::row($lang->anschlusssuche_core_status, $is_group ? $lang->anschlusssuche_core_status_group_desc : '', false,
            Anschlusssuche_Renderer::render('anschlusssuche_form_status', [
                'status_options'        => $status_options,
                'status_manual_checked' => $status_manual_checked,
                'show_manual'           => $is_group ? '' : ' style="display: none;"',
            ]));

        if ($is_group) {
            $rows .= self::row($lang->anschlusssuche_core_slots, $lang->anschlusssuche_core_slots_desc, true,
                Anschlusssuche_Renderer::render('anschlusssuche_form_slots', [
                    'slots_total'    => (int)$core['slots_total'],
                    'slots_taken'    => (int)$core['slots_taken'],
                    'open_checked'   => !empty($core['slots_open_end']) ? ' checked="checked"' : '',
                ]));
        }

        foreach (Anschlusssuche_Repository::fields($type) as $field) {
            $raw = $field['fieldtype'] === 'links'
                ? (isset($input['links'][$field['fkey']]) ? $input['links'][$field['fkey']] : [])
                : (isset($input['values'][$field['fkey']]) ? $input['values'][$field['fkey']] : Anschlusssuche_FieldTypes::empty_value($field));
            $rows .= self::row((string)$field['title'], (string)$field['description'], !empty($field['required']),
                Anschlusssuche_FieldTypes::render_input($field, $raw));
        }

        $jsurl = htmlspecialchars_uni($mybb->asset_url . '/jscripts/anschlusssuche.js');
        return Anschlusssuche_Renderer::render('anschlusssuche_form_fields', ['rows' => $rows, 'jsurl' => $jsurl]);
    }

    private static function row(string $title, string $description, bool $required, string $input): string
    {
        return Anschlusssuche_Renderer::render('anschlusssuche_form_row', [
            'row_title'       => htmlspecialchars_uni($title),
            'row_description' => $description !== '' ? htmlspecialchars_uni($description) : '',
            'row_required'    => $required ? ' <span class="anschlusssuche-required">*</span>' : '',
            'row_input'       => $input,
        ]);
    }

    /**
     * @param PostDataHandler $handler
     */
    public static function validate_thread($handler, int $fid): void
    {
        global $lang;

        $type = self::thread_type($fid);
        if ($type === '') {
            return;
        }
        anschlusssuche_lang();
        $errors = self::validate_input($type, self::read_input($type));
        if ($errors) {
            // DataHandler keys errors by code, so all messages go into one entry.
            $handler->set_error('anschlusssuche_error_generic', [implode('<br />', array_map('htmlspecialchars_uni', $errors))]);
        }
    }

    public static function save_thread(int $tid, int $fid, int $uid): void
    {
        $type = self::thread_type($fid);
        if ($type === '' || $tid <= 0) {
            return;
        }
        $thread = get_thread($tid);
        if (!$thread) {
            return;
        }
        $input = self::read_input($type);
        $entry = $input['core'] + [
            'source' => 'thread',
            'tid'    => $tid,
            'fid'    => (int)$thread['fid'],
            'uid'    => (int)$thread['uid'],
            'type'   => $type,
        ];
        $entry['title'] = (string)$thread['subject'];
        $existing = Anschlusssuche_Repository::entry_by_tid($tid);
        if ($existing) {
            // A type change of the forum must not silently drop the stored type.
            $entry['type'] = $type;
        } else {
            $entry['created_at'] = (int)$thread['dateline'];
        }
        Anschlusssuche_EntryService::save($entry, $input['values'], $input['links']);
    }
}
