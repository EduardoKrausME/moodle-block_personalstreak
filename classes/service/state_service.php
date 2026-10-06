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
 * classes/service/state_service.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service;

use block_personalstreak\event\personal_record_reached;
use block_personalstreak\event\streak_broken;
use block_personalstreak\event\streak_continued;
use block_personalstreak\event\streak_started;

/**
 * Aggregated personal streak state service.
 *
 * @package block_personalstreak
 */
class state_service {
    /**
     * Recalculate one user/course state from consolidated days only.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int|null $timestamp Reference timestamp, defaults to now.
     * @param bool $emitevents Whether transition events and milestone rewards may be emitted.
     * @return object Persisted state.
     */
    public static function refresh(
        int $userid,
        int $courseid,
        ?int $timestamp = null,
        bool $emitevents = true
    ) {
        global $DB;

        $timestamp = $timestamp ?: time();
        $today = date_helper::daydate_from_timestamp($timestamp, $userid);
        $config = config_service::get_for_course($courseid);
        $active = day_service::get_active_days($userid, $courseid);
        $calculated = streak_calculator::calculate($active, $config, $today);
        $old = $DB->get_record('block_personalstreak_state', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);

        $record = $old ? clone $old : (object)[
            'userid' => $userid,
            'courseid' => $courseid,
            'currentstreak' => 0,
            'beststreak' => 0,
            'lastactiveday' => 0,
            'totalactivedays' => 0,
            'longeststreakstart' => 0,
            'longeststreakend' => 0,
        ];
        $record->currentstreak = (int)$calculated['currentstreak'];
        $record->beststreak = (int)$calculated['beststreak'];
        $record->lastactiveday = (int)$calculated['lastactiveday'];
        $record->totalactivedays = (int)$calculated['totalactivedays'];
        $record->longeststreakstart = (int)$calculated['longeststreakstart'];
        $record->longeststreakend = (int)$calculated['longeststreakend'];
        $record->timemodified = time();

        if ($old) {
            $DB->update_record('block_personalstreak_state', $record);
        } else {
            $record->id = $DB->insert_record('block_personalstreak_state', $record);
        }

        if ($emitevents) {
            self::emit_transitions($old, $record, $calculated, $today);
            reward_service::process(
                $userid,
                $courseid,
                (int)$record->currentstreak,
                (int)$record->id,
                $today,
                $config
            );
        }

        return $record;
    }

    /**
     * Return public API state, refreshing lazily once per user-local day.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return array
     */
    public static function get_public_state(int $userid, int $courseid): array {
        global $DB;

        $now = time();
        $today = date_helper::daydate_from_timestamp($now, $userid);
        $state = $DB->get_record('block_personalstreak_state', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);

        $needrefresh = !$state || empty($state->timemodified);
        if (!$needrefresh) {
            $lastcalculatedday = date_helper::daydate_from_timestamp((int)$state->timemodified, $userid);
            $needrefresh = $lastcalculatedday !== $today;
        }
        if ($needrefresh) {
            $state = self::refresh($userid, $courseid, $now, true);
        }

        $config = config_service::get_for_course($courseid);
        $next = reward_service::next_milestone((int)$state->currentstreak, $config);
        $fromday = date_helper::add_days($today, -29);

        return [
            'currentstreak' => (int)$state->currentstreak,
            'beststreak' => (int)$state->beststreak,
            'active_today' => day_service::is_active_day($userid, $courseid, $today),
            'active_last_30_days' => day_service::count_active_days($userid, $courseid, $fromday, $today),
            'next_milestone' => $next,
            'days_until_next_milestone' => $next === null ? null : max(0, $next - (int)$state->currentstreak),
        ];
    }

