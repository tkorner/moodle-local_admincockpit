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
 * Strings for component 'local_admincockpit', language 'en'.
 *
 * @package   local_admincockpit
 * @copyright 2026 Thomas Korner <thomas.korner@edu.zh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activeschools'] = 'Active {$a} codes';
$string['activeschools_desc'] = 'Only fully matched codes (cohort and top-level category share the same idnumber) can be selected here. They are shown on the dashboard.';
$string['activeschools_option'] = '{$a->idnumber} ({$a->cohortname} / {$a->categoryname})';
$string['activeusers'] = 'Active';
$string['activeusers_help'] = 'Accounts with a site access (lastaccess) within the time range selected at the top of the page. Change the dropdown above to adjust it.';
$string['activeusers_link'] = 'https://github.com/tkorner/moodle-local_admincockpit';
$string['admincockpit:view'] = 'View the admin cockpit';
$string['allcachespurged'] = 'All site caches cleared (class map, theme, strings, and more) - not just this plugin\'s own cached numbers.';
$string['backtodashboard'] = 'Back to dashboard';
$string['boostunionsettings'] = 'Boost Union theme settings';
$string['cachedef_dashboarddata'] = 'Computed dashboard metrics (user/school metrics, health signals)';
$string['cachepurged'] = 'Cache cleared - the numbers below are freshly computed.';
$string['courseswithoutenddate'] = 'Courses without an end date';
$string['courseswithoutenddate_none'] = 'No courses without an end date - nothing to report here.';
$string['courseswithoutenddate_truncated'] = 'Showing the first 500 of {$a} courses without an end date.';
$string['dashboardintro'] = 'Overview of user and course activity across the whole site and per configured {$a}, plus data-hygiene and infrastructure signals that need attention. Click any health signal tile to see the underlying list or report.';
$string['duplicateemails'] = 'Duplicate email addresses';
$string['duplicateemails_none'] = 'No duplicate email addresses found - nothing to report here.';
$string['duplicateemails_truncated'] = 'Showing the first 500 of {$a} duplicate-email groups.';
$string['eventdashboardviewed'] = 'Viewed admin cockpit';
$string['expiredenrolments'] = 'Expired enrolments';
$string['expiredenrolments_expiredon'] = 'Expired on';
$string['expiredenrolments_intro'] = 'Enrolments that are still active but whose end date has already passed. Depending on the enrolment method, these are not always cleaned up automatically - open the course participants list to extend or remove them.';
$string['expiredenrolments_none'] = 'No expired enrolments found - nothing to report here.';
$string['expiredenrolments_truncated'] = 'Showing the first 500 of {$a} expired enrolments.';
$string['groupinglabel'] = 'Grouping label';
$string['groupinglabel_desc'] = 'What to call a "grouping" (a cohort and a top-level category sharing the same idnumber) in the dashboard UI - e.g. School, Site, Department, or Faculty. Purely cosmetic: it only changes wording, never how groupings are matched or selected.';
$string['healthsignals'] = 'Health signal order / visibility';
$string['healthsignals_available'] = 'This plugin\'s own built-in signals: local_admincockpit:duplicateemails, local_admincockpit:courseswithoutenddate, local_admincockpit:unpublishedcourses, local_admincockpit:expiredenrolments, local_admincockpit:selfenrolrisks, local_admincockpit:security, local_admincockpit:cron. A third-party plugin\'s signal uses its own component name instead of local_admincockpit - check the dashboard itself, or that plugin\'s documentation, for its exact key.';
$string['healthsignals_desc'] = 'One entry per line, using the "component:key" identifiers listed above. A listed entry pins that signal\'s position (top to bottom); prefix with "-" to hide it entirely (e.g. "-local_admincockpit:security"). A signal never mentioned here stays enabled and is shown after the listed ones, in the order it was contributed - so leaving this empty keeps every currently available signal visible, and any newly installed third-party signal shows up automatically without editing this setting.';
$string['lastcomputed'] = 'As of: {$a}, updates daily.';
$string['mergeusershint'] = 'This list is a starting point for identifying accounts to merge. The "Merge user accounts" admin tool (tool_mergeusers) is not installed on this instance, so no direct link is shown here - install it to actually merge two accounts.';
$string['mergeusershint_link'] = 'This list is a starting point for identifying accounts to merge. Use {$a} to actually merge two accounts.';
$string['mergeuserslinktext'] = 'Merge user accounts';
$string['navgroup_courses'] = 'Course management';
$string['navgroup_reports'] = 'Reports/Logs';
$string['navgroup_system'] = 'System';
$string['navgroup_theme'] = 'Theme/Appearance';
$string['navgroup_users'] = 'User management';
$string['navitems'] = 'Navigation links';
$string['navitems_desc'] = 'One link per line: Title|URL|Group|Capability(optional). Group is the card heading it appears under (cards are shown in the order their group name first appears here). URL can be a site-relative path (e.g. /cohort/index.php) or a full external URL, and must be http(s) - other schemes (e.g. javascript:) are rejected. If a capability is given (e.g. moodle/site:config), it must be a real, existing capability, and the link is only shown to users who have it in the system context; leave it out to show the link to everyone who can see this dashboard. Lines that cannot be parsed are skipped - see the warning above if any are.';
$string['navitems_parseerror'] = '{$a} line(s) in the navigation links setting below could not be parsed and were skipped. Each line needs 3 or 4 non-empty "|"-separated parts: Title|URL|Group|Capability(optional).';
$string['newinperiod'] = 'New in period';
$string['newinperiod_help'] = 'Counts records (user accounts, cohort members, or courses) created/added within the time range selected at the top of the page. Change the dropdown above to adjust it.';
$string['newinperiod_link'] = 'https://github.com/tkorner/moodle-local_admincockpit';
$string['nonavitemsconfigured'] = 'No navigation links are configured.';
$string['noschoolsconfigured'] = '0 active {$a} codes are configured.';
$string['noschoolsconfigured_linktext'] = 'Go to settings';
$string['onesided_categoryonly'] = '{$a}: a top-level category exists, but no matching cohort';
$string['onesided_cohortonly'] = '{$a}: a cohort exists, but no matching top-level category';
$string['onesided_intro'] = 'These codes are only maintained on one side (cohort or category), not both, and cannot be selected as an active {$a}:';
$string['onesided_none'] = 'All cohorts and top-level categories with an idnumber are fully matched - nothing to report here.';
$string['onesidedwarning'] = 'One-sided matches';
$string['pluginname'] = 'Admin Cockpit';
$string['privacy:metadata'] = 'The Admin Cockpit plugin does not store any personal data. All numbers shown are computed on request from existing Moodle core data (users, cohorts, courses, task logs) and are never written anywhere by this plugin.';
$string['purgeallcaches'] = 'Purge ALL site caches';
$string['purgecache'] = 'Refresh now';
$string['schoolcard_coursemanagement'] = 'Course management';
$string['schooltile_activemembers'] = 'Active members';
$string['schooltile_coursecount'] = 'Courses';
$string['schooltile_membercount'] = 'Members';
$string['schooltile_newcourses'] = 'New courses';
$string['schooltile_newmembers'] = 'New members';
$string['section_globalusers'] = 'Global user metrics';
$string['section_healthsignals'] = 'Health signals';
$string['section_navigation'] = 'Navigation';
$string['section_schools'] = 'Per {$a}';
$string['selfenrolrisks'] = 'Self-enrolment without key or end date';
$string['selfenrolrisks_finding'] = 'Finding';
$string['selfenrolrisks_intro'] = 'Enabled self-enrolment methods that have no enrolment key, no end date, or neither. Each is a way for a course to keep taking enrolments unsupervised - open the course\'s enrolment methods to set a key or an end date.';
$string['selfenrolrisks_noenddate'] = 'No end date';
$string['selfenrolrisks_nokey'] = 'No enrolment key';
$string['selfenrolrisks_none'] = 'No self-enrolment methods without a key or an end date - nothing to report here.';
$string['selfenrolrisks_truncated'] = 'Showing the first 500 of {$a} self-enrolment methods.';
$string['signal_cron'] = 'Cron status';
$string['signal_cron_failedtasks'] = '{$a} failed task(s) in the last 24h.';
$string['signal_cron_help'] = 'Time since the last cron.php run and the number of scheduled tasks that failed in the last 24 hours. Click the tile to open the scheduled tasks overview.';
$string['signal_cron_lastrun'] = 'Last run {$a} ago.';
$string['signal_cron_link'] = 'https://github.com/tkorner/moodle-local_admincockpit';
$string['signal_cron_neverrun'] = 'Cron has never run.';
$string['signal_security'] = 'Security overview';
$string['signal_security_error'] = '{$a} errors';
$string['signal_security_help'] = 'Aggregated status of the core security overview checks (the same checks as Site administration → Reports → Security overview). Click the tile to open the full report with details for each check.';
$string['signal_security_link'] = 'https://github.com/tkorner/moodle-local_admincockpit';
$string['signal_security_ok'] = '{$a} OK';
$string['signal_security_warning'] = '{$a} warnings';
$string['tile_activeusers'] = 'Active users';
$string['tile_newusers'] = 'New users';
$string['tile_totalusers'] = 'Total users';
$string['timerange_label'] = 'Time range:';
$string['timerange_submit'] = 'Update';
$string['timerangedays'] = 'Time range for "new in period" counts';
$string['timerangedays_desc'] = 'Used by the dashboard to determine which users, cohort members, and courses count as "new", and which users count as "active" (a single shared period for both metrics, no separate setting). Can be temporarily overridden on the dashboard page itself without changing this default.';
$string['unpublishedcoursedays'] = 'Unpublished-course age threshold';
$string['unpublishedcoursedays_desc'] = 'A hidden course only counts as the "unpublished courses" health signal once it was created at least this long ago. A course hidden while still being prepared is normal, not a problem - this threshold is what tells the two apart.';
$string['unpublishedcourses'] = 'Unpublished courses';
$string['unpublishedcourses_intro'] = 'Courses hidden from students for at least {$a} days - a shorter time is normal while a course is still being prepared, so those are not shown here.';
$string['unpublishedcourses_none'] = 'No long-hidden courses found - nothing to report here.';
$string['unpublishedcourses_truncated'] = 'Showing the first 500 of {$a} long-hidden courses.';
