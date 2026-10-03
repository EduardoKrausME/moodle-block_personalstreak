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
 * English strings for block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['active'] = 'Active';
$string['active30'] = 'Active days in the last 30 days: {$a}';
$string['activitysettings'] = 'What counts as study';
$string['activitysettings_desc'] = 'These are site defaults. Teachers can override them in the block settings for each course.';
$string['blocksettings'] = 'Study consistency settings';
$string['customevents'] = 'Custom Moodle events';
$string['customevents_desc'] = 'One fully qualified event class per line, for example \\mod_lesson\\event\\lesson_ended.';
$string['customevents_help'] = 'One fully qualified event class per line, for example \\mod_lesson\\event\\lesson_ended.';
$string['daymode'] = 'Ignored days';
$string['daymode_custom'] = 'Ignore selected weekdays';
$string['daymode_desc'] = 'Choose whether all weekdays are required or some days should not break a streak.';
$string['daymode_normal'] = 'Count every day normally';
$string['daymode_weekends'] = 'Ignore Saturday and Sunday';
$string['event_personal_record_reached'] = 'Personal streak record reached';
$string['event_streak_broken'] = 'Personal streak broken';
$string['event_streak_continued'] = 'Personal streak continued';
$string['event_streak_milestone_reached'] = 'Personal streak milestone reached';
$string['event_streak_started'] = 'Personal streak started';
$string['ignored'] = 'Ignored day';
$string['ignoreweekdays'] = 'Weekdays to ignore';
$string['ignoreweekdays_desc'] = 'For custom mode, use ISO weekday numbers 1 to 7 separated by commas, where Monday is 1 and Sunday is 7.';
$string['inactive'] = 'No activity';
$string['last30days'] = 'Your last 30 days';
$string['milestonerewardlabel'] = 'Personal streak milestone: {$a} days';
$string['milestones'] = 'Milestones';
$string['milestones_desc'] = 'One milestone per line using days|xp|credits|badgeid|event|visual. Example: 7|50|20|0|1|1. Set a value to 0 to disable that reward. The default milestones are visual only.';
$string['milestones_help'] = 'One milestone per line using days|xp|credits|badgeid|event|visual. Example: 7|50|20|0|1|1. Set a value to 0 to disable that reward. The default milestones are visual only.';
$string['nextmilestone'] = 'Next milestone';
$string['nextmilestonevalue'] = '{$a} consecutive days';
$string['notstudiedtoday'] = 'No qualifying study activity has been recorded today yet.';
$string['personalrecord'] = 'Personal record: {$a} days';
$string['personalstreak:addinstance'] = 'Add a personal study streak block';
$string['personalstreak:manage'] = 'Manage personal study streak settings';
$string['personalstreak:myaddinstance'] = 'Add a personal study streak block to Dashboard';
$string['personalstreak:viewown'] = 'View own study streak';
$string['pluginname'] = 'Personal study streak';
$string['privacy:metadata:days'] = 'Stores consolidated daily study activity for personal streak calculations.';
$string['privacy:metadata:days:activitycount'] = 'Number of qualifying study events recorded that day.';
$string['privacy:metadata:days:courseid'] = 'The course where study activity occurred.';
$string['privacy:metadata:days:daydate'] = 'The user-local calendar day.';
$string['privacy:metadata:days:firstactivity'] = 'Timestamp of the first qualifying activity that day.';
$string['privacy:metadata:days:lastactivity'] = 'Timestamp of the latest qualifying activity that day.';
$string['privacy:metadata:days:source'] = 'Types of study signals recorded that day.';
$string['privacy:metadata:days:userid'] = 'The user whose activity day is stored.';
$string['privacy:metadata:events'] = 'Stores idempotency hashes for personal streak events.';
$string['privacy:metadata:events:daydate'] = 'The user-local calendar day associated with the guarded event.';
$string['privacy:metadata:events:eventkey'] = 'The semantic key used to prevent a duplicate streak event.';
$string['privacy:metadata:events:uniquehash'] = 'The idempotency hash used to prevent duplicate events.';
$string['privacy:metadata:mstone'] = 'Stores milestone delivery state so rewards and events are not duplicated.';
$string['privacy:metadata:mstone:badgeawarded'] = 'Whether the configured badge reward was delivered.';
$string['privacy:metadata:mstone:creditsrewarded'] = 'Whether the configured credit reward was delivered.';
$string['privacy:metadata:mstone:eventfired'] = 'Whether the configured milestone event was emitted.';
$string['privacy:metadata:mstone:milestonedays'] = 'The personal streak length represented by the milestone.';
$string['privacy:metadata:mstone:xprewarded'] = 'Whether the configured XP reward was delivered.';
$string['privacy:metadata:state'] = 'Stores the user personal streak aggregate.';
$string['privacy:metadata:state:beststreak'] = 'Best personal streak length.';
$string['privacy:metadata:state:currentstreak'] = 'Current personal streak length.';
$string['privacy:metadata:state:lastactiveday'] = 'Most recent user-local active day.';
$string['privacy:metadata:state:longeststreakend'] = 'The last active day of the longest personal streak.';
$string['privacy:metadata:state:longeststreakstart'] = 'The first day of the longest personal streak.';
$string['privacy:metadata:state:totalactivedays'] = 'Total number of active days stored.';
$string['protection_none'] = 'No protection';
$string['protection_one'] = 'One tolerated day per streak';
$string['protection_rolling'] = 'N tolerated days in a rolling period';
$string['protectiondays'] = 'Tolerated days';
$string['protectiondays_desc'] = 'Maximum protected missed days inside the configured rolling period.';
$string['protectionmode'] = 'Streak protection';
$string['protectionmode_desc'] = 'Protection does not create fake activity. It only prevents a missed required day from resetting the existing streak.';
$string['protectionperiod'] = 'Protection period in days';
$string['protectionperiod_desc'] = 'Rolling window used to limit tolerated missed days.';
$string['streakheadline'] = '{$a} days of continuous learning';
$string['studiedtoday'] = 'You studied today.';
$string['today'] = 'Today';
$string['trackcompletion'] = 'Activity completion';
$string['trackcompletion_desc'] = 'Count an activity when Moodle reports it as completed.';
$string['trackcompletion_help'] = 'Count an activity when Moodle reports it as completed.';
$string['trackcourseaccess'] = 'Course access';
$string['trackcourseaccess_desc'] = 'Count opening the course as a study signal. Moodle login alone is never counted.';
$string['trackcourseaccess_help'] = 'Count opening the course as a study signal. Moodle login alone is never counted.';
$string['trackforum'] = 'Forum post';
$string['trackforum_desc'] = 'Count a new forum post.';
$string['trackforum_help'] = 'Count a new forum post.';
$string['trackpersonalxp'] = 'Activity registered by Personal XP';
$string['trackpersonalxp_desc'] = 'Accept study signals emitted by local_personalxp or sent through the public block API.';
$string['trackpersonalxp_help'] = 'Accept study signals emitted by local_personalxp or sent through the public block API.';
$string['trackquiz'] = 'Quiz attempt';
$string['trackquiz_desc'] = 'Count a submitted quiz attempt.';
$string['trackquiz_help'] = 'Count a submitted quiz attempt.';
$string['trackresourceview'] = 'Resource view';
$string['trackresourceview_desc'] = 'Count views of passive learning resources such as Page, Book, File, URL and Folder.';
$string['trackresourceview_help'] = 'Count views of passive learning resources such as Page, Book, File, URL and Folder.';
$string['tracksubmission'] = 'Assignment submission';
$string['tracksubmission_desc'] = 'Count a submitted assignment.';
$string['tracksubmission_help'] = 'Count a submitted assignment.';
