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
 * block_personalstreak.php for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Personal streak block.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_personalstreak extends block_base {
    /**
     * Initialise the block title.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_personalstreak');
    }

    /**
     * Course pages only.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'all' => false,
            'course-view' => true,
            'course-view-*' => true,
        ];
    }

    /**
     * Site defaults are available.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Avoid conflicting course configurations from duplicate instances.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }


    /**
     * Require the plugin-specific management capability in addition to normal block editing rights.
     *
     * @return bool
     */
    public function user_can_edit() {
        return parent::user_can_edit()
            && $this->context
            && has_capability('block/personalstreak:manage', $this->context);
    }

    /**
     * Persist block config and synchronize the course-level observer config.
     *
     * @param stdClass $data Form data.
     * @param bool $nolongerused Kept for block API compatibility.
     * @return bool
     */
    public function instance_config_save($data, $nolongerused = false) {
        $saved = parent::instance_config_save($data, $nolongerused);
        if ($saved && !empty($this->page->course->id) && $this->page->course->id != SITEID) {
            \block_personalstreak\service\config_service::save_for_course((int)$this->page->course->id, $data);
        }
        return $saved;
    }

    /**
     * Build the block with only the current user's own state.
     *
     * @return stdClass
     */
    public function get_content() {
        global $COURSE, $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser() || empty($COURSE->id) || $COURSE->id == SITEID) {
            return $this->content;
        }
        if (!$this->context || !has_capability('block/personalstreak:viewown', $this->context)) {
            return $this->content;
        }
        $coursecontext = \context_course::instance((int)$COURSE->id);
        if (!is_enrolled($coursecontext, (int)$USER->id, '', true)) {
            return $this->content;
        }

        $state = \block_personalstreak\api::get_user_state((int)$USER->id, (int)$COURSE->id);
        $calendar = \block_personalstreak\service\state_service::get_calendar_data((int)$USER->id, (int)$COURSE->id, 30);

        $current = (int)$state['currentstreak'];
        $template = [
            'currentstreak' => $current,
            'headline' => get_string('streakheadline', 'block_personalstreak', $current),
            'active_today' => !empty($state['active_today']),
            'todaymessage' => !empty($state['active_today'])
                ? get_string('studiedtoday', 'block_personalstreak')
                : get_string('notstudiedtoday', 'block_personalstreak'),
            'bestlabel' => get_string('personalrecord', 'block_personalstreak', (int)$state['beststreak']),
            'active30label' => get_string('active30', 'block_personalstreak', (int)$state['active_last_30_days']),
            'hasnextmilestone' => $state['next_milestone'] !== null,
            'nextmilestone' => $state['next_milestone'],
            'nextmilestonelabel' => $state['next_milestone'] === null ? '' : get_string(
                'nextmilestonevalue',
                'block_personalstreak',
                (int)$state['next_milestone']
            ),
            'daysuntil' => $state['days_until_next_milestone'],
            'calendar' => $calendar,
            'calendarlabel' => get_string('last30days', 'block_personalstreak'),
        ];

        $this->page->requires->js_call_amd('block_personalstreak/streak', 'init', ['[data-region="personalstreak"]']);
        $this->content->text = $OUTPUT->render_from_template('block_personalstreak/content', $template);
        return $this->content;
    }
}