    /**
     * Build a compact activity calendar without exposing anyone else's data.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $numberofdays Number of calendar cells.
     * @return array
     */
    public static function get_calendar_data(int $userid, int $courseid, int $numberofdays = 30): array {
        $numberofdays = max(1, min(90, $numberofdays));
        $today = date_helper::daydate_from_timestamp(time(), $userid);
        $start = date_helper::add_days($today, -($numberofdays - 1));
        $config = config_service::get_for_course($courseid);
        $active = array_fill_keys(day_service::get_active_days($userid, $courseid), true);
        $timezone = date_helper::user_timezone($userid);
        $cells = [];

        for ($day = $start; $day <= $today; $day = date_helper::add_days($day, 1)) {
            $isactive = isset($active[$day]);
            $isignored = streak_calculator::is_ignored_day($day, $config);
            $istoday = $day === $today;
            $date = date_helper::date_from_daydate($day, $timezone);
            $label = userdate($date->getTimestamp(), get_string('strftimedateshort', 'langconfig'), $timezone->getName());
            $status = $isactive ? get_string('active', 'block_personalstreak')
                : ($isignored ? get_string('ignored', 'block_personalstreak') : get_string('inactive', 'block_personalstreak'));
            if ($istoday) {
                $status .= ' · ' . get_string('today', 'block_personalstreak');
            }

            $classes = ['streak-day'];
            $classes[] = $isactive ? 'streak-day--active' : ($isignored ? 'streak-day--ignored' : 'streak-day--inactive');
            if ($istoday) {
                $classes[] = 'streak-day--today';
            }

            $cells[] = [
                'daydate' => $day,
                'day' => (int)$date->format('j'),
                'class' => implode(' ', $classes),
                'detail' => $label . ' — ' . $status,
                'active' => $isactive,
                'ignored' => $isignored,
                'today' => $istoday,
            ];
        }
        return $cells;
    }

    /**
     * Emit semantic transitions once.
     *
     * @param object|false $old Previous state.
     * @param object $new New state.
     * @param array $calculated Calculator details.
     * @param int $today User-local day.
     * @return void
     */
    private static function emit_transitions($old, $new, array $calculated, int $today): void {
        $context = \context_course::instance((int)$new->courseid);
        $oldcurrent = $old ? (int)$old->currentstreak : 0;
        $oldbest = $old ? (int)$old->beststreak : 0;
        $oldlast = $old ? (int)$old->lastactiveday : 0;
        $newcurrent = (int)$new->currentstreak;
        $newlast = (int)$new->lastactiveday;
        $newactive = $newlast > 0 && $newlast !== $oldlast;
        $currentstart = isset($calculated['currentstreakstart']) ? (int)$calculated['currentstreakstart'] : 0;
        $brokeandstarted = $oldcurrent > 0 && $newactive && $currentstart > $oldlast;

        if ($old && $oldcurrent > 0 && ($newcurrent === 0 || $brokeandstarted)) {
            self::trigger_once(
                (int)$new->userid,
                (int)$new->courseid,
                'broken:' . $today . ':' . $oldlast,
                $today,
                streak_broken::class,
                (int)$new->id,
                ['previousstreak' => $oldcurrent, 'daydate' => $today],
                $context
            );
        }

        if ($newcurrent > 0 && (!$old || $oldcurrent === 0 || $brokeandstarted)) {
            self::trigger_once(
                (int)$new->userid,
                (int)$new->courseid,
                'started:' . $newlast,
                $newlast ?: $today,
                streak_started::class,
                (int)$new->id,
                ['streak' => $newcurrent, 'daydate' => $newlast ?: $today],
                $context
            );
        } else if ($newactive && $newcurrent > $oldcurrent && $oldcurrent > 0) {
            self::trigger_once(
                (int)$new->userid,
                (int)$new->courseid,
                'continued:' . $newlast,
                $newlast,
                streak_continued::class,
                (int)$new->id,
                ['streak' => $newcurrent, 'daydate' => $newlast],
                $context
            );
        }

        if ($old && $oldbest > 0 && (int)$new->beststreak > $oldbest) {
            self::trigger_once(
                (int)$new->userid,
                (int)$new->courseid,
                'record:' . (int)$new->beststreak,
                $newlast ?: $today,
                personal_record_reached::class,
                (int)$new->id,
                ['record' => (int)$new->beststreak, 'daydate' => $newlast ?: $today],
                $context
            );
        }
    }

    /**
     * Trigger a Moodle event only after a durable idempotency claim.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param string $key Event key.
     * @param int $daydate User-local day.
     * @param string $eventclass Event class.
     * @param int $stateid State record id.
     * @param array $other Event other data.
     * @param \context_course $context Course context.
     * @return void
     */
    private static function trigger_once(
        int $userid,
        int $courseid,
        string $key,
        int $daydate,
        string $eventclass,
        int $stateid,
        array $other,
        \context_course $context
    ): void {
        if (!event_guard::claim($userid, $courseid, $key, $daydate)) {
            return;
        }
        $event = $eventclass::create([
            'context' => $context,
            'objectid' => $stateid,
            'relateduserid' => $userid,
            'other' => $other,
        ]);
        $event->trigger();
    }
}
