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
 * Tests for health_signal.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

/**
 * Class health_signal_test
 */
final class health_signal_test extends \advanced_testcase {
    /**
     * All constructor arguments are stored as-is on their matching
     * readonly properties.
     *
     * @return void
     */
    public function test_constructor_stores_all_values(): void {
        $signal = new health_signal(
            component: 'local_admincockpit',
            key: 'cron',
            label: 'Cron status',
            value: '2 hours ago',
            severity: 'warning',
            url: '/admin/tool/task/scheduledtasks.php',
            valuetitle: '2026-08-02 10:00',
            helpicon: '<a>help</a>',
        );

        $this->assertSame('local_admincockpit', $signal->component);
        $this->assertSame('cron', $signal->key);
        $this->assertSame('Cron status', $signal->label);
        $this->assertSame('2 hours ago', $signal->value);
        $this->assertSame('warning', $signal->severity);
        $this->assertSame('/admin/tool/task/scheduledtasks.php', $signal->url);
        $this->assertSame('2026-08-02 10:00', $signal->valuetitle);
        $this->assertSame('<a>help</a>', $signal->helpicon);
    }

    /**
     * valuetitle and helpicon default to an empty string when omitted -
     * most signals (e.g. duplicate emails, courses without an end date)
     * don't need either.
     *
     * @return void
     */
    public function test_optional_arguments_default_to_empty_string(): void {
        $signal = new health_signal(
            component: 'local_admincockpit',
            key: 'duplicateemails',
            label: 'Duplicate email addresses',
            value: 3,
            severity: 'warning',
            url: '/local/admincockpit/duplicateemails.php',
        );

        $this->assertSame('', $signal->valuetitle);
        $this->assertSame('', $signal->helpicon);
    }

    /**
     * value accepts both an int (e.g. duplicate-email count) and a string
     * (e.g. the security overview's compact "15 OK · 4 warnings" summary).
     *
     * @return void
     */
    public function test_value_accepts_int_and_string(): void {
        $intvalue = new health_signal('local_admincockpit', 'noenddate', 'Label', 5, 'ok', '/x.php');
        $stringvalue = new health_signal('local_admincockpit', 'security', 'Label', '15 OK', 'ok', '/x.php');

        $this->assertSame(5, $intvalue->value);
        $this->assertSame('15 OK', $stringvalue->value);
    }
}
