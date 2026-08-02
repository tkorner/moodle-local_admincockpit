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
 * Health signal value object (extension point, step 14).
 *
 * Deliberately a plain data carrier, not aware of rendering (badge markup,
 * CSS classes) or of how it was computed - the same architectural split as
 * classes/metrics/* vs. classes/output/* elsewhere in this plugin. This is
 * the shape both this plugin's own built-in signals AND any third-party
 * hook listener (see classes/hook/health_signals.php, step 16) produce, so
 * classes/output/dashboard_page.php can treat every signal identically
 * regardless of who contributed it.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

/**
 * One health signal tile's data: a number with a click-target, never a bare
 * statistic (see CLAUDE.md "Core architectural principles").
 */
final class health_signal {
    /**
     * @param string $component frankenstyle component of whoever produced
     *        this signal, e.g. 'local_admincockpit' for the built-in ones -
     *        together with $key this is the stable identity used by the
     *        enable/disable/ordering setting (step 18)
     * @param string $key identifier unique within $component, e.g.
     *        'duplicateemails', 'cron'
     * @param string $label already-translated display label
     * @param int|string $value the tile's displayed value
     * @param string $severity one of 'ok', 'warning', 'error'
     * @param string $url site-relative or absolute click-target
     * @param string $valuetitle optional tooltip/title attribute for the value
     * @param string $helpicon optional pre-rendered help icon HTML (see
     *        $OUTPUT->help_icon())
     */
    public function __construct(
        public readonly string $component,
        public readonly string $key,
        public readonly string $label,
        public readonly int|string $value,
        public readonly string $severity,
        public readonly string $url,
        public readonly string $valuetitle = '',
        public readonly string $helpicon = '',
    ) {
    }
}
