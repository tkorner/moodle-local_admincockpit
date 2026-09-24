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
 * Health signals for the admin cockpit (SPEC section 4).
 *
 * security_overview_summary() and cron_status() deliberately reuse existing
 * core infrastructure instead of re-implementing checks:
 * - Security checks: \core\check\manager::get_checks('security'), the same
 *   API report_security/index.php itself uses (new core\check\table(...)).
 *   Core defines 7 granular statuses (result::OK/INFO/NA/UNKNOWN/WARNING/
 *   ERROR/CRITICAL) but no 3-bucket "ok/warning/error" grouping, so this
 *   class provides that mapping itself (see bucket_for_status()) - OK/INFO/NA
 *   count as ok, WARNING/UNKNOWN as warning (core's own docblock for
 *   UNKNOWN literally recommends treating it as a warning), ERROR/CRITICAL
 *   as error.
 * - Cron status: admin/tool/task/classes/check/cronrunning.php (core's own
 *   "is cron running" check) shows the canonical last-run source is
 *   get_config('tool_task', 'lastcronstart'), not a derived value from
 *   task_log - a task_log MAX(timeend) would be misleading if cron ran but
 *   no task happened to be due. Failed-task counting uses task_log.result
 *   (0 = pass, 1 = fail per its install.xml comment).
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_admincockpit\metrics;

/**
 * Data hygiene and infrastructure health signals for the admin cockpit.
 */
class health_signals {
    /** @var int Hard cap on courses_without_enddate()'s drill-down rows - enddate=0 is Moodle's own
     *  course default, so on a site that never adopted end dates this signal could otherwise match
     *  every course; count stays exact, only the detail list (and its cached HTML rendering cost) is
     *  capped. See details_truncated on the returned object.
     */
    private const COURSESWITHOUTENDDATE_MAXDETAILS = 500;

    /** @var int Hard cap on the number of distinct duplicate-email groups whose member rows get
     *  fetched for duplicate_emails()'s drill-down - keeps the get_in_or_equal() list (and thus the
     *  number of bound query parameters) bounded even on a site with an unusually large number of
     *  duplicate groups. The group count itself stays exact.
     */
    private const DUPLICATEEMAILS_MAXGROUPS = 500;

    /** @var int Hard cap on unpublished_courses()'s drill-down rows, same rationale as
     *  COURSESWITHOUTENDDATE_MAXDETAILS - count stays exact, only the detail list is capped.
     */
    private const UNPUBLISHEDCOURSES_MAXDETAILS = 500;

    /** @var int Hard cap on expired_enrolments()'s drill-down rows, same rationale as
     *  COURSESWITHOUTENDDATE_MAXDETAILS - count stays exact, only the detail list is capped.
     */
    private const EXPIREDENROLMENTS_MAXDETAILS = 500;

    /** @var int Hard cap on self_enrolment_risks()'s drill-down rows, same rationale as
     *  COURSESWITHOUTENDDATE_MAXDETAILS - count stays exact, only the detail list is capped.
     */
    private const SELFENROLRISKS_MAXDETAILS = 500;

    /**
     * Finds email addresses shared by more than one non-deleted, locally
     * managed, non-guest user account.
     *
     * "Aktives Nutzerkonto" here uses the same convention as user_metrics:
     * not deleted, not guest, not a remote MNet account - see that class's
     * docblock for the rationale. Matching is case-insensitive (Foo@x.ch and
     * foo@x.ch are the same mailbox, which is exactly what this signal exists
     * to catch before a merge-users pass).
     *
     * @return \stdClass with count (number of duplicated email addresses,
     *         exact), details (array of stdClass: userid, email, fullname -
     *         one row per affected user, for the drill-down list, capped at
     *         DUPLICATEEMAILS_MAXGROUPS groups), detailsgrouptruncated (bool),
     *         and computedat (unix timestamp of when this was last computed,
     *         not necessarily this request - see db/caches.php, 1 day TTL)
     */
    public static function duplicate_emails(): \stdClass {
        return self::from_cache('duplicateemails', [self::class, 'compute_duplicate_emails']);
    }

