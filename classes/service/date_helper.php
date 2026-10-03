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
 * classes/service/date_helper.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak\service;

use DateTimeImmutable;
use DateTimeZone;

/**
 * User-local calendar helpers.
 *
 * Day values are stored as YYYYMMDD integers. They are calendar identifiers, not timestamps.
 *
 * @package block_personalstreak
 */
class date_helper {
    /**
     * Resolve a user's Moodle timezone.
     *
     * @param int $userid User id.
     * @return DateTimeZone
     */
    public static function user_timezone(int $userid): DateTimeZone {
        $user = \core_user::get_user($userid, 'id,timezone', MUST_EXIST);
        return \core_date::get_user_timezone_object($user);
    }

    /**
     * Convert a timestamp to a calendar day in the user's timezone.
     *
     * @param int $timestamp Unix timestamp.
     * @param int $userid User id.
     * @return int YYYYMMDD.
     */
    public static function daydate_from_timestamp(int $timestamp, int $userid): int {
        return self::daydate_for_timezone($timestamp, self::user_timezone($userid));
    }

    /**
     * Convert a timestamp to a calendar day in an explicit timezone.
     *
     * @param int $timestamp Unix timestamp.
     * @param DateTimeZone $timezone Timezone.
     * @return int YYYYMMDD.
     */
    public static function daydate_for_timezone(int $timestamp, DateTimeZone $timezone): int {
        $date = new DateTimeImmutable('@' . $timestamp);
        $date = $date->setTimezone($timezone);
        return (int)$date->format('Ymd');
    }

    /**
     * Create a local calendar DateTime at midnight.
     *
     * @param int $daydate YYYYMMDD.
     * @param DateTimeZone|null $timezone Optional timezone.
     * @return DateTimeImmutable
     */
    public static function date_from_daydate(int $daydate, ?DateTimeZone $timezone = null): DateTimeImmutable {
        $timezone = $timezone ?: new DateTimeZone('UTC');
        $date = DateTimeImmutable::createFromFormat('!Ymd', sprintf('%08d', $daydate), $timezone);
        if (!$date) {
            throw new \coding_exception('Invalid daydate: ' . $daydate);
        }
        return $date;
    }

    /**
     * Add calendar days.
     *
     * @param int $daydate YYYYMMDD.
     * @param int $days Signed day count.
     * @return int YYYYMMDD.
     */
    public static function add_days(int $daydate, int $days): int {
        $date = self::date_from_daydate($daydate);
        if ($days !== 0) {
            $date = $date->modify(($days > 0 ? '+' : '') . $days . ' days');
        }
        return (int)$date->format('Ymd');
    }

    /**
     * ISO weekday, Monday=1 and Sunday=7.
     *
     * @param int $daydate YYYYMMDD.
     * @return int
     */
    public static function iso_weekday(int $daydate): int {
        return (int)self::date_from_daydate($daydate)->format('N');
    }

    /**
     * Calendar distance from start to end.
     *
     * @param int $start YYYYMMDD.
     * @param int $end YYYYMMDD.
     * @return int Signed number of days.
     */
    public static function diff_days(int $start, int $end): int {
        $a = self::date_from_daydate($start);
        $b = self::date_from_daydate($end);
        return (int)$a->diff($b)->format('%r%a');
    }
}
