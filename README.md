# Personal Study Streak (`block_personalstreak`)

`block_personalstreak` gives each learner a private view of their own study consistency inside a Moodle course. It focuses on personal continuity: current streak, personal best, active days and upcoming milestones. It does not create rankings, compare learners or expose another learner's streak in the block.

## What it does

The plugin listens to Moodle events that represent actual study actions and consolidates them into one daily record per user and course. A day can count because of course access, activity completion, assignment submission, quiz attempt, forum post, resource view, a configured Moodle event or an activity signal sent by `local_personalxp` through the public API.

A Moodle login by itself never counts as study. Course access is a separate event and can be enabled or disabled by the teacher.

The daily consolidation keeps page rendering cheap: the block does not rescan Moodle logs every time it is displayed. Multiple qualifying events on the same day increase `activitycount` in the same daily record, while streak state changes only when the calendar state actually changes.

## How streaks work

All calendar decisions use the learner's Moodle timezone. The stored `daydate` is a user-local `YYYYMMDD` calendar identifier rather than a UTC date, which avoids the usual midnight bug where a late-night activity ends up on the wrong day.

Ignored weekdays do not add a day to the streak and do not break it. This means that with weekends ignored, an active Friday followed by an active Monday extends the same streak by one study day rather than pretending Saturday and Sunday were active.

Protection behaves the same way: it never creates an active day. A protected missed day only preserves the existing streak. The available modes are no protection, one tolerated required day per streak, or N tolerated required days inside a rolling period.

## What can count as study

The course configuration can enable or disable these sources independently:

- course access;
- activity completion;
- assignment submission;
- submitted quiz attempt;
- forum post;
- view of passive resources such as Page, Book, File, URL and Folder;
- activity emitted by `local_personalxp` or sent to the public streak API;
- any additional Moodle event class explicitly configured by the teacher or administrator.

Custom events are entered one fully qualified class per line, for example:

```text
\mod_lesson\event\lesson_ended
\mod_h5pactivity\event\attempt_submitted
```

## Milestones and rewards

Milestones are configured one per line using:

```text
days|xp|credits|badgeid|event|visual
```

Examples:

```text
3|0|0|0|0|1
7|50|20|0|1|1
14|100|0|12|1|1
30|0|0|0|0|1
```

The 7-day example awards 50 XP, requests 20 credits through the public `local_rewardshop` API when that plugin is available, fires `streak_milestone_reached` and keeps the milestone visible in the learner interface. The 14-day example also issues Moodle badge id 12. A zero value disables that reward.

XP is awarded only through the public `local_personalxp` service. The block never reads or writes Personal XP tables. Credits follow the same rule: `block_personalstreak` calls `\local_rewardshop\api::add_credits()` when that public API exists and never accesses wallet or ledger tables directly.

Milestones are recorded once per learner, course and milestone, so reloading a page, double event delivery or reaching the same streak length again does not duplicate a reward.

## Learner view

The block shows only the current learner's data, for example:

```text
🔥 6 days of continuous learning
You studied today.
Personal record: 14 days
Active days in the last 30 days: 18

Next milestone
7 consecutive days
```

Below the summary, a compact 30-day calendar shows active, inactive and ignored days. Selecting a cell reveals the local date and its status. The representation is intentionally simple and belongs to the plugin rather than imitating a third-party activity calendar.

## Public API

Other Moodle plugins can read the current personal state with:

```php
$state = \block_personalstreak\api::get_user_state($userid, $courseid);
```

The result contains:

```php
[
    'currentstreak' => 6,
    'beststreak' => 14,
    'active_today' => true,
    'active_last_30_days' => 18,
    'next_milestone' => 7,
    'days_until_next_milestone' => 1,
]
```

A trusted plugin can also contribute an already-qualified study signal without touching the block's tables:

```php
\block_personalstreak\api::record_activity(
    $userid,
    $courseid,
    'personalxp_activity',
    $eventtimestamp
);
```

This is the preferred integration path when `local_personalxp` knows that an activity should count but does not emit a dedicated Moodle event.

## Moodle events emitted by the plugin

The plugin can emit these events when the corresponding state transition occurs:

```text
\block_personalstreak\event\streak_started
\block_personalstreak\event\streak_continued
\block_personalstreak\event\streak_milestone_reached
\block_personalstreak\event\personal_record_reached
\block_personalstreak\event\streak_broken
```

An idempotency guard prevents duplicate transition events. Milestone events are optional per configured milestone; the other state-transition events describe actual changes in the learner's personal streak.

## Typical use

A course that expects study on weekdays can ignore Saturday and Sunday, count course access plus meaningful learning actions, allow one protected missed weekday and use visual milestones at 3, 7, 14 and 30 days. Another course can disable course access entirely and require completion, submissions, quizzes or forum participation before a day becomes active.

The important distinction is that the streak represents consistency, while `local_personalxp` remains responsible for XP. The block can request XP as a milestone reward, but it never becomes a second XP engine.
