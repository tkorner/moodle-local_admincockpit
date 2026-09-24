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
 * This plugin's own listener for its own health_signals hook (step 16).
 *
 * Registered in db/hooks.php like any third-party listener would be - the
 * four built-in signals are not a special case the dashboard hardcodes, they
 * are simply the first (and default-enabled) consumer of the extension
 * point. The severity/value/URL decisions made here were moved unchanged
 * from classes/output/dashboard_page.php's former export_health_signals()/
 * format_security_value()/format_cron_value()/cron_severity() - this class
 * is a refactor extracting existing logic, not new business logic. The
 * underlying computations in classes/metrics/health_signals.php are
 * themselves untouched.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit\hook;

use local_admincockpit\health_signal;
use local_admincockpit\metrics\health_signals as health_signal_metrics;

/**
 * Wraps this plugin's own built-in signals into health_signal DTOs: the four
 * from SPEC section 4, plus the ones added later from the section 11 backlog
 * (unpublished courses, expired enrolments, self-enrolment risks).
 */
final class local_listener {
    /**
     * db/hooks.php callback target.
     *
     * @param health_signals $hook
     * @return void
     */
    public static function add_builtin_signals(health_signals $hook): void {
        $hook->add_signal(self::duplicate_emails_signal());
        $hook->add_signal(self::courses_without_enddate_signal());
        $hook->add_signal(self::unpublished_courses_signal());
        $hook->add_signal(self::expired_enrolments_signal());
        $hook->add_signal(self::self_enrolment_risks_signal());
        $hook->add_signal(self::security_overview_signal());
        $hook->add_signal(self::cron_status_signal());
    }

    /**
     * Duplicate email addresses.
     *
     * @return health_signal
     */
    private static function duplicate_emails_signal(): health_signal {
        $duplicates = health_signal_metrics::duplicate_emails();

        return new health_signal(
            component: 'local_admincockpit',
            key: 'duplicateemails',
            label: get_string('duplicateemails', 'local_admincockpit'),
            value: $duplicates->count,
            severity: $duplicates->count > 0 ? 'warning' : 'ok',
            url: '/local/admincockpit/duplicateemails.php',
        );
    }

    /**
     * Courses without an end date.
     *
     * @return health_signal
     */
    private static function courses_without_enddate_signal(): health_signal {
        $noenddate = health_signal_metrics::courses_without_enddate();

        return new health_signal(
            component: 'local_admincockpit',
            key: 'courseswithoutenddate',
            label: get_string('courseswithoutenddate', 'local_admincockpit'),
            value: $noenddate->count,
            severity: $noenddate->count > 0 ? 'warning' : 'ok',
            url: '/local/admincockpit/courseswithoutenddate.php',
        );
    }

    /**
     * Courses hidden longer than the configured threshold (step 19 -
     * the plugin's own first dogfood signal added through the hook
     * mechanism, proving it works for real, not just this plugin's original
     * four signals).
     *
     * @return health_signal
     */
    private static function unpublished_courses_signal(): health_signal {
        $days = (int) (get_config('local_admincockpit', 'unpublishedcoursedays') ?: 90);
        $unpublished = health_signal_metrics::unpublished_courses($days);

        return new health_signal(
            component: 'local_admincockpit',
            key: 'unpublishedcourses',
            label: get_string('unpublishedcourses', 'local_admincockpit'),
            value: $unpublished->count,
            severity: $unpublished->count > 0 ? 'warning' : 'ok',
            url: '/local/admincockpit/unpublishedcourses.php',
        );
    }

    /**
     * Active enrolments whose end date has already passed (SPEC section 11
     * backlog, implemented 2026-09-24).
     *
     * @return health_signal
     */
    private static function expired_enrolments_signal(): health_signal {
        $expired = health_signal_metrics::expired_enrolments();

        return new health_signal(
            component: 'local_admincockpit',
            key: 'expiredenrolments',
            label: get_string('expiredenrolments', 'local_admincockpit'),
            value: $expired->count,
            severity: $expired->count > 0 ? 'warning' : 'ok',
            url: '/local/admincockpit/expiredenrolments.php',
        );
    }

