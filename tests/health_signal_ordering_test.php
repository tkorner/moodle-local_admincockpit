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
 * Tests for health_signal_ordering.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

/**
 * Class health_signal_ordering_test
 */
final class health_signal_ordering_test extends \advanced_testcase {
    /**
     * Builds four fixture signals in a fixed order, matching this plugin's
     * own built-in keys.
     *
     * @return health_signal[]
     */
    private function fixture_signals(): array {
        return [
            new health_signal('local_admincockpit', 'duplicateemails', 'Duplicate emails', 0, 'ok', '/a.php'),
            new health_signal('local_admincockpit', 'courseswithoutenddate', 'No end date', 1, 'warning', '/b.php'),
            new health_signal('local_admincockpit', 'security', 'Security', '15 OK', 'ok', '/c.php'),
            new health_signal('local_admincockpit', 'cron', 'Cron', 'ok', 'ok', '/d.php'),
        ];
    }

    /**
     * An empty (never configured) setting keeps every contributed signal, in
     * the exact order the hook produced them - existing installations see no
     * behaviour change at all.
     *
     * @covers \local_admincockpit\health_signal_ordering::apply
     * @return void
     */
    public function test_empty_setting_keeps_original_order(): void {
        $result = health_signal_ordering::apply($this->fixture_signals(), '');

        $this->assertSame(
            ['duplicateemails', 'courseswithoutenddate', 'security', 'cron'],
            array_map(fn (health_signal $s) => $s->key, $result)
        );
    }

    /**
     * Explicitly listed refs are shown in the order they're listed; anything
     * not mentioned is appended afterwards in its original order.
     *
     * @covers \local_admincockpit\health_signal_ordering::apply
     * @return void
     */
    public function test_explicit_entries_are_reordered_unmentioned_appended(): void {
        $raw = "local_admincockpit:cron\nlocal_admincockpit:security";

        $result = health_signal_ordering::apply($this->fixture_signals(), $raw);

        $this->assertSame(
            ['cron', 'security', 'duplicateemails', 'courseswithoutenddate'],
            array_map(fn (health_signal $s) => $s->key, $result)
        );
    }

    /**
     * A '-component:key' prefixed entry hides that signal entirely, without
     * affecting the order of the others.
     *
     * @covers \local_admincockpit\health_signal_ordering::apply
     * @return void
     */
    public function test_dash_prefixed_entry_hides_signal(): void {
        $raw = "-local_admincockpit:security";

        $result = health_signal_ordering::apply($this->fixture_signals(), $raw);

        $this->assertSame(
            ['duplicateemails', 'courseswithoutenddate', 'cron'],
            array_map(fn (health_signal $s) => $s->key, $result)
        );
    }

    /**
     * A ref referring to a signal that no longer exists (e.g. its
     * contributing plugin was uninstalled) is silently ignored, same
     * tolerance as the existing 'activeschools' setting for a stale code.
     *
     * @covers \local_admincockpit\health_signal_ordering::apply
     * @return void
     */
    public function test_stale_ref_to_missing_signal_is_ignored(): void {
        $raw = "some_uninstalled_plugin:longgoneSignal";

        $result = health_signal_ordering::apply($this->fixture_signals(), $raw);

        $this->assertCount(4, $result);
    }

    /**
     * parse() splits on newlines, trims whitespace, skips blank lines, and
     * recognises the '-' disable prefix.
     *
     * @covers \local_admincockpit\health_signal_ordering::parse
     * @return void
     */
    public function test_parse(): void {
        $raw = "  local_admincockpit:cron  \n\n-local_admincockpit:security\n";

        $this->assertSame(
            [
                ['ref' => 'local_admincockpit:cron', 'enabled' => true],
                ['ref' => 'local_admincockpit:security', 'enabled' => false],
            ],
            health_signal_ordering::parse($raw)
        );
    }
}
