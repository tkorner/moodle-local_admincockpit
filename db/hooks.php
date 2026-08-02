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
 * Hook callbacks for local_admincockpit.
 *
 * This plugin registers a callback for its OWN health_signals hook, the
 * same way any third-party plugin would - the four built-in signals are
 * just the first consumer of the extension point, not a special case.
 * See classes/hook/health_signals.php and classes/hook/local_listener.php.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \local_admincockpit\hook\health_signals::class,
        'callback' => [\local_admincockpit\hook\local_listener::class, 'add_builtin_signals'],
        'priority' => 0,
    ],
];
