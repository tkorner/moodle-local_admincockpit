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
 * Applies the 'healthsignals' setting (step 18) to a list of health_signal
 * DTOs contributed via the health_signals hook.
 *
 * Format, one entry per line (same "plain text list" convention as the
 * existing 'navitems' setting): 'component:key' to pin a signal's position,
 * or '-component:key' to hide it. A signal never mentioned is enabled and
 * appended at the end, in hook-dispatch order - so a freshly installed
 * third-party signal (or a newly added built-in one) shows up automatically
 * without the admin having to do anything, and an existing installation's
 * empty setting changes nothing about today's behaviour.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

/**
 * Orders and filters health_signal DTOs according to the 'healthsignals'
 * admin setting.
 */
final class health_signal_ordering {
    /**
     * Builds the 'component:key' identity string for a signal.
     *
     * @param health_signal $signal
     * @return string
     */
    public static function ref(health_signal $signal): string {
        return $signal->component . ':' . $signal->key;
    }

    /**
     * Applies the setting to a list of contributed signals.
     *
     * A ref appearing in $raw that no longer matches any contributed signal
     * (e.g. that plugin was uninstalled) is silently ignored - the same
     * tolerance the existing 'activeschools' setting already has for a
     * stale, since-removed code.
     *
     * @param health_signal[] $signals as returned by
     *        \local_admincockpit\hook\health_signals::get_signals(), in
     *        hook-dispatch (registration) order
     * @param string $raw the raw 'healthsignals' setting value
     * @return health_signal[] filtered and reordered
     */
    public static function apply(array $signals, string $raw): array {
        $byref = [];
        foreach ($signals as $signal) {
            $byref[self::ref($signal)] = $signal;
        }

        $explicit = self::parse($raw);

        $result = [];
        $handled = [];
        foreach ($explicit as $entry) {
            $handled[$entry['ref']] = true;
            if ($entry['enabled'] && isset($byref[$entry['ref']])) {
                $result[] = $byref[$entry['ref']];
            }
        }

        foreach ($signals as $signal) {
            if (!isset($handled[self::ref($signal)])) {
                $result[] = $signal;
            }
        }

        return $result;
    }

    /**
     * Parses the raw setting into an ordered list of
     * ['ref' => string, 'enabled' => bool] entries.
     *
     * @param string $raw
     * @return array
     */
    public static function parse(string $raw): array {
        $entries = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $enabled = true;
            if ($line[0] === '-') {
                $enabled = false;
                $line = trim(substr($line, 1));
            }

            if ($line === '') {
                continue;
            }

            $entries[] = ['ref' => $line, 'enabled' => $enabled];
        }
        return $entries;
    }
}
