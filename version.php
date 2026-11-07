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
 * Version file.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_admincockpit';
$plugin->version   = 2026110800;   // YYYYMMDDXX.
$plugin->requires  = 2026042000;   // Moodle 5.2.0 branching version (MOODLE_502_STABLE) - supports 5.2 to 5.3.
// 5.1 support dropped 2026-10-22: no longer developed or tested against. 503 is covered by CI
// (MOODLE_503_STABLE) and was verified by hand against the 5.3 stable release (v5.3.0, 2026100500).
$plugin->supported = [502, 503];   // Range (inclusive), not a list - see lib/upgrade.txt's $plugin->supported example.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '2.7.0';
