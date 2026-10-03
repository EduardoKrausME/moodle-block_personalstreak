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
 * classes/service/event_guard.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service;

/**
 * Cross-database event idempotency guard.
 *
 * @package block_personalstreak
 */
class event_guard {
    /**
     * Claim an event key once.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param string $eventkey Stable semantic event key.
     * @param int $daydate User-local day.
     * @return bool True only for the first claimant.
     */
    public static function claim(int $userid, int $courseid, string $eventkey, int $daydate): bool {
        global $DB;

        $eventkey = substr(clean_param($eventkey, PARAM_TEXT), 0, 100);
        $hash = hash('sha256', implode('|', [$userid, $courseid, $eventkey, $daydate]));
        if ($DB->record_exists('block_personalstreak_events', ['uniquehash' => $hash])) {
            return false;
        }

        try {
            $DB->insert_record('block_personalstreak_events', (object)[
                'userid' => $userid,
                'courseid' => $courseid,
                'eventkey' => $eventkey,
                'daydate' => $daydate,
                'uniquehash' => $hash,
                'timecreated' => time(),
            ]);
            return true;
        } catch (\dml_write_exception $e) {
            // A concurrent request claimed the same unique hash first.
            return false;
        }
    }
}
