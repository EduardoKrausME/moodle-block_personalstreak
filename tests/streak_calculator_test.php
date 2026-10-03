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
 * tests/streak_calculator_test.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_personalstreak;

use block_personalstreak\service\streak_calculator;

/**
 * Calendar calculation tests.
 *
 * @package block_personalstreak
 * @covers \block_personalstreak\service\streak_calculator
 */
final class streak_calculator_test extends \advanced_testcase {
    /**
     * Base config used by pure calculator tests.
     *
     * @param array $overrides Overrides.
     * @return array
     */
    private function config(array $overrides = []): array {
        return array_merge([
            'daymode' => 'normal',
            'ignoreweekdays' => [],
            'protectionmode' => 'none',
            'protectiondays' => 0,
            'protectionperiod' => 30,
        ], $overrides);
    }

    /**
     * Normal consecutive sequence.
     */
    public function test_normal_streak(): void {
        $state = streak_calculator::calculate(
            [20260929, 20260930, 20261001, 20261002, 20261003],
            $this->config(),
            20261003
        );
        $this->assertSame(5, $state['currentstreak']);
        $this->assertSame(5, $state['beststreak']);
        $this->assertSame(20260929, $state['longeststreakstart']);
        $this->assertSame(20261003, $state['longeststreakend']);
    }

    /**
     * A missed required day breaks the previous sequence.
     */
    public function test_streak_break(): void {
        $state = streak_calculator::calculate(
            [20261001, 20261002, 20261004],
            $this->config(),
            20261004
        );
        $this->assertSame(1, $state['currentstreak']);
        $this->assertSame(2, $state['beststreak']);
        $this->assertSame(20261004, $state['currentstreakstart']);
    }

    /**
     * Month boundaries are ordinary consecutive calendar days.
     */
    public function test_month_rollover(): void {
        $state = streak_calculator::calculate(
            [20260929, 20260930, 20261001, 20261002],
            $this->config(),
            20261002
        );
        $this->assertSame(4, $state['currentstreak']);
        $this->assertSame(4, $state['beststreak']);
    }

    /**
     * Ignored weekends neither increment nor break the sequence.
     */
    public function test_ignored_weekend(): void {
        $state = streak_calculator::calculate(
            [20261002, 20261005],
            $this->config(['daymode' => 'weekends']),
            20261005
        );
        $this->assertSame(2, $state['currentstreak']);
        $this->assertSame(2, $state['beststreak']);
    }

    /**
     * Protection preserves the streak without inventing an active day.
     */
    public function test_one_day_protection(): void {
        $state = streak_calculator::calculate(
            [20261001, 20261003],
            $this->config(['protectionmode' => 'one']),
            20261003
        );
        $this->assertSame(2, $state['currentstreak']);
        $this->assertSame(2, $state['totalactivedays']);
        $this->assertContains(20261002, $state['protecteddaydates']);
    }


    /**
     * The current day is not a missed day until it has ended.
     */
    public function test_current_day_without_activity_does_not_break_streak(): void {
        $state = streak_calculator::calculate(
            [20261001, 20261002],
            $this->config(),
            20261003
        );
        $this->assertSame(2, $state['currentstreak']);
        $this->assertSame(20261002, $state['currentstreakend']);
    }

    /**
     * Rolling protection respects the configured allowance inside its period.
     */
    public function test_rolling_protection_limit(): void {
        $config = $this->config([
            'protectionmode' => 'rolling',
            'protectiondays' => 1,
            'protectionperiod' => 7,
        ]);
        $state = streak_calculator::calculate(
            [20261001, 20261003, 20261005],
            $config,
            20261005
        );
        $this->assertSame(1, $state['currentstreak']);
        $this->assertSame(2, $state['beststreak']);
        $this->assertContains(20261002, $state['protecteddaydates']);
        $this->assertNotContains(20261004, $state['protecteddaydates']);
    }
}
