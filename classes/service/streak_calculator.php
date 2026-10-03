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
 * classes/service/streak_calculator.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service;

/**
 * Pure calendar-based streak calculator.
 *
 * @package block_personalstreak
 */
class streak_calculator {
    /**
     * Calculate aggregate state from consolidated active days.
     *
     * Ignored weekdays neither increment nor break a streak. Protected missed days do not increment the streak;
     * they only preserve the streak until the next qualifying active day.
     *
     * @param array $activedays YYYYMMDD values.
     * @param array $config Effective config.
     * @param int $today User-local YYYYMMDD.
     * @return array
     */
    public static function calculate(array $activedays, array $config, int $today): array {
        $active = [];
        foreach ($activedays as $day) {
            $day = (int)$day;
            if ($day > 0 && $day <= $today) {
                $active[$day] = true;
            }
        }
        ksort($active, SORT_NUMERIC);

        $allactivedays = array_keys($active);
        $result = [
            'currentstreak' => 0,
            'beststreak' => 0,
            'lastactiveday' => empty($allactivedays) ? 0 : (int)end($allactivedays),
            'totalactivedays' => count($allactivedays),
            'longeststreakstart' => 0,
            'longeststreakend' => 0,
            'protecteddaydates' => [],
            'currentstreakstart' => 0,
            'currentstreakend' => 0,
        ];

        $countedactive = array_values(array_filter($allactivedays, function($day) use ($config) {
            return !self::is_ignored_day((int)$day, $config);
        }));
        if (empty($countedactive)) {
            return $result;
        }

        $first = (int)reset($countedactive);
        $current = 0;
        $best = 0;
        $currentstart = 0;
        $currentlastactive = 0;
        $beststart = 0;
        $bestend = 0;
        $oneprotectionused = false;
        $rollingprotected = [];
        $protectedall = [];

        for ($day = $first; $day <= $today; $day = date_helper::add_days($day, 1)) {
            if (self::is_ignored_day($day, $config)) {
                continue;
            }

            if (isset($active[$day])) {
                if ($current === 0) {
                    $current = 1;
                    $currentstart = $day;
                    $oneprotectionused = false;
                } else {
                    $current++;
                }
                $currentlastactive = $day;

                if ($current > $best) {
                    $best = $current;
                    $beststart = $currentstart;
                    $bestend = $currentlastactive;
                }
                continue;
            }

            // The current day is still in progress. Absence only becomes a missed day after midnight.
            if ($day === $today) {
                continue;
            }

            if ($current === 0) {
                continue;
            }

            if (self::can_protect($day, $config, $oneprotectionused, $rollingprotected)) {
                $protectedall[] = $day;
                if ($config['protectionmode'] === 'one') {
                    $oneprotectionused = true;
                } else if ($config['protectionmode'] === 'rolling') {
                    $rollingprotected[] = $day;
                }
                continue;
            }

            $current = 0;
            $currentstart = 0;
            $currentlastactive = 0;
            $oneprotectionused = false;
        }

        $result['currentstreak'] = $current;
        $result['beststreak'] = $best;
        $result['longeststreakstart'] = $beststart;
        $result['longeststreakend'] = $bestend;
        $result['protecteddaydates'] = $protectedall;
        $result['currentstreakstart'] = $current > 0 ? $currentstart : 0;
        $result['currentstreakend'] = $current > 0 ? $currentlastactive : 0;
        return $result;
    }

    /**
     * Whether a weekday is ignored by the configured schedule.
     *
     * @param int $daydate YYYYMMDD.
     * @param array $config Config.
     * @return bool
     */
    public static function is_ignored_day(int $daydate, array $config): bool {
        $mode = $config['daymode'] ?? 'normal';
        $weekday = date_helper::iso_weekday($daydate);
        if ($mode === 'weekends') {
            return $weekday >= 6;
        }
        if ($mode === 'custom') {
            $ignored = $config['ignoreweekdays'] ?? [];
            if (!is_array($ignored)) {
                $ignored = preg_split('/\s*,\s*/', (string)$ignored, -1, PREG_SPLIT_NO_EMPTY);
            }
            return in_array($weekday, array_map('intval', $ignored), true);
        }
        return false;
    }

    /**
     * Decide whether a missed required day can be protected.
     *
     * @param int $daydate Missed day.
     * @param array $config Config.
     * @param bool $oneused Whether one-day protection was already used in this streak.
     * @param array $rollingprotected Previously protected days.
     * @return bool
     */
    private static function can_protect(int $daydate, array $config, bool $oneused, array &$rollingprotected): bool {
        $mode = $config['protectionmode'] ?? 'none';
        if ($mode === 'none') {
            return false;
        }
        if ($mode === 'one') {
            return !$oneused;
        }
        if ($mode !== 'rolling') {
            return false;
        }

        $allowed = max(0, (int)($config['protectiondays'] ?? 0));
        $period = max(1, (int)($config['protectionperiod'] ?? 30));
        if ($allowed === 0) {
            return false;
        }

        $rollingprotected = array_values(array_filter($rollingprotected, function($protected) use ($daydate, $period) {
            $distance = date_helper::diff_days((int)$protected, $daydate);
            return $distance >= 0 && $distance < $period;
        }));
        return count($rollingprotected) < $allowed;
    }
}