    /**
     * Self-enrolment methods with no key and/or no end date (SPEC section 11
     * backlog, implemented 2026-09-24).
     *
     * @return health_signal
     */
    private static function self_enrolment_risks_signal(): health_signal {
        $risks = health_signal_metrics::self_enrolment_risks();

        return new health_signal(
            component: 'local_admincockpit',
            key: 'selfenrolrisks',
            label: get_string('selfenrolrisks', 'local_admincockpit'),
            value: $risks->count,
            severity: $risks->count > 0 ? 'warning' : 'ok',
            url: '/local/admincockpit/selfenrolrisks.php',
        );
    }

    /**
     * Security overview traffic light.
     *
     * @return health_signal
     */
    private static function security_overview_signal(): health_signal {
        global $OUTPUT;

        $security = health_signal_metrics::security_overview_summary();

        return new health_signal(
            component: 'local_admincockpit',
            key: 'security',
            label: get_string('signal_security', 'local_admincockpit'),
            value: self::format_security_value($security),
            severity: $security->error > 0 ? 'error' : ($security->warning > 0 ? 'warning' : 'ok'),
            url: '/report/security/index.php',
            helpicon: $OUTPUT->help_icon('signal_security', 'local_admincockpit'),
        );
    }

    /**
     * Cron status.
     *
     * @return health_signal
     */
    private static function cron_status_signal(): health_signal {
        global $OUTPUT;

        $cron = health_signal_metrics::cron_status();

        return new health_signal(
            component: 'local_admincockpit',
            key: 'cron',
            label: get_string('signal_cron', 'local_admincockpit'),
            value: self::format_cron_value($cron),
            severity: self::cron_severity($cron),
            url: '/admin/tool/task/scheduledtasks.php',
            valuetitle: $cron->lastrunat > 0 ? userdate($cron->lastrunat) : '',
            helpicon: $OUTPUT->help_icon('signal_cron', 'local_admincockpit'),
        );
    }

    /**
     * Formats the security overview health signal's display value as a
     * single compact line, e.g. "15 OK · 4 Warnungen" - error is only shown
     * when non-zero (0 errors is the expected, unremarkable case); ok and
     * warning are always shown since either could legitimately be 0.
     *
     * Moved unchanged from classes/output/dashboard_page.php.
     *
     * @param \stdClass $security as returned by
     *        health_signals::security_overview_summary()
     * @return string
     */
    private static function format_security_value(\stdClass $security): string {
        $parts = [
            get_string('signal_security_ok', 'local_admincockpit', $security->ok),
            get_string('signal_security_warning', 'local_admincockpit', $security->warning),
        ];
        if ($security->error > 0) {
            $parts[] = get_string('signal_security_error', 'local_admincockpit', $security->error);
        }

        return implode(' · ', $parts);
    }

    /**
     * Formats the cron health signal's display value.
     *
     * Moved unchanged from classes/output/dashboard_page.php.
     *
     * @param \stdClass $cron as returned by health_signals::cron_status()
     * @return string
     */
    private static function format_cron_value(\stdClass $cron): string {
        $lastrun = $cron->lastrunat > 0
            ? get_string('signal_cron_lastrun', 'local_admincockpit', format_time(time() - $cron->lastrunat))
            : get_string('signal_cron_neverrun', 'local_admincockpit');

        return $lastrun . ' ' . get_string('signal_cron_failedtasks', 'local_admincockpit', $cron->failedtasks24h);
    }

    /**
     * Decides the cron tile's severity.
     *
     * Moved unchanged from classes/output/dashboard_page.php.
     *
     * @param \stdClass $cron as returned by health_signals::cron_status()
     * @return string 'ok', 'warning', or 'error'
     */
    private static function cron_severity(\stdClass $cron): string {
        global $CFG;

        if ($cron->failedtasks24h > 0 || $cron->lastrunat === 0) {
            return 'error';
        }

        $expectedfrequency = $CFG->expectedcronfrequency ?? MINSECS;
        if ((time() - $cron->lastrunat) > $expectedfrequency + MINSECS) {
            return 'warning';
        }

        return 'ok';
    }
}
