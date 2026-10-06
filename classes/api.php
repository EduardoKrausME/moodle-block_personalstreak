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
 * classes/api.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak;

use block_personalstreak\service\config_service;
use block_personalstreak\service\day_service;
use block_personalstreak\service\state_service;

/**
 * Public integration API for block_personalstreak.
 *
 * @package block_personalstreak
 */
class api {
    /**
     * Return a user's personal streak state in one course.
     *
     * Interactive requests may only request the current user's state. Background/CLI code can use the API for
     * system integrations. The block itself never provides an interface for viewing other learners' streaks.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return array{currentstreak:int,beststreak:int,active_today:bool,active_last_30_days:int,next_milestone:?int,days_until_next_milestone:?int}
     */
    public static function get_user_state(int $userid, int $courseid): array {
        global $USER;

        self::validate_subject($userid, $courseid);
        if (!self::is_background_request() && isloggedin() && !isguestuser() && (int)$USER->id !== $userid) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('pluginname', 'block_personalstreak'));
        }
        return state_service::get_public_state($userid, $courseid);
    }

    /**
     * Public integration point for local_personalxp or another trusted Moodle plugin to register study activity.
     *
     * This does not add XP. It only consolidates the supplied study signal into the streak day table.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param string $source Stable source identifier.
     * @param int|null $timestamp Source timestamp.
     * @return bool
     */
    public static function record_activity(
        int $userid,
        int $courseid,
        string $source = 'integration',
        ?int $timestamp = null
    ): bool {
        self::validate_subject($userid, $courseid);
        $config = config_service::get_for_course($courseid);
        if ($source === 'personalxp_activity' && empty($config['trackpersonalxp'])) {
            return false;
        }
        return day_service::record_activity($userid, $courseid, $source, $timestamp);
    }

    /**
     * Validate core records and enrollment.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return void
     */
    private static function validate_subject(int $userid, int $courseid): void {
        global $DB;

        if ($userid <= 0 || $courseid <= 0 || $courseid == SITEID) {
            throw new \invalid_parameter_exception('Invalid user or course id.');
        }
        $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id', MUST_EXIST);
        $DB->get_record('course', ['id' => $courseid], 'id', MUST_EXIST);
        $context = \context_course::instance($courseid);
        if (!is_enrolled($context, $userid, '', true)) {
            throw new \moodle_exception('notenrolled', 'error');
        }
    }

    /**
     * Whether this execution is a background/test context without an interactive user boundary.
     *
     * @return bool
     */
    private static function is_background_request(): bool {
        return (defined('CLI_SCRIPT') && CLI_SCRIPT) || (defined('PHPUNIT_TEST') && PHPUNIT_TEST);
    }
}
