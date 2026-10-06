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
 * classes/event/personal_record_reached.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\event;

/**
 * New personal record reached.
 *
 * @package block_personalstreak
 */
class personal_record_reached extends base_streak_event {
    /**
     * get_name
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public static function get_name() {
        return get_string('event_personal_record_reached', 'block_personalstreak');
    }

    /**
     * get_description
     *
     * @return string
     */
    public function get_description() {
        $record = isset($this->other['record']) ? (int)$this->other['record'] : 0;
        return "The user with id '{$this->relateduserid}' reached a new personal streak record of " .
            "{$record} days in course '{$this->courseid}'.";
    }
}
