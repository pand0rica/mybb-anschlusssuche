<?php
/**
 * Forenliste (Index, Unterforen-Liste): Foren vom Typ "lose" zählen die losen Gesuche bei
 * Themen/Beiträgen mit und zeigen das neueste lose Gesuch als "Letzter Beitrag", sofern es
 * neuer ist als der letzte echte Beitrag. Sonst wirkt das Forum leer.
 *
 * MyBB baut den Link des letzten Beitrags fest als Thread-Link. Deshalb wird als Thread-ID
 * die negative Forum-ID gesetzt und dieser Platzhalter-Link in pre_output_page auf das Forum
 * umgebogen.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_ForumList
{
    /** @var array|null ['count' => int, 'latest' => array|null] */
    private static $summary = null;

    /** @var int[] fids whose lastpost link has to be rewritten */
    private static $rewritten = [];

    /**
     * Anzahl und neuestes Element aller losen Gesuche (UserCP + Relatives), einmal pro Request.
     *
     * @return array ['count' => int, 'latest' => ['time', 'uid', 'title']|null]
     */
    public static function summary(): array
    {
        global $db;

        if (self::$summary !== null) {
            return self::$summary;
        }
        $count = (int)$db->fetch_field($db->simple_select('anschlusssuche_entries', 'COUNT(*) AS c', "source = 'loose'"), 'c');
        $latest = null;
        $row = $db->fetch_array($db->simple_select('anschlusssuche_entries', 'uid, title, updated_at', "source = 'loose'",
            ['order_by' => 'updated_at', 'order_dir' => 'DESC', 'limit' => 1]));
        if ($row) {
            $latest = ['time' => (int)$row['updated_at'], 'uid' => (int)$row['uid'], 'title' => (string)$row['title']];
        }

        if (Anschlusssuche_RelativesAdapter::available()) {
            $count += Anschlusssuche_RelativesAdapter::count([]);
            $persons = Anschlusssuche_RelativesAdapter::search(['limit' => 1, 'order' => 'updated']);
            if ($persons) {
                $person = $persons[0];
                $time = max((int)$person['updated_at'], (int)$person['created_at']);
                if (!$latest || $time > $latest['time']) {
                    $latest = ['time' => $time, 'uid' => (int)$person['uid'], 'title' => (string)$person['title']];
                }
            }
        }
        return self::$summary = ['count' => $count, 'latest' => $latest];
    }

    /**
     * Ergänzt eine Forenzeile (Hook build_forumbits_forum).
     */
    public static function apply(array $forum): array
    {
        if (empty($forum['fid'])) {
            return $forum;
        }
        $config = Anschlusssuche_Repository::forum((int)$forum['fid']);
        if (!$config || $config['type'] !== 'loose' || empty($config['show_loose_block'])) {
            return $forum;
        }
        $summary = self::summary();
        if ($summary['count'] <= 0) {
            return $forum;
        }
        $forum['threads'] = (int)$forum['threads'] + $summary['count'];
        $forum['posts'] = (int)$forum['posts'] + $summary['count'];

        $latest = $summary['latest'];
        if ($latest && $latest['time'] > (int)$forum['lastpost']) {
            $user = $latest['uid'] > 0 ? get_user($latest['uid']) : null;
            $forum['lastpost'] = $latest['time'];
            $forum['lastposteruid'] = $user ? (int)$user['uid'] : 0;
            $forum['lastposter'] = $user ? (string)$user['username'] : '';
            $forum['lastpostsubject'] = $latest['title'];
            $forum['lastposttid'] = -(int)$forum['fid'];
            self::$rewritten[(int)$forum['fid']] = (int)$forum['fid'];
        }
        return $forum;
    }

    /**
     * Biegt die Platzhalter-Links (Thread-ID = -fid) auf das Forum um (Hook pre_output_page).
     */
    public static function fix_links(string $contents): string
    {
        if (!self::$rewritten) {
            return $contents;
        }
        foreach (self::$rewritten as $fid) {
            $target = get_forum_link($fid);
            // Longest variants first; the lookahead keeps -11 from matching inside -110.
            foreach ([get_thread_link(-$fid, 0, 'lastpost'), get_thread_link(-$fid, 0, 'newpost'), get_thread_link(-$fid)] as $fake) {
                $contents = (string)preg_replace('#' . preg_quote($fake, '#') . '(?![0-9])#', $target, $contents);
            }
        }
        return $contents;
    }
}