    /**
     * Computes the duplicate_emails() signal.
     *
     * @return \stdClass with count, details, and detailsgrouptruncated
     */
    private static function compute_duplicate_emails(): \stdClass {
        global $DB, $CFG;

        // Plain LOWER() rather than a $DB->sql_*() wrapper - verified there is no such wrapper in core's
        // moodle_database API, and core's own authenticate_user_login() (lib/moodlelib.php) uses this
        // exact "LOWER(email) = LOWER(:email)" idiom directly for the same case-insensitive email
        // matching problem, so it's portable across the DB drivers Moodle supports.
        $emaillower = 'LOWER(email)';
        $sql = "SELECT {$emaillower} AS emaillower, COUNT(*) AS usercount
                  FROM {user}
                 WHERE deleted = 0
                   AND id != :guestid
                   AND mnethostid = :mnethostid
                   AND email != ''
              GROUP BY {$emaillower}
                HAVING COUNT(*) > 1";
        $params = ['guestid' => $CFG->siteguest, 'mnethostid' => $CFG->mnet_localhost_id];
        $duplicategroups = $DB->get_records_sql($sql, $params);

        $result = new \stdClass();
        $result->details = [];
        $result->count = count($duplicategroups);

        $groupstoshow = array_slice(array_keys($duplicategroups), 0, self::DUPLICATEEMAILS_MAXGROUPS);
        $result->detailsgrouptruncated = count($duplicategroups) > count($groupstoshow);

        if (!empty($groupstoshow)) {
            $namefields = implode(', ', \core_user\fields::get_name_fields());
            [$emailsql, $emailparams] = $DB->get_in_or_equal($groupstoshow, SQL_PARAMS_NAMED, 'email');
            $sql = "SELECT id, email, {$namefields}
                      FROM {user}
                     WHERE deleted = 0
                       AND id != :guestid
                       AND mnethostid = :mnethostid
                       AND {$emaillower} {$emailsql}
                  ORDER BY {$emaillower}, id";
            $params = array_merge(
                ['guestid' => $CFG->siteguest, 'mnethostid' => $CFG->mnet_localhost_id],
                $emailparams
            );
            $users = $DB->get_records_sql($sql, $params);
            foreach ($users as $user) {
                $result->details[] = (object) [
                    'userid' => $user->id,
                    'email' => $user->email,
                    'fullname' => fullname($user),
                ];
            }
        }

        return $result;
    }

    /**
     * Finds courses with enddate = 0.
     *
     * The join against course_categories naturally excludes the site course
     * (category = 0, which has no matching category row) - intentional,
     * not an oversight: the front page is not a "course without an enddate"
     * in any actionable sense for this signal.
     *
     * @return \stdClass with count (exact, uncapped), details (array of
     *         stdClass: courseid, fullname, categoryid, categoryname -
     *         capped at COURSESWITHOUTENDDATE_MAXDETAILS rows), detailstruncated
     *         (bool - whether count exceeds the number of detail rows returned),
     *         and computedat (see db/caches.php, 1 day TTL)
     */
    public static function courses_without_enddate(): \stdClass {
        return self::from_cache('courseswithoutenddate', [self::class, 'compute_courses_without_enddate']);
    }

    /**
     * Computes the courses_without_enddate() signal.
     *
     * @return \stdClass with count, details, and detailstruncated
     */
    private static function compute_courses_without_enddate(): \stdClass {
        global $DB;

        $fromwhere = "FROM {course} c
                       JOIN {course_categories} cc ON cc.id = c.category
                      WHERE c.enddate = 0";

        $count = $DB->count_records_sql("SELECT COUNT(*) {$fromwhere}");

        $courses = $DB->get_records_sql(
            "SELECT c.id, c.fullname, cc.id AS categoryid, cc.name AS categoryname {$fromwhere}
              ORDER BY c.fullname",
            null,
            0,
            self::COURSESWITHOUTENDDATE_MAXDETAILS
        );

        $result = new \stdClass();
        $result->details = [];
        foreach ($courses as $course) {
            $result->details[] = (object) [
                'courseid' => $course->id,
                'fullname' => $course->fullname,
                'categoryid' => $course->categoryid,
                'categoryname' => $course->categoryname,
            ];
        }
        $result->count = $count;
        $result->detailstruncated = $count > count($result->details);

        return $result;
    }

