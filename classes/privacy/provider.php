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
 * classes/privacy/provider.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for block_personalstreak.
 *
 * @package block_personalstreak
 */
class provider implements
    \core_privacy\local\metadata\provider,
    plugin_provider,
    core_userlist_provider {

    /**
     * Describe personal data tables.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_personalstreak_days', [
            'userid' => 'privacy:metadata:days:userid',
            'courseid' => 'privacy:metadata:days:courseid',
            'daydate' => 'privacy:metadata:days:daydate',
            'activitycount' => 'privacy:metadata:days:activitycount',
            'firstactivity' => 'privacy:metadata:days:firstactivity',
            'lastactivity' => 'privacy:metadata:days:lastactivity',
            'source' => 'privacy:metadata:days:source',
        ], 'privacy:metadata:days');

        $collection->add_database_table('block_personalstreak_state', [
            'userid' => 'privacy:metadata:days:userid',
            'courseid' => 'privacy:metadata:days:courseid',
            'currentstreak' => 'privacy:metadata:state:currentstreak',
            'beststreak' => 'privacy:metadata:state:beststreak',
            'lastactiveday' => 'privacy:metadata:state:lastactiveday',
            'totalactivedays' => 'privacy:metadata:state:totalactivedays',
            'longeststreakstart' => 'privacy:metadata:state:longeststreakstart',
            'longeststreakend' => 'privacy:metadata:state:longeststreakend',
        ], 'privacy:metadata:state');

        $collection->add_database_table('block_personalstreak_mstone', [
            'userid' => 'privacy:metadata:days:userid',
            'courseid' => 'privacy:metadata:days:courseid',
            'milestonedays' => 'privacy:metadata:mstone:milestonedays',
            'xprewarded' => 'privacy:metadata:mstone:xprewarded',
            'creditsrewarded' => 'privacy:metadata:mstone:creditsrewarded',
            'badgeawarded' => 'privacy:metadata:mstone:badgeawarded',
            'eventfired' => 'privacy:metadata:mstone:eventfired',
        ], 'privacy:metadata:mstone');

        $collection->add_database_table('block_personalstreak_events', [
            'userid' => 'privacy:metadata:days:userid',
            'courseid' => 'privacy:metadata:days:courseid',
            'eventkey' => 'privacy:metadata:events:eventkey',
            'daydate' => 'privacy:metadata:events:daydate',
            'uniquehash' => 'privacy:metadata:events:uniquehash',
        ], 'privacy:metadata:events');

        return $collection;
    }

    /**
     * Find course contexts containing this user's streak data.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN (
                        SELECT courseid FROM {block_personalstreak_days} WHERE userid = :u1
                        UNION
                        SELECT courseid FROM {block_personalstreak_state} WHERE userid = :u2
                        UNION
                        SELECT courseid FROM {block_personalstreak_mstone} WHERE userid = :u3
                        UNION
                        SELECT courseid FROM {block_personalstreak_events} WHERE userid = :u4
                  ) d ON d.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel";
        $contextlist->add_from_sql($sql, [
            'u1' => $userid,
            'u2' => $userid,
            'u3' => $userid,
            'u4' => $userid,
            'contextlevel' => CONTEXT_COURSE,
        ]);
        return $contextlist;
    }

    /**
     * Export data by course context.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $courseid = $context->instanceid;
            $days = $DB->get_records('block_personalstreak_days', [
                'userid' => $userid,
                'courseid' => $courseid,
            ], 'daydate ASC');
            $state = $DB->get_record('block_personalstreak_state', [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);
            $milestones = $DB->get_records('block_personalstreak_mstone', [
                'userid' => $userid,
                'courseid' => $courseid,
            ], 'milestonedays ASC');
            $events = $DB->get_records('block_personalstreak_events', [
                'userid' => $userid,
                'courseid' => $courseid,
            ], 'timecreated ASC');

            $stateexport = null;
            if ($state) {
                $stateexport = (object)[
                    'currentstreak' => (int)$state->currentstreak,
                    'beststreak' => (int)$state->beststreak,
                    'lastactiveday' => (int)$state->lastactiveday,
                    'totalactivedays' => (int)$state->totalactivedays,
                    'longeststreakstart' => (int)$state->longeststreakstart,
                    'longeststreakend' => (int)$state->longeststreakend,
                    'timemodified' => transform::datetime($state->timemodified),
                ];
            }

            $export = (object)[
                'days' => array_values(array_map(function($record) {
                    return (object)[
                        'daydate' => (int)$record->daydate,
                        'activitycount' => (int)$record->activitycount,
                        'firstactivity' => transform::datetime($record->firstactivity),
                        'lastactivity' => transform::datetime($record->lastactivity),
                        'source' => $record->source,
                    ];
                }, $days)),
                'state' => $stateexport,
                'milestones' => array_values(array_map(function($record) {
                    return (object)[
                        'milestonedays' => (int)$record->milestonedays,
                        'xprewarded' => (bool)$record->xprewarded,
                        'creditsrewarded' => (bool)$record->creditsrewarded,
                        'badgeawarded' => (bool)$record->badgeawarded,
                        'eventfired' => (bool)$record->eventfired,
                        'timecreated' => transform::datetime($record->timecreated),
                        'timemodified' => transform::datetime($record->timemodified),
                    ];
                }, $milestones)),
                'events' => array_values(array_map(function($record) {
                    return (object)[
                        'eventkey' => $record->eventkey,
                        'daydate' => (int)$record->daydate,
                        'uniquehash' => $record->uniquehash,
                        'timecreated' => transform::datetime($record->timecreated),
                    ];
                }, $events)),
            ];
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'block_personalstreak')],
                $export
            );
        }
    }

    /**
     * Delete all users' streak data in one course context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        foreach (self::user_tables() as $table) {
            $DB->delete_records($table, ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Delete one user's data in approved course contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            foreach (self::user_tables() as $table) {
                $DB->delete_records($table, [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ]);
            }
        }
    }

    /**
     * Add affected users to a context user list.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $courseid = $userlist->get_context()->instanceid;
        foreach (self::user_tables() as $table) {
            $userlist->add_from_sql('userid', "SELECT userid FROM {{$table}} WHERE courseid = :courseid", [
                'courseid' => $courseid,
            ]);
        }
    }

    /**
     * Delete approved users' data in a context.
     *
     * @param approved_userlist $userlist Approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $context->instanceid;
        foreach (self::user_tables() as $table) {
            $DB->delete_records_select($table, "courseid = :courseid AND userid {$insql}", $params);
        }
    }

    /**
     * User-owned tables.
     *
     * @return array
     */
    private static function user_tables(): array {
        return [
            'block_personalstreak_days',
            'block_personalstreak_state',
            'block_personalstreak_mstone',
            'block_personalstreak_events',
        ];
    }
}
