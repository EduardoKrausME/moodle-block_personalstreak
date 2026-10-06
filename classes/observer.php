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
 * classes/observer.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak;

use block_personalstreak\service\config_service;
use block_personalstreak\service\day_service;
use core\event\base;

/**
 * Event observer that translates configured Moodle events into study signals.
 *
 * @package block_personalstreak
 */
class observer {
    /**
     * Observe all events so administrators can configure arbitrary event class names.
     *
     * The callback deliberately does not treat login as study and returns before any DB write for irrelevant events.
     *
     * @param base $event Moodle event.
     * @return void
     */
    public static function observe(base $event): void {
        $eventclass = '\\' . ltrim(get_class($event), '\\');
        if (strpos($eventclass, '\\block_personalstreak\\event\\') === 0) {
            return;
        }

        $courseid = self::get_courseid($event);
        if ($courseid <= 0 || $courseid == SITEID) {
            return;
        }

        $config = config_service::get_for_course($courseid);
        $source = self::classify($event, $config);
        if ($source === null) {
            return;
        }

        $userid = self::get_target_userid($event);
        if ($userid <= 0) {
            return;
        }

        $context = \context_course::instance($courseid);
        if (!is_enrolled($context, $userid, '', true)) {
            return;
        }

        day_service::record_activity($userid, $courseid, $source, (int)$event->timecreated);
    }

    /**
     * Resolve course id from standard event data/context.
     *
     * @param base $event Event.
     * @return int
     */
    private static function get_courseid(base $event): int {
        if (!empty($event->courseid)) {
            return (int)$event->courseid;
        }
        $context = $event->get_context();
        if ($context && $context->contextlevel >= CONTEXT_COURSE) {
            $coursecontext = $context->get_course_context(false);
            if ($coursecontext) {
                return (int)$coursecontext->instanceid;
            }
        }
        return 0;
    }

    /**
     * Some completion events target a related user rather than the actor.
     *
     * @param base $event Event.
     * @return int
     */
    private static function get_target_userid(base $event): int {
        if ($event instanceof \core\event\course_module_completion_updated && !empty($event->relateduserid)) {
            return (int)$event->relateduserid;
        }
        return (int)$event->userid;
    }

    /**
     * Return the study source key or null when the event does not count.
     *
     * @param base $event Event.
     * @param array $config Effective course config.
     * @return string|null
     */
    private static function classify(base $event, array $config): ?string {
        $class = '\\' . ltrim(get_class($event), '\\');

        if (!empty($config['trackcourseaccess']) && $event instanceof \core\event\course_viewed) {
            return 'course_access';
        }

        if (!empty($config['trackcompletion']) && $event instanceof \core\event\course_module_completion_updated) {
            $other = (array)$event->other;
            if (!isset($other['completionstate']) || (int)$other['completionstate'] !== COMPLETION_INCOMPLETE) {
                return 'activity_completion';
            }
            return null;
        }

        if (!empty($config['tracksubmission']) && $class === '\\mod_assign\\event\\assessable_submitted') {
            return 'assignment_submission';
        }
        if (!empty($config['trackquiz']) && $class === '\\mod_quiz\\event\\attempt_submitted') {
            return 'quiz_attempt';
        }
        if (!empty($config['trackforum']) && $class === '\\mod_forum\\event\\post_created') {
            return 'forum_post';
        }

        if (!empty($config['trackresourceview']) && $event instanceof \core\event\course_module_viewed) {
            $component = (string)$event->component;
            if (in_array($component, ['mod_resource', 'mod_page', 'mod_book', 'mod_url', 'mod_folder'], true)) {
                return 'resource_view';
            }
        }

        if (!empty($config['trackpersonalxp']) && strpos((string)$event->component, 'local_personalxp') === 0) {
            $other = (array)$event->other;
            if (!empty($other['personalstreakstudy'])) {
                return 'personalxp_activity';
            }
        }

        if (in_array($class, config_service::custom_event_classes($config), true)) {
            return 'custom_event';
        }

        return null;
    }
}
