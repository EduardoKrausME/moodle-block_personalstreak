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
 * block_personalstreak_edit_form.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Block instance configuration form.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_personalstreak_edit_form extends block_edit_form {
    /**
     * Add block-specific fields.
     *
     * @param MoodleQuickForm $mform Form instance.
     * @return void
     */
    protected function specific_definition($mform) {
        $defaults = \block_personalstreak\service\config_service::get_defaults();
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_personalstreak'));

        foreach ([
            'trackcourseaccess',
            'trackcompletion',
            'tracksubmission',
            'trackquiz',
            'trackforum',
            'trackresourceview',
            'trackpersonalxp',
        ] as $name) {
            $mform->addElement('advcheckbox', 'config_' . $name, get_string($name, 'block_personalstreak'));
            $mform->setDefault('config_' . $name, (int)$defaults[$name]);
            $mform->addHelpButton('config_' . $name, $name, 'block_personalstreak');
        }

        $mform->addElement('textarea', 'config_customevents', get_string('customevents', 'block_personalstreak'),
            ['rows' => 5, 'cols' => 60]);
        $mform->setType('config_customevents', PARAM_RAW_TRIMMED);
        $mform->setDefault('config_customevents', (string)$defaults['customevents']);
        $mform->addHelpButton('config_customevents', 'customevents', 'block_personalstreak');

        $mform->addElement('select', 'config_daymode', get_string('daymode', 'block_personalstreak'), [
            'normal' => get_string('daymode_normal', 'block_personalstreak'),
            'weekends' => get_string('daymode_weekends', 'block_personalstreak'),
            'custom' => get_string('daymode_custom', 'block_personalstreak'),
        ]);
        $mform->setDefault('config_daymode', (string)$defaults['daymode']);

        $weekdayoptions = [
            1 => get_string('monday', 'calendar'),
            2 => get_string('tuesday', 'calendar'),
            3 => get_string('wednesday', 'calendar'),
            4 => get_string('thursday', 'calendar'),
            5 => get_string('friday', 'calendar'),
            6 => get_string('saturday', 'calendar'),
            7 => get_string('sunday', 'calendar'),
        ];
        $select = $mform->addElement('select', 'config_ignoreweekdays', get_string('ignoreweekdays', 'block_personalstreak'),
            $weekdayoptions, ['size' => 7]);
        $select->setMultiple(true);
        $mform->setDefault('config_ignoreweekdays', $defaults['ignoreweekdays']);
        $mform->disabledIf('config_ignoreweekdays', 'config_daymode', 'neq', 'custom');

        $mform->addElement('select', 'config_protectionmode', get_string('protectionmode', 'block_personalstreak'), [
            'none' => get_string('protection_none', 'block_personalstreak'),
            'one' => get_string('protection_one', 'block_personalstreak'),
            'rolling' => get_string('protection_rolling', 'block_personalstreak'),
        ]);
        $mform->setDefault('config_protectionmode', (string)$defaults['protectionmode']);

        $mform->addElement('text', 'config_protectiondays', get_string('protectiondays', 'block_personalstreak'));
        $mform->setType('config_protectiondays', PARAM_INT);
        $mform->setDefault('config_protectiondays', (int)$defaults['protectiondays']);
        $mform->disabledIf('config_protectiondays', 'config_protectionmode', 'neq', 'rolling');

        $mform->addElement('text', 'config_protectionperiod', get_string('protectionperiod', 'block_personalstreak'));
        $mform->setType('config_protectionperiod', PARAM_INT);
        $mform->setDefault('config_protectionperiod', (int)$defaults['protectionperiod']);
        $mform->disabledIf('config_protectionperiod', 'config_protectionmode', 'neq', 'rolling');

        $mform->addElement('textarea', 'config_milestones', get_string('milestones', 'block_personalstreak'),
            ['rows' => 8, 'cols' => 70]);
        $mform->setType('config_milestones', PARAM_RAW_TRIMMED);
        $mform->setDefault('config_milestones', (string)$defaults['milestones']);
        $mform->addHelpButton('config_milestones', 'milestones', 'block_personalstreak');
    }
}
