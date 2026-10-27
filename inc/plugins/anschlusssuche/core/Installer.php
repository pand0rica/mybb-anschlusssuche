<?php
/**
 * Lifecycle: Tabellen, Settings, Templates, Stylesheet, Core-Template-Marker, UserCP-Link.
 *
 * Einzelne Teile sind öffentlich, damit externe Migrationen sie gezielt aufrufen können,
 * ohne einen vollständigen activate()-Lauf.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Anschlusssuche_Installer
{
    public const TABLES = [
        'anschlusssuche_forums',
        'anschlusssuche_fields',
        'anschlusssuche_entries',
        'anschlusssuche_values',
        'anschlusssuche_links',
        'anschlusssuche_header',
        'anschlusssuche_credits',
        'anschlusssuche_shop_items',
    ];

    public const STYLESHEET = 'anschlusssuche.css';

    /** Marker placed in the core templates newthread/editpost. */
    public const FORM_MARKER = '{$anschlusssuche_fields}';

    public static function is_installed(): bool
    {
        global $db;
        return $db->table_exists('anschlusssuche_entries');
    }

    public static function install(): void
    {
        self::create_tables();
        self::install_settings();
        rebuild_settings();
    }

    public static function uninstall(): void
    {
        global $db;

        foreach (self::TABLES as $table) {
            if ($db->table_exists($table)) {
                $db->drop_table($table);
            }
        }
        $db->delete_query('settings', "name LIKE 'anschlusssuche\\_%'");
        $db->delete_query('settinggroups', "name = 'anschlusssuche'");
        rebuild_settings();
        self::set_admin_permission(false);
    }

    /**
     * ACP-Recht tools/anschlusssuche: beim Aktivieren allen Admins/Gruppen geben, die den
     * Tools-Bereich nutzen dürfen (vorhandene Einstellungen bleiben unangetastet);
     * beim Deinstallieren wieder entfernen.
     */
    public static function set_admin_permission(bool $grant): void
    {
        global $db;

        $query = $db->simple_select('adminoptions', 'uid, permissions', "permissions != ''");
        while ($row = $db->fetch_array($query)) {
            $perms = my_unserialize($row['permissions']);
            if (!is_array($perms)) {
                continue;
            }
            if ($grant) {
                $tools = isset($perms['tools']) && is_array($perms['tools']) ? $perms['tools'] : [];
                if (isset($tools['anschlusssuche']) || !in_array('1', array_map('strval', $tools), true)) {
                    continue; // already decided, or no Tools access at all
                }
                $perms['tools']['anschlusssuche'] = 1;
            } else {
                if (!isset($perms['tools']['anschlusssuche'])) {
                    continue;
                }
                unset($perms['tools']['anschlusssuche']);
            }
            $db->update_query('adminoptions', ['permissions' => $db->escape_string(my_serialize($perms))], 'uid = ' . (int)$row['uid']);
        }
    }

    public static function activate(): void
    {
        self::install_settings();
        rebuild_settings();
        self::install_templates();
        self::install_stylesheet();
        self::install_form_markers();
        self::install_usercp_link();
        self::install_default_fields();
        self::set_admin_permission(true);
    }

    public static function deactivate(): void
    {
        self::remove_usercp_link();
        self::remove_form_markers();
        self::remove_stylesheet();
        self::remove_templates();
    }

    /* ------------------------------------------------------------------ */

    public static function create_tables(): void
    {
        global $db;

        $collation = $db->build_create_table_collation();
        $p = TABLE_PREFIX;

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_forums` (
            `fid` INT(11) UNSIGNED NOT NULL,
            `type` VARCHAR(10) NOT NULL DEFAULT 'single',
            `is_archive` TINYINT(1) NOT NULL DEFAULT 0,
            `show_filterbar` TINYINT(1) NOT NULL DEFAULT 1,
            `show_loose_block` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`fid`)
        ) ENGINE=InnoDB{$collation};");

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_fields` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(10) NOT NULL DEFAULT 'single',
            `fkey` VARCHAR(50) NOT NULL DEFAULT '',
            `title` VARCHAR(100) NOT NULL DEFAULT '',
            `description` TEXT,
            `fieldtype` VARCHAR(20) NOT NULL DEFAULT 'text',
            `required` TINYINT(1) NOT NULL DEFAULT 0,
            `disporder` INT(11) NOT NULL DEFAULT 0,
            `options` TEXT,
            `icon` VARCHAR(100) NOT NULL DEFAULT '',
            `show_list` TINYINT(1) NOT NULL DEFAULT 1,
            `show_profile` TINYINT(1) NOT NULL DEFAULT 1,
            `show_header` TINYINT(1) NOT NULL DEFAULT 0,
            `filterable` TINYINT(1) NOT NULL DEFAULT 0,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `type_fkey` (`type`, `fkey`)
        ) ENGINE=InnoDB{$collation};");

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_entries` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `source` VARCHAR(10) NOT NULL DEFAULT 'thread',
            `tid` INT(11) UNSIGNED NULL DEFAULT NULL,
            `fid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `uid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `type` VARCHAR(10) NOT NULL DEFAULT 'single',
            `title` VARCHAR(200) NOT NULL DEFAULT '',
            `status` VARCHAR(10) NOT NULL DEFAULT 'free',
            `status_manual` TINYINT(1) NOT NULL DEFAULT 0,
            `slots_total` INT(11) NOT NULL DEFAULT 0,
            `slots_taken` INT(11) NOT NULL DEFAULT 0,
            `slots_open_end` TINYINT(1) NOT NULL DEFAULT 0,
            `age_min` INT(11) NULL DEFAULT NULL,
            `age_max` INT(11) NULL DEFAULT NULL,
            `age_text` VARCHAR(100) NOT NULL DEFAULT '',
            `created_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `tid` (`tid`),
            KEY `type_status` (`type`, `status`),
            KEY `uid` (`uid`),
            KEY `fid` (`fid`)
        ) ENGINE=InnoDB{$collation};");

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_values` (
            `entry_id` INT(11) UNSIGNED NOT NULL,
            `field_id` INT(11) UNSIGNED NOT NULL,
            `value` TEXT,
            `num_min` INT(11) NULL DEFAULT NULL,
            `num_max` INT(11) NULL DEFAULT NULL,
            PRIMARY KEY (`entry_id`, `field_id`),
            KEY `field_value` (`field_id`, `value`(100))
        ) ENGINE=InnoDB{$collation};");

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_links` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `entry_id` INT(11) UNSIGNED NOT NULL,
            `field_id` INT(11) UNSIGNED NOT NULL,
            `name` VARCHAR(120) NOT NULL DEFAULT '',
            `role` VARCHAR(120) NOT NULL DEFAULT '',
            `uid` INT(11) UNSIGNED NULL DEFAULT NULL,
            `disporder` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `entry_id` (`entry_id`),
            KEY `uid` (`uid`)
        ) ENGINE=InnoDB{$collation};");

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_header` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `entry_id` INT(11) UNSIGNED NULL DEFAULT NULL,
            `relatives_person_id` INT(11) UNSIGNED NULL DEFAULT NULL,
            `source` VARCHAR(10) NOT NULL DEFAULT 'acp',
            `owner_uid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `start_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `end_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `note` VARCHAR(255) NOT NULL DEFAULT '',
            `credit_id` INT(11) UNSIGNED NULL DEFAULT NULL,
            `created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `created_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `period` (`start_at`, `end_at`)
        ) ENGINE=InnoDB{$collation};");

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_credits` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `uid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `shop_item_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `user_item_id` INT(11) UNSIGNED NULL DEFAULT NULL,
            `days` INT(11) NOT NULL DEFAULT 0,
            `created_at` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `redeemed_at` INT(11) UNSIGNED NULL DEFAULT NULL,
            `header_id` INT(11) UNSIGNED NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `uid` (`uid`)
        ) ENGINE=InnoDB{$collation};");

        $db->write_query("CREATE TABLE IF NOT EXISTS `{$p}anschlusssuche_shop_items` (
            `shop_item_id` INT(11) UNSIGNED NOT NULL,
            `days` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`shop_item_id`)
        ) ENGINE=InnoDB{$collation};");
    }

    /* ------------------------------------------------------------------ */

    public static function install_settings(): void
    {
        global $db;

        $existing = $db->fetch_array($db->simple_select('settinggroups', 'gid', "name = 'anschlusssuche'"));
        if ($existing) {
            $gid = (int)$existing['gid'];
        } else {
            $gid = (int)$db->insert_query('settinggroups', [
                'name'        => 'anschlusssuche',
                'title'       => 'Anschluss-Suchmaschine',
                'description' => 'Settings for wanted-character ads (single, group, loose), profile, header and search.',
                'disporder'   => 50,
                'isdefault'   => 0,
            ]);
        }

        $settings = [
            ['anschlusssuche_enabled', 'Enable plugin', 'Master switch for all front-end features.', 'onoff', '1'],
            ['anschlusssuche_search_guests', 'Search for guests', 'Allow guests to use the search page anschluss.php.', 'yesno', '1'],
            ['anschlusssuche_results_per_page', 'Search results per page', 'Number of results per page on the search page.', 'numeric', '20'],
            ['anschlusssuche_loose_per_page', 'Loose ads per page', 'Number of cards per page in the loose ads block of a forum.', 'numeric', '12'],
            ['anschlusssuche_loose_groups', 'Groups allowed to create loose ads', 'User groups that may create loose ads in the User CP.', 'groupselect', '-1'],
            ['anschlusssuche_loose_max_per_user', 'Max. loose ads per account', 'Maximum number of loose ads per account (0 = unlimited).', 'numeric', '0'],
            ['anschlusssuche_relatives_enabled', 'Include Relatives', 'Show persons marked as wanted in the Relatives plugin as loose ads (requires a Relatives version with relatives_count_persons()).', 'yesno', '1'],
            ['anschlusssuche_relatives_gender_map', 'Relatives gender mapping', 'One line per code: code=label. Maps the Relatives gender code to the label used for display and filters.', 'textarea', "0=male\n1=female\n2=diverse"],
            ['anschlusssuche_profile_enabled', 'Show in profile', 'Show the ads block in user profiles (variable {$anschlusssuche_profile}).', 'yesno', '1'],
            ['anschlusssuche_profile_show_done', 'Show finished ads in profile', 'Show ads from archive forums as their own section in the profile.', 'yesno', '0'],
            ['anschlusssuche_profile_hide_relatives', 'Hide Relatives persons in profile', 'Do not list persons from the Relatives plugin in the profile block (useful when the Relatives wanted box is shown in the profile as well).', 'yesno', '0'],
            ['anschlusssuche_header_enabled', 'Header teaser', 'Fill the variable {$anschlusssuche_header} with fixed and random ads.', 'yesno', '1'],
            ['anschlusssuche_header_count', 'Header: number of ads', 'Total number of ads in the header (fixed + random).', 'numeric', '3'],
            ['anschlusssuche_header_forums', 'Header: source forums', 'Forums from which random ads are picked. Forums without an assigned ad type only show the subject.', 'forumselect', ''],
            ['anschlusssuche_header_loose', 'Header: include loose ads', 'Add own loose ads to the random pool.', 'yesno', '0'],
            ['anschlusssuche_header_free_only', 'Header: only open ads', 'Only pick random ads with status "free".', 'yesno', '1'],
            ['anschlusssuche_shop_enabled', 'NewPoints shop (prepared)', 'Allow buying fixed header slots via the NewPoints shop. Prepared for a later version – currently without effect.', 'yesno', '0'],
            ['anschlusssuche_shop_max_concurrent', 'Shop: max. concurrent header slots', 'Maximum number of purchased header entries at the same time (0 = header count).', 'numeric', '0'],
        ];

        $order = 1;
        foreach ($settings as $s) {
            $name = $db->escape_string($s[0]);
            if ($db->fetch_array($db->simple_select('settings', 'sid', "name = '{$name}'"))) {
                $order++;
                continue;
            }
            $db->insert_query('settings', [
                'name'        => $name,
                'title'       => $db->escape_string($s[1]),
                'description' => $db->escape_string($s[2]),
                'optionscode' => $db->escape_string($s[3]),
                'value'       => $db->escape_string($s[4]),
                'disporder'   => $order++,
                'gid'         => $gid,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */

    public static function install_templates(): void
    {
        global $db;

        $dir = ANSCHLUSSSUCHE_DIR . 'templates/';
        if (!is_dir($dir)) {
            return;
        }
        $files = glob($dir . '*.tpl');
        if (!$files) {
            return;
        }
        foreach ($files as $file) {
            $title = basename($file, '.tpl');
            $content = (string)file_get_contents($file);
            $safe_title = $db->escape_string($title);
            $existing = $db->fetch_array($db->simple_select('templates', 'tid', "title = '{$safe_title}' AND sid = -1"));
            if ($existing) {
                $db->update_query('templates', [
                    'template' => $db->escape_string($content),
                    'dateline' => TIME_NOW,
                ], "tid = " . (int)$existing['tid']);
                continue;
            }
            $db->insert_query('templates', [
                'title'    => $safe_title,
                'template' => $db->escape_string($content),
                'sid'      => -1,
                'version'  => '1800',
                'dateline' => TIME_NOW,
            ]);
        }
    }

    public static function remove_templates(): void
    {
        global $db;
        $db->delete_query('templates', "title LIKE 'anschlusssuche\\_%' AND sid = -1");
    }

    /* ------------------------------------------------------------------ */

    public static function install_stylesheet(): void
    {
        global $db;

        self::require_theme_functions();
        $path = ANSCHLUSSSUCHE_DIR . 'assets/' . self::STYLESHEET;
        if (!file_exists($path)) {
            return;
        }
        $css = $db->escape_string((string)file_get_contents($path));
        $name = self::STYLESHEET;

        $themes = $db->simple_select('themes', 'tid');
        while ($theme = $db->fetch_array($themes)) {
            $tid = (int)$theme['tid'];
            $existing = $db->fetch_array($db->simple_select('themestylesheets', 'sid', "name = '{$name}' AND tid = {$tid}"));
            if ($existing) {
                $db->update_query('themestylesheets', [
                    'stylesheet'   => $css,
                    'lastmodified' => TIME_NOW,
                ], 'sid = ' . (int)$existing['sid'], 1);
            } else {
                $sid = (int)$db->insert_query('themestylesheets', [
                    'name'         => $name,
                    'tid'          => $tid,
                    'attachedto'   => '',
                    'stylesheet'   => $css,
                    'lastmodified' => TIME_NOW,
                    'cachefile'    => $name,
                ]);
                $db->update_query('themestylesheets', ['cachefile' => "css.php?stylesheet={$sid}"], "sid = {$sid}", 1);
            }
            update_theme_stylesheet_list($tid);
        }
    }

    public static function remove_stylesheet(): void
    {
        global $db;

        self::require_theme_functions();
        $name = self::STYLESHEET;
        $db->delete_query('themestylesheets', "name = '{$name}'");
        $themes = $db->simple_select('themes', 'tid');
        while ($theme = $db->fetch_array($themes)) {
            update_theme_stylesheet_list((int)$theme['tid']);
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * Fügt {$anschlusssuche_fields} vor {$posticons} (Fallback: vor {$attachbox}) in
     * newthread/editpost aller Template-Versionen ein. Idempotent.
     */
    public static function install_form_markers(): void
    {
        global $db;

        foreach (['newthread', 'editpost'] as $title) {
            $query = $db->simple_select('templates', 'tid, template', "title = '{$title}'");
            while ($tpl = $db->fetch_array($query)) {
                $content = (string)$tpl['template'];
                if (strpos($content, self::FORM_MARKER) !== false) {
                    continue;
                }
                $new = $content;
                foreach (['{$posticons}', '{$attachbox}'] as $needle) {
                    $pos = strpos($content, $needle);
                    if ($pos !== false) {
                        $new = substr($content, 0, $pos) . self::FORM_MARKER . "\n" . substr($content, $pos);
                        break;
                    }
                }
                if ($new !== $content) {
                    $db->update_query('templates', ['template' => $db->escape_string($new)], 'tid = ' . (int)$tpl['tid']);
                }
            }
        }
    }

    public static function remove_form_markers(): void
    {
        global $db;

        foreach (['newthread', 'editpost'] as $title) {
            $query = $db->simple_select('templates', 'tid, template', "title = '{$title}'");
            while ($tpl = $db->fetch_array($query)) {
                $content = (string)$tpl['template'];
                $new = str_replace([self::FORM_MARKER . "\n", self::FORM_MARKER], '', $content);
                if ($new !== $content) {
                    $db->update_query('templates', ['template' => $db->escape_string($new)], 'tid = ' . (int)$tpl['tid']);
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */

    /** Anchor row in usercp_nav_misc (core "Drafts" row), see dev kit boilerplate §14. */
    public static function usercp_anchor(): string
    {
        return '<tr><td class="trow1 smalltext"><a href="usercp.php?action=drafts" class="usercp_nav_item usercp_nav_drafts">{$draftcount}</a></td></tr>';
    }

    public static function usercp_row(): string
    {
        return '<tr><td class="trow1 smalltext"><a href="usercp.php?action=anschlusssuche_loose" class="usercp_nav_item usercp_nav_anschlusssuche" style="background-image: url(\'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22%3E%3Ccircle cx=%226.5%22 cy=%226.5%22 r=%224.5%22 fill=%22none%22 stroke=%22%23555%22 stroke-width=%222%22/%3E%3Cpath d=%22M10 10l4 4%22 stroke=%22%23555%22 stroke-width=%222%22/%3E%3C/svg%3E\');">{$lang->anschlusssuche_usercp_nav}</a></td></tr>';
    }

    public static function install_usercp_link(): void
    {
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        self::remove_usercp_link();
        $anchor = self::usercp_anchor();
        find_replace_templatesets('usercp_nav_misc', '#' . preg_quote($anchor, '#') . '#i', $anchor . self::usercp_row());
    }

    public static function remove_usercp_link(): void
    {
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        find_replace_templatesets('usercp_nav_misc', '#' . preg_quote(self::usercp_row(), '#') . '#i', '', 0);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Legt generische Standardfelder an, aber nur wenn noch gar keine Felder existieren
     * (Reaktivieren dupliziert nichts, eine Migration kann vorher eigene Felder anlegen).
     */
    public static function install_default_fields(): void
    {
        global $db;

        if (!$db->table_exists('anschlusssuche_fields')) {
            return;
        }
        $count = (int)$db->fetch_field($db->simple_select('anschlusssuche_fields', 'COUNT(*) AS c'), 'c');
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['age', 'Age', 'age_range', 0, '', 'fas fa-birthday-cake', 1],
            ['gender', 'Gender', 'radio', 1, "male\nfemale\ndiverse\nany", 'fas fa-venus-mars', 1],
            ['relation', 'Relation', 'select', 0, "Family\nFriendship\nRomance\nEnmity\nWork\nOther", 'fas fa-street-view', 1],
            ['tags', 'Other', 'text', 0, '', 'far fa-bookmark', 0],
            ['links', 'Connections', 'links', 0, '', 'fas fa-star', 0],
        ];

        foreach (['single', 'group', 'loose'] as $type) {
            $order = 1;
            foreach ($defaults as $d) {
                $db->insert_query('anschlusssuche_fields', [
                    'type'         => $type,
                    'fkey'         => $d[0],
                    'title'        => $db->escape_string($d[1]),
                    'description'  => '',
                    'fieldtype'    => $d[2],
                    'required'     => $d[3],
                    'disporder'    => $order++,
                    'options'      => $db->escape_string($d[4]),
                    'icon'         => $db->escape_string($d[5]),
                    'show_list'    => 1,
                    'show_profile' => 1,
                    'show_header'  => $d[0] === 'links' ? 0 : 1,
                    'filterable'   => $d[6],
                    'active'       => 1,
                ]);
            }
        }
    }

    private static function require_theme_functions(): void
    {
        global $config;

        if (!defined('MYBB_ADMIN_DIR')) {
            $admin_dir = isset($config['admin_dir']) ? $config['admin_dir'] : 'admin';
            define('MYBB_ADMIN_DIR', MYBB_ROOT . $admin_dir . '/');
        }
        require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        require_once MYBB_ADMIN_DIR . 'inc/functions_themes.php';
    }
}