    /**
     * Finds courses hidden (visible = 0) for longer than $days.
     *
     * Deliberately NOT a bare visible = 0 check (step 19): hiding a course
     * while still preparing it is completely normal, intentional behaviour,
     * not a health problem - flagging every hidden course would be exactly
     * the kind of false-positive-prone signal already rejected once for
     * "auth mismatch" (see SPEC section 11). Pairing the visibility check
     * with an age condition (created more than $days ago) targets actually-
     * abandoned drafts instead.
     *
     * The join against course_categories naturally excludes the site course
     * (category = 0), same reasoning as courses_without_enddate() above.
     *
     * @param int $days a course must have been created at least this many
     *        days ago to count (local_admincockpit/unpublishedcoursedays
     *        setting)
     * @return \stdClass with count (exact, uncapped), details (array of
     *         stdClass: courseid, fullname, categoryid, categoryname -
     *         capped at UNPUBLISHEDCOURSES_MAXDETAILS rows), detailstruncated
     *         (bool), and computedat (see db/caches.php, 1 day TTL)
     */
    public static function unpublished_courses(int $days): \stdClass {
        return self::from_cache(
            'unpublishedcourses_' . $days,
            fn () => self::compute_unpublished_courses($days)
        );
    }

    /**
     * Computes the unpublished_courses() signal.
     *
     * @param int $days
     * @return \stdClass with count, details, and detailstruncated
     */
    private static function compute_unpublished_courses(int $days): \stdClass {
        global $DB;

        $fromwhere = "FROM {course} c
                       JOIN {course_categories} cc ON cc.id = c.category
                      WHERE c.visible = 0 AND c.timecreated <= :since";
        $params = ['since' => time() - ($days * DAYSECS)];

        $count = $DB->count_records_sql("SELECT COUNT(*) {$fromwhere}", $params);

        $courses = $DB->get_records_sql(
            "SELECT c.id, c.fullname, cc.id AS categoryid, cc.name AS categoryname {$fromwhere}
              ORDER BY c.fullname",
            $params,
            0,
            self::UNPUBLISHEDCOURSES_MAXDETAILS
        );

        $result = new \stdClass();
        $result->details = [];
        foreach ($courses as $course) {
            $result->details[] = (object) [
                'courseid' => $course->id,
                'fullname' => $course->fullname,
                'categoryid' => $course->categoryid,
                'categoryname' => $course->categoryname,
            ];
        }
        $result->count = $count;
        $result->detailstruncated = $count > count($result->details);

        return $result;
    }

    /**
     * Finds active user enrolments whose end date has already passed.
     *
     * "Active" and "has an end date" both use core's own conventions
     * (verified against lib/enrollib.php rather than assumed): status = 0 is
     * ENROL_USER_ACTIVE, and timeend = 0 means "no end date" - the same
     * default enrol_plugin::enrol_user() itself uses, and the same idiom
     * core's own enrolment-expiry-warning query (enrol_send_expiry_notifications())
     * checks against (timeend > 0 AND ...). A suspended enrolment or one with
     * no end date at all is not a problem this signal is about.
     *
     * @return \stdClass with count (exact, uncapped), details (array of
     *         stdClass: userid, fullname, courseid, coursefullname, timeend -
     *         capped at EXPIREDENROLMENTS_MAXDETAILS rows, oldest expiry
     *         first), detailstruncated (bool), and computedat (see
     *         db/caches.php, 1 day TTL)
     */
    public static function expired_enrolments(): \stdClass {
        return self::from_cache('expiredenrolments', [self::class, 'compute_expired_enrolments']);
    }

