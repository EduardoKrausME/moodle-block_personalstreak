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
 * classes/service/integration/rewardshop_adapter.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service\integration;

use ReflectionMethod;
use Throwable;

/**
 * Optional public-API adapter for local_rewardshop credits.
 *
 * No Reward Shop table is queried. The adapter only acts when a public add_credits() API is available.
 *
 * @package block_personalstreak
 */
class rewardshop_adapter {
    /**
     * Add credits using the installed public API without assuming its storage schema.
     *
     * The adapter maps common public API parameter names so the streak plugin remains decoupled from the wallet internals.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $amount Credit amount.
     * @param int $sourceid Stable milestone log id.
     * @param int $milestonedays Milestone days.
     * @return bool
     */
    public static function add_credits(
        int $userid,
        int $courseid,
        int $amount,
        int $sourceid,
        int $milestonedays
    ): bool {
        if ($amount <= 0) {
            return true;
        }
        $class = '\\local_rewardshop\\api';
        if (!class_exists($class) || !method_exists($class, 'add_credits')) {
            return false;
        }

        try {
            $method = new ReflectionMethod($class, 'add_credits');
            $args = [];
            $uniquekey = 'block_personalstreak:milestone:' . $sourceid;
            $label = get_string('milestonerewardlabel', 'block_personalstreak', $milestonedays);

            foreach ($method->getParameters() as $parameter) {
                $name = strtolower($parameter->getName());
                switch ($name) {
                    case 'userid':
                    case 'user_id':
                        $args[] = $userid;
                        break;
                    case 'courseid':
                    case 'course_id':
                        $args[] = $courseid;
                        break;
                    case 'amount':
                    case 'credits':
                    case 'value':
                        $args[] = $amount;
                        break;
                    case 'source':
                    case 'component':
                        $args[] = 'block_personalstreak';
                        break;
                    case 'sourceid':
                    case 'source_id':
                    case 'objectid':
                    case 'object_id':
                        $args[] = $sourceid;
                        break;
                    case 'uniquekey':
                    case 'unique_key':
                    case 'idempotencykey':
                    case 'idempotency_key':
                    case 'reference':
                        $args[] = $uniquekey;
                        break;
                    case 'description':
                    case 'reason':
                    case 'label':
                        $args[] = $label;
                        break;
                    default:
                        if ($parameter->isDefaultValueAvailable()) {
                            $args[] = $parameter->getDefaultValue();
                            break;
                        }
                        return false;
                }
            }

            $result = $method->invokeArgs(null, $args);
            return $result !== false;
        } catch (Throwable $e) {
            debugging('block_personalstreak could not add Reward Shop credits: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}
