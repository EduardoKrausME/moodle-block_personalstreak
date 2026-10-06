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
 * tests/day_service_test.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak;

use block_personalstreak\service\day_service;

/**
 * Daily consolidation tests.
 *
 * @package block_personalstreak
 * @covers \block_personalstreak\service\day_service
 */
final class day_service_test extends \advanced_testcase {
    /**
     * User timezone decides the stored day, not server UTC.
     */
    public function test_user_timezone_daydate(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['timezone' => 'Pacific/Kiritimati']);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        // 2026-10-02 12:30 UTC is already 2026-10-03 in UTC+14.
        $timestamp = gmmktime(12, 30, 0, 10, 2, 2026);
        $this->assertTrue(day_service::record_activity($user->id, $course->id, 'quiz_attempt', $timestamp));

        $record = $DB->get_record('block_personalstreak_days', [
            'userid' => $user->id,
            'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $this->assertSame(20261003, (int)$record->daydate);
    }

    /**
     * Multiple events on one day are consolidated instead of creating duplicate day rows.
     */
    public function test_multiple_events_same_day(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['timezone' => 'UTC']);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
        $timestamp = gmmktime(10, 0, 0, 10, 3, 2026);

        day_service::record_activity($user->id, $course->id, 'quiz_attempt', $timestamp);
        day_service::record_activity($user->id, $course->id, 'forum_post', $timestamp + 60);

        $this->assertSame(1, $DB->count_records('block_personalstreak_days', [
            'userid' => $user->id,
            'courseid' => $course->id,
        ]));
        $record = $DB->get_record('block_personalstreak_days', [
            'userid' => $user->id,
            'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $this->assertSame(2, (int)$record->activitycount);
        $this->assertStringContainsString('quiz_attempt', $record->source);
        $this->assertStringContainsString('forum_post', $record->source);
    }
}