    /**
     * Computes the expired_enrolments() signal.
     *
     * @return \stdClass with count, details, and detailstruncated
     */
    private static function compute_expired_enrolments(): \stdClass {
        global $DB, $CFG;

        $fromwhere = "FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid
                       JOIN {course} c ON c.id = e.courseid
                       JOIN {user} u ON u.id = ue.userid
                      WHERE ue.status = :active
                        AND ue.timeend > 0
                        AND ue.timeend < :now
                        AND u.deleted = 0
                        AND u.id != :guestid
                        AND u.mnethostid = :mnethostid";
        $params = [
            'active' => ENROL_USER_ACTIVE,
            'now' => time(),
            'guestid' => $CFG->siteguest,
            'mnethostid' => $CFG->mnet_localhost_id,
        ];

        $count = $DB->count_records_sql("SELECT COUNT(*) {$fromwhere}", $params);

        $namefields = implode(', ', array_map(
            fn ($field) => "u.{$field}",
            \core_user\fields::get_name_fields()
        ));
        $rows = $DB->get_records_sql(
            "SELECT ue.id, u.id AS userid, {$namefields}, c.id AS courseid, c.fullname AS coursefullname,
                    ue.timeend
               {$fromwhere}
           ORDER BY ue.timeend ASC",
            $params,
            0,
            self::EXPIREDENROLMENTS_MAXDETAILS
        );

        $result = new \stdClass();
        $result->details = [];
        foreach ($rows as $row) {
            $result->details[] = (object) [
                'userid' => $row->userid,
                'fullname' => fullname($row),
                'courseid' => $row->courseid,
                'coursefullname' => $row->coursefullname,
                'timeend' => (int) $row->timeend,
            ];
        }
        $result->count = $count;
        $result->detailstruncated = $count > count($result->details);

        return $result;
    }

    /**
     * Finds enabled self-enrolment methods with no enrolment key, no end
     * date, or both - either weakness alone leaves a course open to
     * uncontrolled self-enrolment, so this flags on either one (an OR, not
     * an AND); the per-row 'nokey'/'noenddate' flags in the details tell the
     * admin which one(s) actually apply so the ambiguity in SPEC section 11's
     * one-line backlog note ("without a key/without an end date") doesn't
     * need resolving up front.
     *
     * Field semantics verified against enrol/self/lib.php rather than
     * assumed: an empty/null password means "no key required"
     * (can_self_enrol() checks `if ($instance->password)`), and
     * enrolenddate = 0 means "no end date"
     * (`if ($instance->enrolenddate != 0 and ...)`) - the same convention
     * user_enrolments.timeend uses in expired_enrolments() above.
     *
     * Counts enrolment *instances*, not courses - a course can have more
     * than one self-enrolment method, and each is independently a risk.
     *
     * @return \stdClass with count (exact, uncapped), details (array of
     *         stdClass: courseid, coursefullname, nokey, noenddate - capped
     *         at SELFENROLRISKS_MAXDETAILS rows), detailstruncated (bool),
     *         and computedat (see db/caches.php, 1 day TTL)
     */
    public static function self_enrolment_risks(): \stdClass {
        return self::from_cache('selfenrolrisks', [self::class, 'compute_self_enrolment_risks']);
    }

    /**
     * Computes the self_enrolment_risks() signal.
     *
     * @return \stdClass with count, details, and detailstruncated
     */
    private static function compute_self_enrolment_risks(): \stdClass {
        global $DB;

        $fromwhere = "FROM {enrol} e
                       JOIN {course} c ON c.id = e.courseid
                      WHERE e.enrol = :selfenrol
                        AND e.status = :enabled
                        AND (e.password IS NULL OR e.password = :emptykey OR e.enrolenddate = 0)";
        $params = ['selfenrol' => 'self', 'enabled' => ENROL_INSTANCE_ENABLED, 'emptykey' => ''];

        $count = $DB->count_records_sql("SELECT COUNT(*) {$fromwhere}", $params);

        $instances = $DB->get_records_sql(
            "SELECT e.id, c.id AS courseid, c.fullname AS coursefullname, e.password, e.enrolenddate
               {$fromwhere}
           ORDER BY c.fullname",
            $params,
            0,
            self::SELFENROLRISKS_MAXDETAILS
        );

        $result = new \stdClass();
        $result->details = [];
        foreach ($instances as $instance) {
            $result->details[] = (object) [
                'courseid' => $instance->courseid,
                'coursefullname' => $instance->coursefullname,
                'nokey' => empty($instance->password),
                'noenddate' => (int) $instance->enrolenddate === 0,
            ];
        }
        $result->count = $count;
        $result->detailstruncated = $count > count($result->details);

        return $result;
    }

