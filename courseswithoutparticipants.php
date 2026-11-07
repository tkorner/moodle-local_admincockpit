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
 * Drill-down list for the "courses without students or teachers" health
 * signal.
 *
 * Each row links into the course's participants page, where missing
 * students or teachers are enrolled - or where an abandoned course becomes
 * recognisable as such.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_admincockpit\event\dashboard_viewed;
use local_admincockpit\metrics\health_signals;

require_login();
$context = \core\context\system::instance();
require_capability('local/admincockpit:view', $context);

$PAGE->set_url(new \core\url('/local/admincockpit/courseswithoutparticipants.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('courseswithoutparticipants', 'local_admincockpit'));
$PAGE->set_heading(get_string('pluginname', 'local_admincockpit'));

dashboard_viewed::create([
    'context' => $context,
    'other' => ['page' => 'courseswithoutparticipants.php'],
])->trigger();

$signal = health_signals::courses_without_participants();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('courseswithoutparticipants', 'local_admincockpit'));
echo $OUTPUT->notification(get_string('courseswithoutparticipants_intro', 'local_admincockpit'), 'info');

if (empty($signal->details)) {
    echo $OUTPUT->notification(get_string('courseswithoutparticipants_none', 'local_admincockpit'), 'success');
} else {
    if ($signal->detailstruncated) {
        echo $OUTPUT->notification(
            get_string('courseswithoutparticipants_truncated', 'local_admincockpit', $signal->count),
            'info'
        );
    }

    $table = new \core_table\output\html_table();
    $table->head = [
        get_string('course', 'core'),
        get_string('courseswithoutparticipants_finding', 'local_admincockpit'),
    ];
    foreach ($signal->details as $row) {
        $findings = [];
        if ($row->nostudents) {
            $findings[] = get_string('courseswithoutparticipants_nostudents', 'local_admincockpit');
        }
        if ($row->noteachers) {
            $findings[] = get_string('courseswithoutparticipants_noteachers', 'local_admincockpit');
        }

        $participantsurl = new \core\url('/user/index.php', ['id' => $row->courseid]);
        $coursecontext = \core\context\course::instance($row->courseid);
        $table->data[] = [
            \core\output\html_writer::link(
                $participantsurl,
                format_string($row->coursefullname, true, ['context' => $coursecontext])
            ),
            implode(', ', $findings),
        ];
    }
    echo \core\output\html_writer::table($table);
}

echo \core\output\html_writer::link(
    new \core\url('/local/admincockpit/index.php'),
    get_string('backtodashboard', 'local_admincockpit')
);

echo $OUTPUT->footer();
