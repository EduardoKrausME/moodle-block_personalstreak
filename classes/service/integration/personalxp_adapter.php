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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * classes/service/integration/personalxp_adapter.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service\integration;

/**
 * Public-API adapter for local_personalxp.
 *
 * @package block_personalstreak
 */
class personalxp_adapter {
    /**
     * Award XP for a milestone through local_personalxp public service only.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $milestonedays Milestone.
     * @param int $amount XP amount.
     * @param int $sourceid Stable milestone log id.
     * @return bool
     */
    public static function award_milestone(
        int $userid,
        int $courseid,
        int $milestonedays,
        int $amount,
        int $sourceid
    ): bool {
        if ($amount <= 0 || !class_exists('\\local_personalxp\\service\\xp_manager')) {
            return $amount <= 0;
        }

        return \local_personalxp\service\xp_manager::award(
            $userid,
            $courseid,
            'personalstreak_milestone_' . $milestonedays,
            $sourceid,
            $amount,
            get_string('milestonerewardlabel', 'block_personalstreak', $milestonedays),
            'block_personalstreak',
            '\\block_personalstreak\\event\\streak_milestone_reached'
        );
    }
}
