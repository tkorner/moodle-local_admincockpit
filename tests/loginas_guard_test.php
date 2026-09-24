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
 * Tests for loginas_guard - the security decision behind the dashboard's
 * quick-login shortcut.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit;

/**
 * Class loginas_guard_test
 */
final class loginas_guard_test extends \advanced_testcase {
    /**
     * An admin may impersonate an ordinary user, and gets the system context
     * back to hand to \core\session\manager::loginas().
     *
     * @covers \local_admincockpit\loginas_guard::context_for
     * @return void
     */
    public function test_admin_may_impersonate_ordinary_user(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $target = $this->getDataGenerator()->create_user();

        $context = loginas_guard::context_for($target);

        $this->assertNotNull($context);
        $this->assertSame(CONTEXT_SYSTEM, $context->contextlevel);
    }

    /**
     * The one rule this plugin adds on top of core: a site admin is never a
     * valid target of the dashboard shortcut, even for another site admin -
     * core's own loginas_helper would happily allow it
     * ("Site admins can always login as someone else"), which is exactly the
     * escalation SPEC section 5 asks to rule out here.
     *
     * @covers \local_admincockpit\loginas_guard::context_for
     * @return void
     */
    public function test_site_admin_is_never_a_valid_target(): void {
        $this->resetAfterTest(true);
        global $CFG, $DB;

        $otheradmin = $this->getDataGenerator()->create_user();
        $CFG->siteadmins = $CFG->siteadmins . ',' . $otheradmin->id;

        $this->setAdminUser();
        $target = $DB->get_record('user', ['id' => $otheradmin->id], '*', MUST_EXIST);

        $this->assertTrue(is_siteadmin($target), 'fixture sanity: target must be a site admin');
        $this->assertNull(loginas_guard::context_for($target));
    }

    /**
     * Core's own rules still apply through the wrapper - a user without the
     * moodle/user:loginas capability gets nothing back.
     *
     * @covers \local_admincockpit\loginas_guard::context_for
     * @return void
     */
    public function test_user_without_capability_may_not_impersonate(): void {
        $this->resetAfterTest(true);

        $actor = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $this->setUser($actor);

        $this->assertNull(loginas_guard::context_for($target));
    }

    /**
     * Impersonating yourself is refused (core's rule, verified through the
     * wrapper so a future refactor can't silently drop it).
     *
     * @covers \local_admincockpit\loginas_guard::context_for
     * @return void
     */
    public function test_self_impersonation_is_refused(): void {
        $this->resetAfterTest(true);
        global $USER;

        $this->setAdminUser();

        $this->assertNull(loginas_guard::context_for($USER));
    }

    /**
     * A deleted account is refused.
     *
     * @covers \local_admincockpit\loginas_guard::context_for
     * @return void
     */
    public function test_deleted_user_is_refused(): void {
        $this->resetAfterTest(true);
        global $DB;

        $this->setAdminUser();
        $target = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'deleted', 1, ['id' => $target->id]);
        $target = $DB->get_record('user', ['id' => $target->id], '*', MUST_EXIST);

        $this->assertNull(loginas_guard::context_for($target));
    }

    /**
     * The control is offered to an admin but not to an ordinary user, so the
     * form never appears for someone who could not use it anyway.
     *
     * @covers \local_admincockpit\loginas_guard::is_available
     * @return void
     */
    public function test_is_available_follows_the_capability(): void {
        $this->resetAfterTest(true);

        $this->setAdminUser();
        $this->assertTrue(loginas_guard::is_available());

        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertFalse(loginas_guard::is_available());
    }
}