    /**
     * Aggregates the core security overview checks into a 3-state traffic
     * light. Reuses \core\check\manager - the same API report_security's own
     * page uses - rather than re-implementing any of the checks.
     *
     * @return \stdClass with ok, warning, error, and computedat (see
     *         db/caches.php, 1 day TTL)
     */
    public static function security_overview_summary(): \stdClass {
        return self::from_cache('securityoverview', [self::class, 'compute_security_overview_summary']);
    }

    /**
     * Computes the security_overview_summary() signal.
     *
     * @return \stdClass with ok, warning, and error counts
     */
    private static function compute_security_overview_summary(): \stdClass {
        $result = new \stdClass();
        $result->ok = 0;
        $result->warning = 0;
        $result->error = 0;

        $checks = \core\check\manager::get_checks('security');
        foreach ($checks as $check) {
            $bucket = self::bucket_for_status($check->get_result()->get_status());
            $result->$bucket++;
        }

        return $result;
    }

    /**
     * Maps a core\check\result status onto this dashboard's 3-state
     * ok/warning/error traffic light.
     *
     * @param string $status one of the \core\check\result::* constants
     * @return string 'ok', 'warning', or 'error'
     */
    private static function bucket_for_status(string $status): string {
        switch ($status) {
            case \core\check\result::OK:
            case \core\check\result::INFO:
            case \core\check\result::NA:
                return 'ok';
            case \core\check\result::WARNING:
            case \core\check\result::UNKNOWN:
                return 'warning';
            case \core\check\result::ERROR:
            case \core\check\result::CRITICAL:
                return 'error';
            default:
                // Fail safe: an unrecognised status is surfaced as an error rather than silently hidden.
                return 'error';
        }
    }

    /**
     * Reads the core scheduled-task infrastructure for the last cron run
     * and recent task failures.
     *
     * Cached the same as the other three signals (1 day TTL, see
     * db/caches.php) for consistency, even though cron health is arguably
     * exactly the kind of thing you don't want to look a day stale - the
     * "Cache now leeren" button on the dashboard (classes/output/
     * dashboard_page.php) is the escape hatch for that.
     *
     * @return \stdClass with lastrunat (unix timestamp, 0 if cron has never
     *         run), failedtasks24h (count of task_log rows with result = 1
     *         in the last 24 hours), and computedat
     */
    public static function cron_status(): \stdClass {
        return self::from_cache('cronstatus', [self::class, 'compute_cron_status']);
    }

    /**
     * Computes the cron_status() signal.
     *
     * @return \stdClass with lastrunat and failedtasks24h
     */
    private static function compute_cron_status(): \stdClass {
        global $DB;

        $result = new \stdClass();
        $result->lastrunat = (int) get_config('tool_task', 'lastcronstart');
        $result->failedtasks24h = $DB->count_records_select(
            'task_log',
            'result = :fail AND timestart >= :since',
            ['fail' => 1, 'since' => time() - DAYSECS]
        );

        return $result;
    }

    /**
     * Shared cache-or-compute helper for the four signal methods above.
     *
     * @param string $cachekey
     * @param callable $computer takes no arguments, returns a \stdClass
     * @return \stdClass the cached or freshly computed value, with
     *         computedat set
     */
    private static function from_cache(string $cachekey, callable $computer): \stdClass {
        $cache = \core_cache\cache::make('local_admincockpit', 'dashboarddata');

        $cached = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }

        $result = $computer();
        $result->computedat = time();
        $cache->set($cachekey, $result);
        return $result;
    }
}
