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
 * Tests for the health_signals hook and this plugin's own listener.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit\hook;

use local_admincockpit\health_signal;

/**
 * Class health_signals_test
 */
final class health_signals_test extends \advanced_testcase {
    /**
     * Purges the MUC cache the underlying metrics read through, same as
     * tests/metrics/health_signals_test.php, so no test can see another
     * test's cached numbers.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        \core_cache\cache::make('local_admincockpit', 'dashboarddata')->purge();
    }

    /**
     * Dispatching the hook (the same way classes/output/dashboard_page.php
     * will from step 17 onwards) picks up this plugin's own db/hooks.php
     * registration and returns exactly the four SPEC section 4 signals,
     * each correctly identified by component+key.
     *
     * @covers \local_admincockpit\hook\health_signals::add_signal
     * @return void
     */
    public function test_dispatch_returns_four_builtin_signals(): void {
        $hook = new health_signals();
        \core\hook\manager::get_instance()->dispatch($hook);

        $signals = $hook->get_signals();
        $this->assertCount(5, $signals);

        $keys = array_map(static fn (health_signal $signal) => $signal->key, $signals);
        $this->assertEqualsCanonicalizing(
            ['duplicateemails', 'courseswithoutenddate', 'unpublishedcourses', 'security', 'cron'],
            $keys
        );

        foreach ($signals as $signal) {
            $this->assertSame('local_admincockpit', $signal->component);
            $this->assertContains($signal->severity, ['ok', 'warning', 'error']);
        }
    }

    /**
     * A clean site (no duplicate emails, no courses without an end date,
     * cron just having run, presumably no security errors on a fresh test
     * install) reports 'ok' for the two simple count-based signals.
     *
     * @covers \local_admincockpit\hook\health_signals::add_signal
     * @return void
     */
    public function test_clean_site_reports_ok_for_count_based_signals(): void {
        $this->resetAfterTest(true);
        set_config('lastcronstart', time(), 'tool_task');

        $hook = new health_signals();
        \core\hook\manager::get_instance()->dispatch($hook);

        $bykey = [];
        foreach ($hook->get_signals() as $signal) {
            $bykey[$signal->key] = $signal;
        }

        $this->assertSame(0, $bykey['duplicateemails']->value);
        $this->assertSame('ok', $bykey['duplicateemails']->severity);
        $this->assertSame(0, $bykey['courseswithoutenddate']->value);
        $this->assertSame('ok', $bykey['courseswithoutenddate']->severity);
    }
}
