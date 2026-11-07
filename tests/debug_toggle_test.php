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
 * Tests for debug_toggle - the dashboard's developer-debugging switch.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

/**
 * Class debug_toggle_test
 */
final class debug_toggle_test extends \advanced_testcase {
    /** @var array config.php settings as they were before the test */
    private array $originalconfigphpsettings;

    /**
     * Starts every test from "not set in config.php", whatever the test
     * site's own config.php contains, so the results don't depend on it.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->originalconfigphpsettings = $CFG->config_php_settings;
        unset($CFG->config_php_settings['debug'], $CFG->config_php_settings['debugdisplay']);
    }

    /**
     * Restores the config.php settings changed in setUp() or a test.
     *
     * @return void
     */
    protected function tearDown(): void {
        global $CFG;

        $CFG->config_php_settings = $this->originalconfigphpsettings;
        parent::tearDown();
    }

    /**
     * Switching on sets both settings, switching off resets both, and
     * is_on() follows.
     *
     * @covers \local_admincockpit\debug_toggle::set
     * @covers \local_admincockpit\debug_toggle::is_on
     * @return void
     */
    public function test_set_switches_both_settings(): void {
        global $CFG;

        debug_toggle::set(true);
        $this->assertSame((string) DEBUG_DEVELOPER, (string) get_config('core', 'debug'));
        $this->assertSame('1', (string) get_config('core', 'debugdisplay'));
        $this->assertSame(DEBUG_DEVELOPER, (int) $CFG->debug);
        $this->assertTrue(debug_toggle::is_on());

        debug_toggle::set(false);
        $this->assertSame((string) DEBUG_NONE, (string) get_config('core', 'debug'));
        $this->assertSame('0', (string) get_config('core', 'debugdisplay'));
        $this->assertFalse(debug_toggle::is_on());
    }

    /**
     * Developer level without on-screen display doesn't count as on - the
     * switch is about making messages visible.
     *
     * @covers \local_admincockpit\debug_toggle::is_on
     * @return void
     */
    public function test_is_on_requires_display(): void {
        set_config('debug', DEBUG_DEVELOPER);
        set_config('debugdisplay', 0);

        $this->assertFalse(debug_toggle::is_on());
    }

    /**
     * Every actual change lands in the config change log, like a change on
     * core's own Debugging settings page; an unchanged value does not.
     *
     * @covers \local_admincockpit\debug_toggle::set
     * @return void
     */
    public function test_set_logs_only_actual_changes(): void {
        global $DB;

        set_config('debug', DEBUG_NONE);
        set_config('debugdisplay', 0);
        $DB->delete_records('config_log');

        debug_toggle::set(true);
        $logged = $DB->get_records('config_log', null, 'id', 'name, oldvalue, value, plugin');
        $this->assertCount(2, $logged);
        $byname = array_column($logged, null, 'name');
        $this->assertSame((string) DEBUG_NONE, $byname['debug']->oldvalue);
        $this->assertSame((string) DEBUG_DEVELOPER, $byname['debug']->value);
        $this->assertNull($byname['debug']->plugin);

        $DB->delete_records('config_log');
        debug_toggle::set(true);
        $this->assertSame(0, $DB->count_records('config_log'));
    }

    /**
     * A value fixed in config.php is reported as forced, and set() refuses
     * instead of writing a database value that would have no effect.
     *
     * @covers \local_admincockpit\debug_toggle::is_forced
     * @covers \local_admincockpit\debug_toggle::set
     * @return void
     */
    public function test_forced_in_config_php(): void {
        global $CFG;

        $this->assertFalse(debug_toggle::is_forced());

        $CFG->config_php_settings['debugdisplay'] = 1;
        $this->assertTrue(debug_toggle::is_forced());

        $this->expectException(\coding_exception::class);
        debug_toggle::set(true);
    }
}
