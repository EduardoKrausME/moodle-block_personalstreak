<?php
// This file is part of Moodle - https://moodle.org/
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
 * classes/service/day_service.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service;

/**
 * Consolidates qualifying Moodle events into user-local study days.
 *
 * @package block_personalstreak
 */
class day_service {
    /**
     * Record one qualifying study signal.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param string $source Stable source key.
     * @param int|null $timestamp Event time, defaults to now.
     * @return bool True when the daily record was updated.
     */
    public static function record_activity(int $userid, int $courseid, string $source, ?int $timestamp = null): bool {
        global $DB;

        if ($userid <= 0 || $courseid <= 0 || $courseid == SITEID) {
            return false;
        }
        $timestamp = $timestamp ?: time();
        $source = clean_param($source, PARAM_ALPHANUMEXT);
        if ($source === '') {
            return false;
        }

        $daydate = date_helper::daydate_from_timestamp($timestamp, $userid);
        $factory = \core\lock\lock_config::get_lock_factory('block_personalstreak');
        $lock = $factory->get_lock('day:' . $userid . ':' . $courseid . ':' . $daydate, 5);
        if (!$lock) {
            return false;
        }

        $newday = false;
        try {
            $transaction = $DB->start_delegated_transaction();
            $record = $DB->get_record('block_personalstreak_days', [
                'userid' => $userid,
                'courseid' => $courseid,
                'daydate' => $daydate,
            ]);

            if (!$record) {
                $newday = true;
                $now = time();
                $record = (object)[
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'daydate' => $daydate,
                    'activitycount' => 1,
                    'firstactivity' => $timestamp,
                    'lastactivity' => $timestamp,
                    'source' => $source,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $DB->insert_record('block_personalstreak_days', $record);
            } else {
                $sources = array_filter(array_map('trim', explode(',', (string)$record->source)));
                if (!in_array($source, $sources, true)) {
                    $sources[] = $source;
                }
                $record->activitycount = (int)$record->activitycount + 1;
                $record->firstactivity = min((int)$record->firstactivity, $timestamp);
                $record->lastactivity = max((int)$record->lastactivity, $timestamp);
                $record->source = substr(implode(',', $sources), 0, 255);
                $record->timemodified = time();
                $DB->update_record('block_personalstreak_days', $record);
            }
            $transaction->allow_commit();
        } finally {
            $lock->release();
        }

        if ($newday) {
            state_service::refresh($userid, $courseid, $timestamp, true);
        }
        return true;
    }

    /**
     * Whether the user has any qualifying activity on a user-local day.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $daydate YYYYMMDD.
     * @return bool
     */
    public static function is_active_day(int $userid, int $courseid, int $daydate): bool {
        global $DB;
        return $DB->record_exists('block_personalstreak_days', [
            'userid' => $userid,
            'courseid' => $courseid,
            'daydate' => $daydate,
        ]);
    }

    /**
     * Return active day identifiers for a user and course.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return array
     */
    public static function get_active_days(int $userid, int $courseid): array {
        global $DB;
        return array_map('intval', $DB->get_fieldset_select(
            'block_personalstreak_days',
            'daydate',
            'userid = :userid AND courseid = :courseid',
            ['userid' => $userid, 'courseid' => $courseid],
            'daydate ASC'
        ));
    }

    /**
     * Count distinct active days in a calendar range.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $fromday Inclusive YYYYMMDD.
     * @param int $today Inclusive YYYYMMDD.
     * @return int
     */
    public static function count_active_days(int $userid, int $courseid, int $fromday, int $today): int {
        global $DB;
        return $DB->count_records_select(
            'block_personalstreak_days',
            'userid = :userid AND courseid = :courseid AND daydate >= :fromday AND daydate <= :today',
            [
                'userid' => $userid,
                'courseid' => $courseid,
                'fromday' => $fromday,
                'today' => $today,
            ]
        );
    }
}
