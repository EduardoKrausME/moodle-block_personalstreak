<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * classes/service/config_service.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service;

/**
 * Course configuration service.
 *
 * @package block_personalstreak
 */
class config_service {
    /** @var array<int,array> Per-request effective config cache. */
    private static $cache = [];

    /**
     * Return normalized effective config for a course.
     *
     * @param int $courseid Course id.
     * @return array
     */
    public static function get_for_course(int $courseid): array {
        global $DB;

        if (isset(self::$cache[$courseid])) {
            return self::$cache[$courseid];
        }

        $defaults = self::get_defaults();
        $record = $DB->get_record('block_personalstreak_cfg', ['courseid' => $courseid]);
        if (!$record) {
            self::$cache[$courseid] = $defaults;
            return $defaults;
        }

        $config = $defaults;
        foreach ($config as $key => $unused) {
            if (property_exists($record, $key)) {
                $config[$key] = $record->{$key};
            }
        }
        self::$cache[$courseid] = self::normalize($config);
        return self::$cache[$courseid];
    }

    /**
     * Synchronize block instance configuration to course configuration.
     *
     * @param int $courseid Course id.
     * @param object $data Block config object.
     * @return void
     */
    public static function save_for_course(int $courseid, $data): void {
        global $DB;

        $effective = self::get_defaults();
        foreach (array_keys($effective) as $key) {
            if (property_exists($data, $key)) {
                $effective[$key] = $data->{$key};
            }
        }
        $effective = self::normalize($effective);
        $now = time();

        $record = $DB->get_record('block_personalstreak_cfg', ['courseid' => $courseid]);
        $dbrecord = (object)$effective;
        $dbrecord->courseid = $courseid;
        $dbrecord->timemodified = $now;
        $dbrecord->ignoreweekdays = implode(',', $effective['ignoreweekdays']);

        if ($record) {
            $dbrecord->id = $record->id;
            $dbrecord->timecreated = $record->timecreated;
            $DB->update_record('block_personalstreak_cfg', $dbrecord);
        } else {
            $dbrecord->timecreated = $now;
            $DB->insert_record('block_personalstreak_cfg', $dbrecord);
        }

        self::$cache[$courseid] = $effective;

        // Force lazy recalculation after a rule change without scanning all users now.
        $DB->set_field('block_personalstreak_state', 'timemodified', 0, ['courseid' => $courseid]);
    }

    /**
     * Site defaults.
     *
     * @return array
     */
    public static function get_defaults(): array {
        $milestones = get_config('block_personalstreak', 'milestones');
        if ($milestones === false || trim((string)$milestones) === '') {
            $milestones = "3|0|0|0|0|1\n7|0|0|0|0|1\n14|0|0|0|0|1\n30|0|0|0|0|1\n60|0|0|0|0|1\n100|0|0|0|0|1";
        }

        return self::normalize([
            'trackcourseaccess' => self::bool_config('trackcourseaccess', true),
            'trackcompletion' => self::bool_config('trackcompletion', true),
            'tracksubmission' => self::bool_config('tracksubmission', true),
            'trackquiz' => self::bool_config('trackquiz', true),
            'trackforum' => self::bool_config('trackforum', true),
            'trackresourceview' => self::bool_config('trackresourceview', true),
            'trackpersonalxp' => self::bool_config('trackpersonalxp', true),
            'customevents' => (string)(get_config('block_personalstreak', 'customevents') ?: ''),
            'protectionmode' => (string)(get_config('block_personalstreak', 'protectionmode') ?: 'none'),
            'protectiondays' => max(0, (int)(get_config('block_personalstreak', 'protectiondays') ?: 1)),
            'protectionperiod' => max(1, (int)(get_config('block_personalstreak', 'protectionperiod') ?: 30)),
            'daymode' => (string)(get_config('block_personalstreak', 'daymode') ?: 'normal'),
            'ignoreweekdays' => (string)(get_config('block_personalstreak', 'ignoreweekdays') ?: ''),
            'milestones' => (string)$milestones,
        ]);
    }

    /**
     * Normalize config values from DB, admin settings and block form.
     *
     * @param array $config Config.
     * @return array
     */
    public static function normalize(array $config): array {
        foreach ([
            'trackcourseaccess', 'trackcompletion', 'tracksubmission', 'trackquiz',
            'trackforum', 'trackresourceview', 'trackpersonalxp',
        ] as $key) {
            $config[$key] = !empty($config[$key]) ? 1 : 0;
        }

        $config['customevents'] = trim((string)($config['customevents'] ?? ''));
        $config['milestones'] = trim((string)($config['milestones'] ?? ''));

        $allowedprotection = ['none', 'one', 'rolling'];
        $config['protectionmode'] = in_array(($config['protectionmode'] ?? ''), $allowedprotection, true)
            ? $config['protectionmode'] : 'none';
        $config['protectiondays'] = max(0, (int)($config['protectiondays'] ?? 0));
        $config['protectionperiod'] = max(1, (int)($config['protectionperiod'] ?? 30));

        $alloweddaymodes = ['normal', 'weekends', 'custom'];
        $config['daymode'] = in_array(($config['daymode'] ?? ''), $alloweddaymodes, true)
            ? $config['daymode'] : 'normal';

        $weekdays = $config['ignoreweekdays'] ?? [];
        if (!is_array($weekdays)) {
            $weekdays = preg_split('/\s*,\s*/', (string)$weekdays, -1, PREG_SPLIT_NO_EMPTY);
        }
        $weekdays = array_values(array_unique(array_filter(array_map('intval', $weekdays), function($value) {
            return $value >= 1 && $value <= 7;
        })));
        sort($weekdays);
        $config['ignoreweekdays'] = $weekdays;

        return $config;
    }

    /**
     * Parse custom event class names.
     *
     * @param array $config Effective config.
     * @return array
     */
    public static function custom_event_classes(array $config): array {
        $classes = [];
        foreach (preg_split('/\R/', (string)$config['customevents']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if ($line[0] !== '\\') {
                $line = '\\' . $line;
            }
            if (preg_match('/^\\\\[A-Za-z0-9_\\\\]+$/', $line)) {
                $classes[] = $line;
            }
        }
        return array_values(array_unique($classes));
    }

    /**
     * Admin bool with fallback.
     *
     * @param string $name Setting name.
     * @param bool $default Default.
     * @return int
     */
    private static function bool_config(string $name, bool $default): int {
        $value = get_config('block_personalstreak', $name);
        return $value === false ? ($default ? 1 : 0) : ((bool)$value ? 1 : 0);
    }
}
