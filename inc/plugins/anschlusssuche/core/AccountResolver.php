<?php
/**
 * Alle Accounts eines Spielers (EnhancedAccountSwitcher: users.as_uid = UID des Hauptaccounts).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_AccountResolver
{
    /** @var array<int, int[]> */
    private static $cache = [];

    /** @return int[] uid selbst plus alle verbundenen Accounts (Haupt- und Zweitaccounts) */
    public static function all_uids(int $uid): array
    {
        global $db;

        if ($uid <= 0) {
            return [];
        }
        if (isset(self::$cache[$uid])) {
            return self::$cache[$uid];
        }
        if (!$db->field_exists('as_uid', 'users')) {
            return self::$cache[$uid] = [$uid];
        }
        $row = $db->fetch_array($db->simple_select('users', 'as_uid', 'uid = ' . $uid));
        $root = ($row && (int)$row['as_uid'] > 0) ? (int)$row['as_uid'] : $uid;

        $uids = [$root => $root];
        $query = $db->simple_select('users', 'uid', "uid = {$root} OR as_uid = {$root}");
        while ($r = $db->fetch_array($query)) {
            $uids[(int)$r['uid']] = (int)$r['uid'];
        }
        $uids[$uid] = $uid;
        return self::$cache[$uid] = array_values($uids);
    }
}
