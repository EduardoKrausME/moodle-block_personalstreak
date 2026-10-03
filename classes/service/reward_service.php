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
 * classes/service/reward_service.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service;

use block_personalstreak\event\streak_milestone_reached;
use block_personalstreak\service\integration\personalxp_adapter;
use block_personalstreak\service\integration\rewardshop_adapter;
use Throwable;

/**
 * Milestone parser and idempotent reward delivery.
 *
 * @package block_personalstreak
 */
class reward_service {
    /**
     * Parse milestone lines.
     *
     * Format: days|xp|credits|badgeid|event|visual
     *
     * @param string $raw Configuration.
     * @return array
     */
    public static function parse_milestones(string $raw): array {
        $milestones = [];
        foreach (preg_split('/\R/', $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            $days = isset($parts[0]) ? (int)$parts[0] : 0;
            if ($days <= 0) {
                continue;
            }
            $milestones[$days] = [
                'days' => $days,
                'xp' => max(0, isset($parts[1]) ? (int)$parts[1] : 0),
                'credits' => max(0, isset($parts[2]) ? (int)$parts[2] : 0),
                'badgeid' => max(0, isset($parts[3]) ? (int)$parts[3] : 0),
                'event' => !empty($parts[4]),
                'visual' => !isset($parts[5]) || !empty($parts[5]),
            ];
        }
        ksort($milestones, SORT_NUMERIC);
        return array_values($milestones);
    }

    /**
     * Return the next visible configured milestone above the current streak.
     *
     * @param int $currentstreak Current streak.
     * @param array $config Effective config.
     * @return int|null
     */
    public static function next_milestone(int $currentstreak, array $config): ?int {
        foreach (self::parse_milestones((string)$config['milestones']) as $milestone) {
            if ($milestone['visual'] && $milestone['days'] > $currentstreak) {
                return (int)$milestone['days'];
            }
        }
        return null;
    }

    /**
     * Process every configured milestone currently reached, once per user/course/milestone.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $currentstreak Current streak.
     * @param int $stateid State object id used by Moodle events.
     * @param int $daydate Current local day.
     * @param array $config Effective config.
     * @return void
     */
    public static function process(
        int $userid,
        int $courseid,
        int $currentstreak,
        int $stateid,
        int $daydate,
        array $config
    ): void {
        global $DB;

        foreach (self::parse_milestones((string)$config['milestones']) as $milestone) {
            if ($currentstreak < $milestone['days']) {
                continue;
            }

            $factory = \core\lock\lock_config::get_lock_factory('block_personalstreak');
            $lock = $factory->get_lock(
                'milestone:' . $userid . ':' . $courseid . ':' . $milestone['days'],
                5
            );
            if (!$lock) {
                continue;
            }

            try {
                $record = $DB->get_record('block_personalstreak_mstone', [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'milestonedays' => $milestone['days'],
                ]);
                if (!$record) {
                    $now = time();
                    $record = (object)[
                        'userid' => $userid,
                        'courseid' => $courseid,
                        'milestonedays' => $milestone['days'],
                        'xprewarded' => 0,
                        'creditsrewarded' => 0,
                        'badgeawarded' => 0,
                        'eventfired' => 0,
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ];
                    $record->id = $DB->insert_record('block_personalstreak_mstone', $record);
                }

                $changed = false;
                if ($milestone['xp'] <= 0) {
                    if (!(int)$record->xprewarded) {
                        $record->xprewarded = 1;
                        $changed = true;
                    }
                } else if (!(int)$record->xprewarded && personalxp_adapter::award_milestone(
                    $userid,
                    $courseid,
                    (int)$milestone['days'],
                    (int)$milestone['xp'],
                    (int)$record->id
                )) {
                    $record->xprewarded = 1;
                    $changed = true;
                }

                if ($milestone['credits'] <= 0) {
                    if (!(int)$record->creditsrewarded) {
                        $record->creditsrewarded = 1;
                        $changed = true;
                    }
                } else if (!(int)$record->creditsrewarded && rewardshop_adapter::add_credits(
                    $userid,
                    $courseid,
                    (int)$milestone['credits'],
                    (int)$record->id,
                    (int)$milestone['days']
                )) {
                    $record->creditsrewarded = 1;
                    $changed = true;
                }

                if ($milestone['badgeid'] <= 0) {
                    if (!(int)$record->badgeawarded) {
                        $record->badgeawarded = 1;
                        $changed = true;
                    }
                } else if (!(int)$record->badgeawarded && self::award_badge(
                    $userid,
                    $courseid,
                    (int)$milestone['badgeid']
                )) {
                    $record->badgeawarded = 1;
                    $changed = true;
                }

                if (!$milestone['event']) {
                    if (!(int)$record->eventfired) {
                        $record->eventfired = 1;
                        $changed = true;
                    }
                } else if (!(int)$record->eventfired) {
                    $claimed = event_guard::claim(
                        $userid,
                        $courseid,
                        'milestone:' . (int)$milestone['days'],
                        0
                    );
                    if ($claimed) {
                        $event = streak_milestone_reached::create([
                            'context' => \context_course::instance($courseid),
                            'objectid' => $stateid,
                            'relateduserid' => $userid,
                            'other' => [
                                'milestone' => (int)$milestone['days'],
                                'xp' => (int)$milestone['xp'],
                                'credits' => (int)$milestone['credits'],
                                'badgeid' => (int)$milestone['badgeid'],
                                'daydate' => $daydate,
                            ],
                        ]);
                        $event->trigger();
                    }
                    // Claimed=false means another request already claimed/fired it. Either way, do not retry forever.
                    $record->eventfired = 1;
                    $changed = true;
                }

                if ($changed) {
                    $record->timemodified = time();
                    $DB->update_record('block_personalstreak_mstone', $record);
                }
            } finally {
                $lock->release();
            }
        }
    }

    /**
     * Award an active site/course badge through the core badges API.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $badgeid Badge id.
     * @return bool
     */
    private static function award_badge(int $userid, int $courseid, int $badgeid): bool {
        try {
            $badge = new \core_badges\badge($badgeid);
            if (!$badge->is_active()) {
                return false;
            }
            if (!empty($badge->courseid) && (int)$badge->courseid !== $courseid) {
                return false;
            }
            if ($badge->is_issued($userid)) {
                return true;
            }
            $badge->issue($userid);
            return $badge->is_issued($userid);
        } catch (Throwable $e) {
            debugging('block_personalstreak could not award badge: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}
