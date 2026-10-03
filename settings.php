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
 * Site defaults for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'block_personalstreak/activityheading',
        get_string('activitysettings', 'block_personalstreak'),
        get_string('activitysettings_desc', 'block_personalstreak')
    ));

    foreach ([
        'trackcourseaccess' => 1,
        'trackcompletion' => 1,
        'tracksubmission' => 1,
        'trackquiz' => 1,
        'trackforum' => 1,
        'trackresourceview' => 1,
        'trackpersonalxp' => 1,
    ] as $name => $default) {
        $settings->add(new admin_setting_configcheckbox(
            'block_personalstreak/' . $name,
            get_string($name, 'block_personalstreak'),
            get_string($name . '_desc', 'block_personalstreak'),
            $default
        ));
    }

    $settings->add(new admin_setting_configtextarea(
        'block_personalstreak/customevents',
        get_string('customevents', 'block_personalstreak'),
        get_string('customevents_desc', 'block_personalstreak'),
        ''
    ));

    $settings->add(new admin_setting_configselect(
        'block_personalstreak/daymode',
        get_string('daymode', 'block_personalstreak'),
        get_string('daymode_desc', 'block_personalstreak'),
        'normal',
        [
            'normal' => get_string('daymode_normal', 'block_personalstreak'),
            'weekends' => get_string('daymode_weekends', 'block_personalstreak'),
            'custom' => get_string('daymode_custom', 'block_personalstreak'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'block_personalstreak/ignoreweekdays',
        get_string('ignoreweekdays', 'block_personalstreak'),
        get_string('ignoreweekdays_desc', 'block_personalstreak'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configselect(
        'block_personalstreak/protectionmode',
        get_string('protectionmode', 'block_personalstreak'),
        get_string('protectionmode_desc', 'block_personalstreak'),
        'none',
        [
            'none' => get_string('protection_none', 'block_personalstreak'),
            'one' => get_string('protection_one', 'block_personalstreak'),
            'rolling' => get_string('protection_rolling', 'block_personalstreak'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'block_personalstreak/protectiondays',
        get_string('protectiondays', 'block_personalstreak'),
        get_string('protectiondays_desc', 'block_personalstreak'),
        1,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'block_personalstreak/protectionperiod',
        get_string('protectionperiod', 'block_personalstreak'),
        get_string('protectionperiod_desc', 'block_personalstreak'),
        30,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtextarea(
        'block_personalstreak/milestones',
        get_string('milestones', 'block_personalstreak'),
        get_string('milestones_desc', 'block_personalstreak'),
        "3|0|0|0|0|1\n7|0|0|0|0|1\n14|0|0|0|0|1\n30|0|0|0|0|1\n60|0|0|0|0|1\n100|0|0|0|0|1"
    ));
}
