<?php
/**
 * Speichern von Einträgen inkl. Werten/Links und Status-Automatik.
 * Funktioniert ohne Session/$mybb->user (wird auch von Migrationen genutzt).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_EntryService
{
    public const STATUSES = ['free', 'reserved', 'taken'];

    /**
     * @param array $entry  Kernfelder, siehe anschlusssuche_entry_save()
     * @param array $values fkey => Rohwert (alle Feldtypen außer links)
     * @param array $links  fkey => list<['name','role','uid']>
     * @return int Eintrags-ID (0 bei ungültigen Daten)
     */
    public static function save(array $entry, array $values = [], array $links = []): int
    {
        global $db, $plugins;

        $type = isset($entry['type']) ? (string)$entry['type'] : '';
        if (!in_array($type, Anschlusssuche_Repository::TYPES, true)) {
            return 0;
        }
        $source = isset($entry['source']) && $entry['source'] === 'loose' ? 'loose' : 'thread';
        $tid = $source === 'thread' && !empty($entry['tid']) ? (int)$entry['tid'] : 0;
        if ($source === 'thread' && $tid <= 0) {
            return 0;
        }

        // Existing row: by id, else by tid.
        $existing = null;
        if (!empty($entry['id'])) {
            $existing = Anschlusssuche_Repository::entry((int)$entry['id']);
        } elseif ($tid > 0) {
            $existing = Anschlusssuche_Repository::entry_by_tid($tid);
        }

        $fields = Anschlusssuche_Repository::fields($type, false);

        $row = [
            'source'         => $source,
            'fid'            => isset($entry['fid']) ? max(0, (int)$entry['fid']) : ($existing ? (int)$existing['fid'] : 0),
            'uid'            => isset($entry['uid']) ? max(0, (int)$entry['uid']) : ($existing ? (int)$existing['uid'] : 0),
            'type'           => $type,
            'title'          => $db->escape_string(my_substr(trim(isset($entry['title']) ? (string)$entry['title'] : ($existing ? (string)$existing['title'] : '')), 0, 200)),
            'status_manual'  => !empty($entry['status_manual']) ? 1 : 0,
            'slots_total'    => isset($entry['slots_total']) ? max(0, (int)$entry['slots_total']) : 0,
            'slots_taken'    => isset($entry['slots_taken']) ? max(0, (int)$entry['slots_taken']) : 0,
            'slots_open_end' => !empty($entry['slots_open_end']) ? 1 : 0,
            'updated_at'     => isset($entry['updated_at']) ? (int)$entry['updated_at'] : TIME_NOW,
        ];
        if ($source === 'loose') {
            $row['fid'] = 0;
        }

        $status = isset($entry['status']) && in_array($entry['status'], self::STATUSES, true) ? $entry['status'] : 'free';
        $row['status'] = self::compute_status($type, $status, $row['status_manual'], $row['slots_total'], $row['slots_taken'], $row['slots_open_end']);

        // Age: explicit age columns win, else the age_range field value.
        $age = null;
        if (array_key_exists('age_min', $entry) || array_key_exists('age_max', $entry)) {
            $age = Anschlusssuche_FieldTypes::to_age_columns([
                'min'  => isset($entry['age_min']) && $entry['age_min'] !== null ? (string)$entry['age_min'] : '',
                'max'  => isset($entry['age_max']) && $entry['age_max'] !== null ? (string)$entry['age_max'] : '',
                'text' => isset($entry['age_text']) ? (string)$entry['age_text'] : '',
            ]);
        } else {
            foreach ($fields as $field) {
                if ($field['fieldtype'] === 'age_range' && array_key_exists($field['fkey'], $values)) {
                    $age = Anschlusssuche_FieldTypes::to_age_columns($values[$field['fkey']]);
                    break;
                }
            }
        }
        if ($age === null) {
            $age = ['age_min' => null, 'age_max' => null, 'age_text' => ''];
        }
        $row['age_text'] = $db->escape_string($age['age_text']);

        if ($existing) {
            $id = (int)$existing['id'];
            $db->update_query('anschlusssuche_entries', $row, 'id = ' . $id);
        } else {
            $row['created_at'] = isset($entry['created_at']) ? (int)$entry['created_at'] : TIME_NOW;
            if ($tid > 0) {
                $row['tid'] = $tid;
            }
            $id = (int)$db->insert_query('anschlusssuche_entries', $row);
            if ($id <= 0) {
                return 0;
            }
        }
        // NULL-able columns are written separately (insert/update_query cannot write NULL).
        $db->write_query('UPDATE ' . TABLE_PREFIX . 'anschlusssuche_entries SET '
            . 'age_min = ' . ($age['age_min'] === null ? 'NULL' : (int)$age['age_min']) . ', '
            . 'age_max = ' . ($age['age_max'] === null ? 'NULL' : (int)$age['age_max'])
            . ' WHERE id = ' . $id);

        self::write_values($id, $fields, $values);
        self::write_links($id, $fields, $links);

        if (is_object($plugins)) {
            $args = ['entry_id' => $id, 'is_new' => $existing === null];
            $plugins->run_hooks('anschlusssuche_entry_saved', $args);
        }
        return $id;
    }

    /**
     * Gruppen ohne manuellen Status: "taken" bei voller Belegung, sonst "free".
     */
    public static function compute_status(string $type, string $status, int $manual, int $total, int $taken, int $open_end): string
    {
        if ($type !== 'group' || $manual) {
            return in_array($status, self::STATUSES, true) ? $status : 'free';
        }
        if ($total > 0 && $taken >= $total && !$open_end) {
            return 'taken';
        }
        return 'free';
    }

    /**
     * Effektiver Status: "done" in Archivforen, sonst der gespeicherte.
     */
    public static function effective_status(array $entry): string
    {
        if (isset($entry['source']) && $entry['source'] === 'thread') {
            $fid = isset($entry['thread_fid']) ? (int)$entry['thread_fid'] : (int)$entry['fid'];
            $forum = Anschlusssuche_Repository::forum($fid);
            if ($forum && !empty($forum['is_archive'])) {
                return 'done';
            }
        }
        return in_array($entry['status'], self::STATUSES, true) ? (string)$entry['status'] : 'free';
    }

    /**
     * @param array<int, array> $fields
     */
    private static function write_values(int $entry_id, array $fields, array $values): void
    {
        global $db;

        foreach ($fields as $field) {
            $type = $field['fieldtype'];
            if ($type === 'links' || $type === 'age_range' || !array_key_exists($field['fkey'], $values)) {
                continue;
            }
            $where = 'entry_id = ' . $entry_id . ' AND field_id = ' . (int)$field['id'];
            $db->delete_query('anschlusssuche_values', $where);
            $value_row = Anschlusssuche_FieldTypes::to_value_row($field, $values[$field['fkey']]);
            if ($value_row === null) {
                continue;
            }
            $db->write_query('INSERT INTO ' . TABLE_PREFIX . 'anschlusssuche_values (entry_id, field_id, value, num_min, num_max) VALUES ('
                . $entry_id . ', ' . (int)$field['id'] . ", '" . $db->escape_string($value_row['value']) . "', "
                . ($value_row['num_min'] === null ? 'NULL' : (int)$value_row['num_min']) . ', '
                . ($value_row['num_max'] === null ? 'NULL' : (int)$value_row['num_max']) . ')');
        }
    }

    /**
     * @param array<int, array> $fields
     */
    private static function write_links(int $entry_id, array $fields, array $links): void
    {
        global $db;

        foreach ($fields as $field) {
            if ($field['fieldtype'] !== 'links' || !array_key_exists($field['fkey'], $links)) {
                continue;
            }
            $db->delete_query('anschlusssuche_links', 'entry_id = ' . $entry_id . ' AND field_id = ' . (int)$field['id']);
            $order = 0;
            foreach (Anschlusssuche_FieldTypes::clean_links((array)$links[$field['fkey']]) as $row) {
                $db->write_query('INSERT INTO ' . TABLE_PREFIX . 'anschlusssuche_links (entry_id, field_id, name, role, uid, disporder) VALUES ('
                    . $entry_id . ', ' . (int)$field['id'] . ", '" . $db->escape_string($row['name']) . "', '"
                    . $db->escape_string($row['role']) . "', " . ($row['uid'] > 0 ? (int)$row['uid'] : 'NULL') . ', ' . ($order++) . ')');
            }
        }
    }
}
