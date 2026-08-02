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
 * Health signals extension hook (step 16).
 *
 * Dispatched once per dashboard render by
 * classes/output/dashboard_page.php. Any plugin - including this one, see
 * local_listener.php in this same namespace - can register a callback via
 * its own db/hooks.php and call add_signal() to contribute a tile, without
 * any coordination with this plugin's maintainer or a code change here.
 *
 * Modelled on core's own \core\hook\navigation\secondary_extend (a mutable
 * collection exposed to listeners) rather than a single-value hook such as
 * \core_course\hook\before_course_viewed - verified against Moodle 5.2 core
 * source (lib/classes/hook/, course/classes/hook/) in step 15, not guessed.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit\hook;

use local_admincockpit\health_signal;

/**
 * Collects health_signal contributions from this plugin's own built-in
 * listener and any third-party listeners registered against this hook.
 */
final class health_signals implements \core\hook\described_hook {
    /** @var health_signal[] */
    private array $signals = [];

    /**
     * Adds one signal tile to the dashboard.
     *
     * Callable any number of times by any number of listeners - order of
     * the resulting tiles is discovery/registration order here; step 18's
     * settings-driven ordering/enable-disable is applied afterwards by the
     * dashboard, not by this hook.
     *
     * @param health_signal $signal
     * @return void
     */
    public function add_signal(health_signal $signal): void {
        $this->signals[] = $signal;
    }

    /**
     * Returns every signal contributed so far.
     *
     * @return health_signal[]
     */
    public function get_signals(): array {
        return $this->signals;
    }

    /**
     * Hook purpose description in Markdown, shown on the core Hooks
     * overview page (Site administration > Development > Hooks overview).
     *
     * @return string
     */
    public static function get_hook_description(): string {
        return 'Allows plugins to add their own tile to the Admin Cockpit dashboard\'s '
            . 'health signals row. A health signal is always a number with a click-target '
            . '(never a bare statistic) - see `health_signal` for the exact contract. '
            . 'Register a callback in your own `db/hooks.php` and call `add_signal()` any '
            . 'number of times; no change to local_admincockpit itself is required.';
    }

    /**
     * List of tags that describe this hook.
     *
     * @return string[]
     */
    public static function get_hook_tags(): array {
        return ['local_admincockpit', 'dashboard'];
    }
}
