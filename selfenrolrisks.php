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
 * Drill-down list for the "self-enrolment risks" health signal.
 *
 * Each row links into the course's enrolment methods page, where both
 * findings this signal reports (missing enrolment key, missing end date) are
 * actually fixed.
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

$PAGE->set_url(new \core\url('/local/admincockpit/selfenrolrisks.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('selfenrolrisks', 'local_admincockpit'));
$PAGE->set_heading(get_string('pluginname', 'local_admincockpit'));

dashboard_viewed::create([
    'context' => $context,
    'other' => ['page' => 'selfenrolrisks.php'],
])->trigger();

$signal = health_signals::self_enrolment_risks();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('selfenrolrisks', 'local_admincockpit'));
echo $OUTPUT->notification(get_string('selfenrolrisks_intro', 'local_admincockpit'), 'info');

if (empty($signal->details)) {
    echo $OUTPUT->notification(get_string('selfenrolrisks_none', 'local_admincockpit'), 'success');
} else {
    if ($signal->detailstruncated) {
        echo $OUTPUT->notification(
            get_string('selfenrolrisks_truncated', 'local_admincockpit', $signal->count),
            'info'
        );
    }

    $table = new \core_table\output\html_table();
    $table->head = [
        get_string('course', 'core'),
        get_string('selfenrolrisks_finding', 'local_admincockpit'),
    ];
    foreach ($signal->details as $row) {
        $findings = [];
        if ($row->nokey) {
            $findings[] = get_string('selfenrolrisks_nokey', 'local_admincockpit');
        }
        if ($row->noenddate) {
            $findings[] = get_string('selfenrolrisks_noenddate', 'local_admincockpit');
        }

        $enrolmethodsurl = new \core\url('/enrol/instances.php', ['id' => $row->courseid]);
        $coursecontext = \core\context\course::instance($row->courseid);
        $table->data[] = [
            \core\output\html_writer::link(
                $enrolmethodsurl,
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
