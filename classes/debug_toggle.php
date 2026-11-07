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
 * The dashboard's developer-debugging switch (SPEC section 5, "Quick cache
 * purge & debug switch").
 *
 * Page-free like loginas_guard, so the state decisions are unit-testable on
 * their own; index.php only adds the sesskey and moodle/site:config checks
 * around set().
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

/**
 * Reads and switches core's 'debug' and 'debugdisplay' settings together.
 */
final class debug_toggle {
    /** @var string[] The two core settings the switch controls, always together. */
    private const SETTINGS = ['debug', 'debugdisplay'];

    /**
     * Whether developer debugging is currently on, i.e. the most verbose
     * level *and* shown on screen - anything less (e.g. DEBUG_NORMAL, or
     * DEBUG_DEVELOPER only written to the log) counts as off, since the
     * switch exists to make messages visible while troubleshooting.
     *
     * @return bool
     */
    public static function is_on(): bool {
        global $CFG;

        return (int) $CFG->debug === DEBUG_DEVELOPER && !empty($CFG->debugdisplay);
    }

    /**
     * Whether config.php fixes either setting. set_config() still writes
     * the database in that case but leaves $CFG alone (verified against
     * lib/moodlelib.php), so switching would silently do nothing - the
     * dashboard shows the state without a button instead.
     *
     * @return bool
     */
    public static function is_forced(): bool {
        global $CFG;

        foreach (self::SETTINGS as $name) {
            if (array_key_exists($name, $CFG->config_php_settings)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Switches developer debugging on (DEBUG_DEVELOPER, shown on screen) or
     * off (DEBUG_NONE, not shown), as SPEC section 5 specifies. Switching
     * off therefore also resets a deliberately chosen lower level such as
     * DEBUG_NORMAL to none.
     *
     * Each actual change is written to the config change log
     * (report_configlog), the same audit trail a change on core's own
     * Debugging settings page leaves.
     *
     * The caller is responsible for require_sesskey() and the
     * moodle/site:config check.
     *
     * @param bool $on
     * @return void
     * @throws \coding_exception if config.php fixes either setting
     */
    public static function set(bool $on): void {
        if (self::is_forced()) {
            throw new \coding_exception('Debugging is set in config.php and cannot be switched from the dashboard.');
        }

        $values = [
            'debug' => $on ? DEBUG_DEVELOPER : DEBUG_NONE,
            'debugdisplay' => $on ? 1 : 0,
        ];

        foreach ($values as $name => $value) {
            if ((string) get_config('core', $name) === (string) $value) {
                continue;
            }
            set_config($name, $value, null, true);
        }
    }
}
