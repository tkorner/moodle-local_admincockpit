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
 * Decides whether the dashboard's quick-login shortcut may impersonate a
 * given user (SPEC section 5, "One-click impersonate").
 *
 * Deliberately a separate, page-free class rather than logic inside
 * index.php: this is the security decision the whole quick action rests on,
 * so it has to be unit-testable on its own, the same split classes/metrics/*
 * already uses against classes/output/*.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

use core\context;
use core\session\loginas_helper;

/**
 * "May the current user log in as this user from the dashboard?"
 */
final class loginas_guard {
    /**
     * Returns the context the current user may impersonate $targetuser in,
     * or null if they may not.
     *
     * The bulk of the decision is core's own
     * \core\session\loginas_helper::get_context_user_can_login_as(), which
     * is exactly what core's course/loginas.php uses - reusing it means the
     * rules about deleted accounts, self-impersonation, already being logged
     * in as someone, and the moodle/user:loginas capability stay core's
     * business, not a second implementation that could drift from it.
     *
     * One restriction is added on top: core deliberately lets a site admin
     * log in as anybody, *including another site admin*
     * ("Site admins can always login as someone else"). SPEC section 5 asks
     * for the stricter rule here - a one-click shortcut sitting on a
     * dashboard is a different risk profile from core's deliberate,
     * multi-step flow, so a site admin is never a valid target of this
     * particular action, no matter who asks.
     *
     * @param \stdClass $targetuser the user record to be impersonated
     * @return context|null the context to pass to
     *         \core\session\manager::loginas(), or null if not permitted
     */
    public static function context_for(\stdClass $targetuser): ?context {
        global $USER;

        if (is_siteadmin($targetuser)) {
            return null;
        }

        return loginas_helper::get_context_user_can_login_as($USER, $targetuser);
    }

    /**
     * Whether the quick-login control should be offered to the current user
     * at all - used to keep the form off the dashboard entirely for someone
     * who could never use it.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return !\core\session\manager::is_loggedinas()
            && has_capability('moodle/user:loginas', \core\context\system::instance());
    }
}
