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
 * tests/state_service_test.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak;

use block_personalstreak\event\personal_record_reached;
use block_personalstreak\event\streak_milestone_reached;
use block_personalstreak\service\config_service;
use block_personalstreak\service\day_service;

/**
 * State transition tests.
 *
 * @package block_personalstreak
 * @covers \block_personalstreak\service\state_service
 */
final class state_service_test extends \advanced_testcase {
    /**
     * A new best streak emits one personal-record event.
     */
    public function test_personal_record_reached(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['timezone' => 'UTC']);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        $day1 = gmmktime(10, 0, 0, 10, 1, 2026);
        $day2 = gmmktime(10, 0, 0, 10, 2, 2026);
        day_service::record_activity($user->id, $course->id, 'course_access', $day1);

        $sink = $this->redirectEvents();
        day_service::record_activity($user->id, $course->id, 'quiz_attempt', $day2);
        $records = array_values(array_filter($sink->get_events(), function($event) {
            return $event instanceof personal_record_reached;
        }));
        $sink->close();

        $this->assertCount(1, $records);
        $this->assertSame(2, (int)$records[0]->other['record']);
    }

    /**
     * Configured milestone event is emitted once when the threshold is crossed.
     */
    public function test_milestone_reached(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['timezone' => 'UTC']);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        config_service::save_for_course($course->id, (object)[
            'milestones' => '2|0|0|0|1|1',
        ]);

        $day1 = gmmktime(10, 0, 0, 10, 1, 2026);
        $day2 = gmmktime(10, 0, 0, 10, 2, 2026);
        day_service::record_activity($user->id, $course->id, 'course_access', $day1);

        $sink = $this->redirectEvents();
        day_service::record_activity($user->id, $course->id, 'quiz_attempt', $day2);
        // Reprocessing the same day must not duplicate the milestone event.
        day_service::record_activity($user->id, $course->id, 'forum_post', $day2 + 60);
        $events = array_values(array_filter($sink->get_events(), function($event) {
            return $event instanceof streak_milestone_reached;
        }));
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertSame(2, (int)$events[0]->other['milestone']);
    }
}
