# Claude Code Prompt – local_admincockpit

This file contains the step-by-step sequence for the implementation, based on `SPEC-admincockpit.md`. Each step is meant as its own prompt for Claude Code – only move to the next step after the current one has been confirmed/reviewed.

Before step 1: place `SPEC-admincockpit.md` in the same directory as `CLAUDE.md`, so Claude Code has both as context. A `CLAUDE.md` for this project should contain: target environment (Moodle 5.2.x in Docker, `/var/www/html/public/local/`), coding standard (core-contribution-grade quality, Moodle Coding Style), and a note that this is a reporting/navigation plugin without its own DB schema.

**Testing note (as of: no GUI access, PHPUnit not locally installable):** instead of running PHPUnit locally, two levels are used:
1. **CLI smoke scripts** under `cli/verify_*.php` for immediate feedback during the session (`docker exec -it claude-moodle-1 php /var/www/html/local/admincockpit/cli/verify_*.php`)
2. **GitHub Actions with `moodlehq/moodle-plugin-ci`** for the actual PHPUnit/Behat/code-checker run on every push (see step 0b)

PHPUnit test files are still written despite this (they just don't run locally, only via CI) – don't skip them.

**Progress:** Steps 0 through 8 as well as 9 and 10 are implemented (see release 1.0.0 and 1.1.0). Open: step 7h (make navigation configurable), 11 (plugin-directory prerequisites), 12 (final review). Steps 0 through 20 are now implemented and released (see "Progress" in CLAUDE.md for the current, authoritative state instead of this line, which is no longer kept perfectly in sync).

---

## Step 0 – Plugin skeleton ✅ done

```
Create the skeleton for a new Moodle plugin of type "local", component
local_admincockpit, for Moodle 5.2.

Create:
- version.php (component local_admincockpit, current version, requires for Moodle 5.2)
- lib.php (empty/placeholder, with the usual callback stubs in case they're needed later)
- classes/ (empty directory with .gitkeep)
- lang/en/local_admincockpit.php with basic entries (pluginname)
- lang/de/local_admincockpit.php with the German translations of the same strings
- settings.php: registers an own admin page under Site administration > Reports
  (admin_externalpage, not admin_settingpage, since it's a dashboard view rather than
  plain configuration values) plus a separate settings page for the
  configuration (time range, active school codes) under Site administration > Plugins >
  Local plugins > Admin Cockpit
- db/access.php with the capability local/admincockpit:view
  (CONTEXT_SYSTEM, archetypes: manager => CAP_ALLOW)

Follow the Moodle coding guidelines and the standard directory structure for local
plugins. Don't commit anything yet, I want to review the result first.
```

---

## Step 1 – Code detection (cohort ↔ category matching) ✅ done

```
Implement the class classes/school_matcher.php (namespace local_admincockpit).

Class responsibilities:
- Finds all system-wide cohorts (cohort table, contextid = system context) with a
  non-empty idnumber
- Finds all top-level course categories (core_course_category, parent = 0) with a
  non-empty idnumber
- Returns three lists:
  1. "matched": codes where both sides exist (idnumber match), including the cohort
     id and the category id
  2. "cohort_only": codes with a cohort but no matching top-level category
  3. "category_only": codes with a top-level category but no matching cohort

Use the Moodle Data Manipulation API ($DB->get_records_sql or get_records with
conditions), no raw mysqli calls. Write a PHPUnit test for this
(tests/school_matcher_test.php), which, using the Moodle test data generator
(get_data_generator()->create_cohort(), create_category()), covers at least the
following cases: a complete pair, a cohort without a category, a category without a
cohort, a code with an empty idnumber gets ignored.
```

---

## Step 0b – Set up CI pipeline (retroactively, catching up now)

```
Set up a GitHub Actions pipeline for this plugin, based on
moodlehq/moodle-plugin-ci.

Create .github/workflows/ci.yml following the standard template from
moodle-plugin-ci (see https://github.com/moodlehq/moodle-plugin-ci for the
current recommended workflow template). Take into account:
- A matrix for at least the PHP version and Moodle version matching the
  target environment (Moodle 5.2.x line; research the matching PHP version,
  don't guess)
- MariaDB as the DB service (matching the target environment, don't assume Postgres)
- Standard steps: phplint, phpcpd, phpcs (moodle ruleset), phpdoc, validate,
  savepoints, mustache lint, grunt, phpunit, behat
- Workflow should run on every push and on pull requests

Also create a short cli/verify_school_matcher.php as an instant check
(no test framework, a plain execution script with the CLI_SCRIPT constant and
output via print_r) that I can run locally via
`docker exec -it claude-moodle-1 php ...`, without having to wait for the
CI run. This script tests school_matcher::get_matched()
against the actual data of the running instance (not the test-data generator,
but real cohorts/categories) – purely for a visual check, not a substitute for the
PHPUnit test from step 1.
```

---

## Step 2 – Settings page (time range + active codes)

```
Build the configuration page for local_admincockpit (Site administration > Plugins >
Local plugins > Admin Cockpit).

Contains:
- A select field "Time range for new-count metrics" with options 30/90/180/360 days
  (setting name: local_admincockpit/timerangedays, default 180)
- A multi-select "Active school codes": options populated dynamically from
  school_matcher::get_matched() (not hardcoded), stored as a comma-separated string or
  JSON in local_admincockpit/activeschools
- A read-only notice block listing cohort_only and category_only from school_matcher
  ("These codes are only maintained on one side: ...") – as an admin_setting_description
  or its own admin_setting element

Use the Moodle Admin Settings API (admin_setting_configmultiselect or similar), not a
custom form, as long as the standard elements are enough.
```

---

## Step 3 – Global user metrics

```
Implement classes/metrics/user_metrics.php with a class that computes the following
values and returns them as a simple data object/array:
- Number of active user accounts (deleted = 0, suspended = 0 -- check in code whether
  suspended should be counted here or not, and document the decision in the docblock)
- Number of users with lastaccess within the last 4 weeks
- Number of users with timecreated within the configured time range
  (time range from the local_admincockpit/timerangedays setting)

Write efficient SQL queries (COUNT queries, don't load full recordsets).
Add a PHPUnit test with test data for all three values (runs via CI, see
step 0b). Also add cli/verify_user_metrics.php for an instant check
against the real data of the running instance.
```

---

## Step 4 – Per-school metrics

```
Implement classes/metrics/school_metrics.php. For a given code (with the cohort id
and category id from school_matcher), the class computes:
- Cohort member count (cohort_members count)
- New joins: cohort_members.timeadded within the configured time range
- Active members: join cohort_members -> user, lastaccess within the last 4 weeks
- Course count: courses in the category INCLUDING subcategories (using the category.path
  prefix matching), but without a per-subcategory breakdown in the return value
- New courses: course.timecreated within the time range, same category filtering

Important: confirm the assumption "course count including subcategories" explicitly
in a code comment, so it can easily be revised if needed.

Add PHPUnit tests with a category that has at least one subcategory containing courses,
to verify the path matching (runs via CI, see step 0b).
Also add cli/verify_school_metrics.php for an instant check against an
actual school code on the running instance.
```

---

## Step 5 – Health signals

```
Implement classes/metrics/health_signals.php with four methods:

1. duplicate_emails(): returns the number of email addresses used by more than one
   active user account, plus a detail list (userid, email, fullname) for
   the drill-down view
2. courses_without_enddate(): number and detail list (courseid, fullname, categoryname)
   of courses with enddate = 0
3. security_overview_summary(): reuse the existing core class/function
   for the security overview report (DO NOT reimplement it) – research first in the
   Moodle core code (admin/report/security/) which class/method runs the checks
   and derive an aggregated traffic light from it (ok/warning/error with a count per
   status)
4. cron_status(): timestamp of the last cron run and number of failed tasks in
   the last 24h, based on the core task-log infrastructure (research the
   matching table/class rather than guessing the table name)

For points 3 and 4: if you find during the code review that the matching core API
is harder to reuse than expected, tell me explicitly instead of writing an own
parallel implementation of the security checks.
```

---

## Step 6 – Drill-down pages for health signals

```
Create two simple pages (no blocks):
- duplicateemails.php: shows the detail list from health_signals::duplicate_emails(),
  one row per line with a link to the user profile (user/profile.php?id=X) and a note
  that tool_mergeusers can be used for the actual merge
- courseswithoutenddate.php: shows the detail list from
  health_signals::courses_without_enddate(), one row per line with a link directly into
  the course settings (course/edit.php?id=X)

Both pages need the local/admincockpit:view capability and a back link
to the main dashboard page. Use a simple table output (core html_table or
core_reportbuilder system_report, whichever needs less code for this
simple case).
```

---

## Step 7 – Main dashboard page (rendering)

```
Build index.php + classes/output/renderer.php + the Mustache templates for the
main dashboard page, bringing together all the building blocks so far:

Layout (top to bottom):
1. Global user metrics (tile row)
2. Per school (only the active codes from the settings): tile group with the 5 values
   from school_metrics + a link to course management for the category
   (course/management.php?categoryid=X)
3. Health signals (4 tiles, each with a click-target as defined in step 5/6)
4. Navigation, grouped into boxes: user management, course management,
   reports/logs, system, theme/appearance (see SPEC section 5 for the concrete
   link targets – research the exact URLs/parameters in Moodle core or in
   the installed theme_boost_union, don't guess them)

The time-range select (30/90/180/360 days) should be a dropdown near the top of the page
that reloads the page with the chosen time range (GET parameter, temporarily overrides
the default from the settings, without permanently changing the setting).

Show "0 codes actively configured" with a link to the settings page if no school has
been selected yet.
```

---

## Step 7b – Styling refinement: Moodle-/Bootstrap-5-compliant instead of custom CSS

```
The main dashboard page is already implemented (step 7), but partly uses its own
CSS instead of Moodle/Bootstrap building blocks. Rework the styling as follows,
without touching the data logic (metric classes):

1. Health-signal tiles: currently colored borders (green/orange), presumably via own
   CSS. Replace with $OUTPUT->notification($message, $type) using the
   standard types 'notifysuccess' / 'notifywarning' / 'notifyproblem', OR, if that
   breaks the tile layout, with the Bootstrap 5 classes 'border-success' /
   'border-warning' / 'border-danger' combined with 'card'. Research first in
   Moodle core (e.g. how report/security or other core reports display traffic-light
   status) to reuse an existing pattern instead of inventing a new one.

2. Add an icon in addition to the border color in every health-signal tile
   (Font Awesome, available in Moodle via $OUTPUT->pix_icon() or the 'fa' classes
   directly) - a checkmark for ok, a warning triangle for warning/error. Reason:
   color coding alone isn't distinguishable enough for color-blind users.

3. Check all Bootstrap classes used across the whole dashboard (metric tiles,
   navigation boxes, health signals) for Bootstrap 5 compliance, NOT Bootstrap 4:
   - text-left/text-right -> text-start/text-end
   - ml-*/mr-* -> ms-*/me-*
   - custom-select -> form-select
   - Other BS4 class names, if any, also updated
   (see https://moodledev.io/docs/5.0/guides/bs5migration for the full list,
   if unsure)

4. Metric tiles (total users/active users/new users): check whether 'card'
   plus 'card-body' (Bootstrap standard component) gives a more consistent look
   than the current free-standing box solution, compared to other Moodle core reports.
   Not a must, but evaluate it briefly and justify the chosen solution.

Only commit after review. Show me first, briefly, which concrete core location you
found as a model for the traffic-light display (point 1).
```

---

## Step 7c – Fix: icons in health-signal badges & inconsistent tile heights

```
After step 7b, the browser shows: the icons in the OK/warning badges don't
render (an empty placeholder before the text), which also shifts the badge's
centering. Also, the security-overview and cron-status tiles are noticeably
taller than the other two, because their text wraps onto multiple lines.

1. Icon rendering: first research which Font Awesome version/class prefix Moodle
   5.2 core currently ships for icons (e.g. whether 'fa fa-check' still works
   or 'fa-solid fa-check' is needed) - look at how a core template with an icon
   (e.g. in a standard notification or a core report) embeds the icon, instead of
   guessing the class. Fix the badge icons accordingly. Alternative, if more robust:
   $OUTPUT->pix_icon() with a matching core icon name (e.g. 'i/valid' / 'i/warning')
   instead of direct Font Awesome classes - briefly evaluate which route is more
   consistent in this codebase, and justify the choice.

2. Badge centering: make sure icon + text in the badge are centered via a
   flex-container class (e.g. 'd-inline-flex align-items-center
   justify-content-center'), so centering stays stable even with a variable icon
   width.

3. Shorten text:
   - Security-overview tile: instead of "15 ok / 4 warning(s) / 0 error(s)" show it
     more compactly, e.g. "15 OK · 4 warnings" on one line (0 errors can be omitted
     when it's 0, or only shown when > 0)
   - Cron-status tile: replace the full timestamp ("Saturday, 11. July 2026, 19:11.")
     with a more compact format (e.g. relative time "2 hours ago" via
     Moodle's userdate() function with a relative format, if available -
     research rather than guess) and offer the full timestamp instead as a
     title attribute/tooltip

4. Match tile heights: put all four health-signal tiles into a shared flex row with
   'align-items-stretch' and 'h-100' on the cards, so all four become equally
   tall regardless of text length - badge aligned at the bottom of the
   tile in each case (e.g. via 'd-flex flex-column justify-content-between' on the
   card itself).

Show me a screenshot before the commit, or briefly describe which icon solution
(Font Awesome class vs. pix_icon) you chose and why.
```

---

## Step 7d – Caching the calculations (Moodle Cache API, TTL 1 day)

```
Currently, all metrics and health signals are recomputed on every page load.
Introduce caching via the Moodle Cache API (MUC), TTL 1 day (86400 seconds).

1. Define db/caches.php with an 'application' cache definition (not 'request'
   or 'session', since the values should be the same across users/sessions), e.g.
   'dashboarddata', with ttl => 86400.

2. Rework the existing calculation classes (user_metrics, school_metrics,
   health_signals) so they first check the cache (cache::make('local_
   admincockpit', 'dashboarddata')->get($key)) and only recompute and write back
   the result on a cache miss. The cache key must include the currently selected
   time range (GET parameter), since different time ranges yield
   different results (e.g. key scheme 'usermetrics_' . $timerangedays).

3. Build a "Purge cache now" button directly on the dashboard page (not only
   reachable via general Moodle cache management). The button:
   - requires a sesskey check (require_sesskey())
   - requires the same capability as the dashboard page (local/admincockpit:view)
     or an own local cascade for it, if that makes more sense - decide what's
     more consistent
   - calls $cache->purge() only on this plugin's own cache definition, NOT
     purge_all_caches() (that would hit the entire instance, not just this
     plugin)
   - afterwards shows a confirmation message ($OUTPUT->notification(..., 'notifysuccess'))
     and reloads the page with freshly computed values

4. Subtly show on the page when the displayed values were last computed
   (e.g. "As of: <timestamp>, updated daily" below the metrics),
   so it's clear to the admin that the numbers aren't live.

5. IMPORTANT regarding placement: the "Purge cache now" button affects the
   ENTIRE page (global metrics, all schools, all health signals - a single
   cache definition gets purged completely). So place it NOT
   inside or directly under the "Global user metrics" block (that would
   wrongly suggest it only affects that block), but at the very top of the
   page on the same row as the time-range selector, together with the
   "As of: ..." timestamp note from point 4. Reasoning: both elements
   (the time-range switcher and the cache-purge button) act page-wide, not on a
   single block - they therefore belong together visually, above all
   content blocks.

Research the exact current Moodle Cache API syntax (cache::make(), definition in
db/caches.php) in the core code if unsure, instead of implementing from
memory - the API has changed slightly across Moodle versions.
```

---

## Step 7e – Fully remove Bootstrap 4 leftovers

```
Search the entire plugin (all Mustache templates, any own CSS/SCSS) for
remaining Bootstrap 4 class names and consistently replace them with the
Bootstrap 5 equivalents. Check in particular:
- text-left/text-right -> text-start/text-end
- ml-*/mr-*, pl-*/pr-* -> ms-*/me-*, ps-*/pe-*
- float-left/float-right -> float-start/float-end
- border-left/border-right -> border-start/border-end
- rounded-left/rounded-right -> rounded-start/rounded-end
- custom-select -> form-select
- sr-only -> visually-hidden
- .close (button class) -> .btn-close
- font-weight-* -> fw-*
- font-italic -> fst-italic
- no-gutters -> g-0

Use https://moodledev.io/docs/5.0/guides/bs5migration as a reference for the
full list, in case further BS4 patterns show up in the code that aren't
listed here. List briefly at the end what you found and replaced, so I
can review it before it gets committed.
```

---

## Step 7f – Moodle event for dashboard views

```
Implement a standard Moodle event following core convention:

1. classes/event/dashboard_viewed.php - event class extending \core\event\base,
   CRUD 'r' (read), edulevel 'other' (not a learning-related event), object table
   not applicable (no DB object being viewed - look at a core example for a
   plain "page viewed" event with no associated record, e.g. how other report
   pages solve this, instead of guessing it)

2. Trigger the event in index.php as soon as the page is successfully accessed with
   a valid capability (after the capability check, before rendering)

3. Add the event description to lang/en/ and lang/de/
   (get_string('eventdashboardviewed', ...))

4. Briefly verify afterwards: the event should then show up under Site administration >
   Reports > Logs when the dashboard page is accessed - test that once
   after implementing it and let me know briefly whether it shows up.
```

---

## Step 7g – Fix: cache key for per-school values is missing the time-range component

```
When caching was introduced in step 7d, the time range was only taken into account
in the cache key of the global user metrics, not in school_metrics. This means
that when switching the time range (30/90/180/360 days), the "new joins" and
"new courses" values per school still show the previously cached numbers for the
old time range, even though the rest of the page already uses the new
time range.

Fix the cache key in school_metrics so it includes both the school code
and the selected time range, e.g. 'schoolmetrics_' . $code . '_' .
$timerangedays.

Conversely, check health_signals: none of the four values there depend on the
time-range parameter (duplicates, courses without an end date, security overview,
cron status are all time-range independent) - make sure the cache key there does
NOT unnecessarily include the time range, otherwise those values would get
recomputed more often than needed, with no benefit.

After the fix, test manually: switch the time range, check whether "new joins"/
"new courses" per school actually change, not just the global values.
```

---

## Step 7h – Make navigation configurable (textarea setting instead of hardcoded links)

```
The navigation links are currently hardcoded in the renderer/templates. Make them
configurable, following the pattern of Moodle's own custom menu
($CFG->custommenuitems), so the plugin can be used on other Moodle instances
without a code change.

1. New setting 'navitems' of type admin_setting_configtextarea in
   settings.php. Format per line (pipe-separated), 3 or 4 segments (capability
   optional, with 3 segments no capability check is performed):
   Title|URL|Group|Capability(optional)
   Trim every segment when parsing (remove leading/trailing whitespace),
   so "Title | URL" works the same as "Title|URL".
   Example:
   Upload users|/admin/user/user_bulk.php|User management|moodle/user:create
   Manage cohorts|/cohort/index.php|User management|moodle/cohort:manage
   Scheduled Tasks|/admin/tool/task/scheduledtasks.php|System|moodle/site:config

   Base the parsing approach on what Moodle core itself uses for custommenuitems
   (look it up in the core code how lines/pipes are split there), instead of
   inventing a completely own parsing logic.

2. Pre-fill 'navitems' with a sensible default that contains exactly the
   currently hardcoded links (all entries from SPEC section 5), so
   existing installations (including our own) keep working after the update without
   manual work. Only pre-fill the Boost Union theme-settings line if
   theme_boost_union is actually installed (otherwise omit it).

3. Renderer: navigation groups (card headings) are built dynamically from the
   group names occurring in 'navitems', no longer hardcoded. Order of the
   groups: order of first occurrence in the setting.

4. Before rendering each link: if a capability is given, check it with
   has_capability() (context: system, since we deliberately stay with
   system-wide administrators here, no category-/context-sensitive check
   needed) and only show the link if it passes. Without a capability given: always
   show it (fallback for admins who don't want to use the capability column).

5. Malformed/unparseable lines (wrong format, more/fewer than 3-4 segments):
   don't cause a fatal error, just skip the line and optionally show an
   admin_setting_description with a note "X line(s) could not be
   parsed" above the textarea.

6. If 'navitems' is completely empty (e.g. deliberately cleared by the admin), the
   navigation section must stay cleanly empty (no fatal error, no empty boxes with
   a heading and no content) - best hidden completely, with an optional note text
   "No navigation items configured" plus a link to the settings.

Add a cli/verify_navitems.php for an instant check of the parsing against the
configured values on the running instance.
```

---

## Step 9 – Generalization: replace "school" with a configurable term

```
The term "school" is currently hardcoded in several places in the UI (tile
headings, settings labels, possibly language files). For the release,
the plugin should be usable for arbitrary groupings (sites, departments, tenants,
faculties), not just schools.

1. New setting 'groupinglabel' (free text, admin_setting_configtext), default value
   "School" (so nothing changes for our own instance), description e.g.
   "Label for the grouping made of cohort + category (e.g. school, site,
   department, faculty)"

2. Replace all hardcoded occurrences of "school"/"schools" in the UI (tile
   heading "Per school", settings labels like "Active school codes") with
   dynamic use of get_config('local_admincockpit', 'groupinglabel')
   or an equivalent language-string placeholder solution (get_string with an $a
   placeholder instead of a hardcoded noun).

3. Do NOT rename purely internal identifiers (variable names like $code,
   method names like school_matcher, school_metrics) - this is purely a UI
   generalization, not a rename of the internal architecture. Otherwise the effort
   is unnecessarily high for no user-facing value.

4. Check language files (en/de) for remaining hardcoded "school"/"Schule" strings
   in user-visible get_string() values and adjust them to the generic wording
   (e.g. "cohort/category groupings" as a fallback wording where no
   placeholder makes sense).
```

---

## Step 10 – Empty-state test with 0 configured groups

```
Test and make sure the dashboard works cleanly when 'activeschools'
(or however it may have been renamed in step 9) is completely empty - the
state of a freshly installed instance with no configured groupings.

Expected behavior:
- No fatal error, no empty/broken "Per school" section
- Instead a note with a link to the settings page, e.g. "No [groupinglabel]
  configured. Go to settings." (text uses the generic term from
  step 9)
- Global user metrics and health signals remain unaffected by this and keep
  working normally

If an error occurs during testing (e.g. because some calculation assumes at
least one element in the code list), fix the affected spot in school_metrics
or the renderer.
```

---

## Step 11 – Formal requirements for the Moodle Plugin Directory

```
Add the components strictly required for a release in the Moodle Plugin Directory:

1. classes/privacy/provider.php: since the plugin itself doesn't STORE any personal
   data (it only reads/aggregates existing core tables at request time),
   implement \core_privacy\local\metadata\null_provider with a clear
   justification as a language string (get_string('privacy:metadata', ...) explaining
   why no own data is stored). Research first in Moodle core how
   other plain report/dashboard plugins without their own data storage build their
   privacy provider class, instead of guessing it.

2. README.md in the repo root: short description, requirements (Moodle version,
   PHP version if relevant), installation instructions, a note on the configuration
   (time range, grouping code, navigation textarea), a license note, at least
   1-2 screenshots (placeholder reference, if images need to be added separately)

3. LICENSE file: full GPLv3 text (standard license for Moodle plugins)

4. Complete lang/en/local_admincockpit.php as a fully complete base language -
   this is mandatory for the directory listing, even though lang/de/ remains the
   primary usage language

5. Check whether any external JS libraries were embedded anywhere (e.g. for chart
   display, if used) - if so, add thirdpartylibs.xml with
   license details. If no external libraries are used, briefly confirm that.

6. version.php: $plugin->maturity (e.g. MATURITY_STABLE) and $plugin->release
   set cleanly, if not already done.
```

---

## Step 12 – Review, language files, wrap-up

```
1. Complete lang/en/ and lang/de/ with all get_string() keys used so far
2. Check all steps against the Moodle Coding Guidelines (phpcs with the
   moodle ruleset, if available locally)
3. List all places in the code marked with "TODO: verify" or similar
   markers pointing to open assumptions from the SPEC (see SPEC section 8), so I
   can specifically review these before the plugin goes into production
4. Do NOT create a runbook/documentation entry automatically – I'll do that separately,
   once the plugin has been fully tested
```

---

## Step 13 – Fix: "of which active" (global + per school) follows the time range instead of a fixed 4 weeks ✅ done

```
Previously, both the global metric "of which active" (user_metrics::count_recently_active_users())
and the per-school metric "Active members" (school_metrics::count_active_members()) were
hardcoded to the last 4 weeks, independent of the local_admincockpit/timerangedays
setting, while "New users in period"/"New joins in period" already used the
configured/selected time range - an inconsistency between two side-by-side
displayed metrics in each case.

Decision (2026-07-29): no separate "active threshold" setting (SPEC §11 had this
planned as a v2 candidate, but it was explicitly rejected) - both "active" metrics
now use the same local_admincockpit/timerangedays value as the respective "new"
metric. A shared time range is easier to reason about than two separate values.
Originally (first implementation of this step) only done for the global metric, then
subsequently extended to "Active members" per school, following the same pattern.

1. classes/metrics/user_metrics.php: count_recently_active_users() gets the parameter
   int $timerangedays instead of the fixed 4 * WEEKSECS calculation; compute_metrics()
   passes through the already-existing $timerangedays parameter.
2. classes/metrics/school_metrics.php: count_active_members() also gets the
   parameter int $timerangedays instead of the fixed 4 * WEEKSECS calculation;
   compute_metrics() passes through the already-existing $timerangedays parameter.
3. lang/en/ and lang/de/: NO new setting-label string - only the existing help text
   of timerangedays_desc gets extended to note that it now also controls "active
   users" (and implicitly "Active members").
4. tests/metrics/user_metrics_test.php and tests/metrics/school_metrics_test.php:
   adjust the existing tests for "of which active"/"Active members" to use a
   narrow time range (e.g. 30 days) instead of the previous fixed 4-week fixtures,
   plus one additional test each proving that a wider time range (e.g. 90 days)
   correctly includes an account/member that was still excluded at 30 days.
5. cli/verify_user_metrics.php and cli/verify_school_metrics.php: adjust the output
   line so it's clear that timerangedays now applies to activeusers/activemembers AND
   newusers/newmembers.
6. SPEC-admincockpit.md: update §3 ("of which active", "Active members") and §11
   (mark the active-threshold idea as rejected, don't delete it) accordingly.

No cache-key fix needed (see steps 7d/7g) - the cache keys of user_metrics and
school_metrics already fully include $timerangedays, no change to caches.php
required.

Verified: vendor/bin/phpunit --testsuite local_admincockpit_testsuite in the
container, 38/38 tests green after re-initialization (php public/admin/tool/phpunit/cli/init.php
was needed, since phpunit/phpunit as a Composer dev dependency wasn't installed
beforehand - composer install caught up on this in the container).
```

---

## Step 14 – Health signal DTO ✅ done

```
Implement classes/health_signal.php (namespace local_admincockpit), a small
immutable value object representing one health signal tile, independent of
how it's computed or by whom:

- component (string): frankenstyle component of whoever produced this signal,
  e.g. 'local_admincockpit' for the built-in ones
- key (string): stable identifier unique within that component, e.g.
  'duplicateemails', 'cron' - together with component this is the identity
  used later (step 17) for the enable/disable/ordering setting
- label (string): already-translated display label
- value (int|string): the tile's displayed value
- severity (string): 'ok'|'warning'|'error'
- url (string): site-relative or absolute click-target
- valuetitle (string, optional, default ''): tooltip/title attribute
- helpicon (string, optional, default ''): pre-rendered help icon HTML

Constructor-only, readonly properties, no setters. Add a PHPUnit test
(tests/health_signal_test.php) covering construction and defaults for the
optional parameters. No behavior change to the dashboard yet - this is a
pure data-object addition.
```

---

## Step 15 – Research: core Hooks API interface (mandatory, verify before coding) ✅ done

```
Before implementing the hook itself: verify against the actual Moodle 5.2
core source (lib/classes/hook/described_hook.php and the Hooks API guide),
not from memory/training data, what the exact described_hook interface
requires (method signatures for the self-description methods), how
db/hooks.php registration entries are structured (hook class => callback,
optional priority), and how \core\hook\manager::get_instance()->dispatch()
is invoked. Report back what you found, with file/line references, before
writing any hook code. Same treatment as the four SPEC "mandatory research
points" - don't guess this.
```

---

## Step 16 – Health signals hook + own listener ✅ done

```
Based on the verified interface from step 15:

1. classes/hook/health_signals.php: the hook class itself, implementing the
   verified described_hook interface. Carries a private array of
   local_admincockpit\health_signal instances, with add_signal(health_signal
   $signal): void and get_signals(): array.

2. db/hooks.php: register this plugin's own listener for its own hook (the
   built-in signals become a hook consumer like any third party would be,
   not a special case).

3. classes/local/hook_listener.php (or similar - pick the namespace that best
   fits existing conventions): one static callback method that wraps the four
   existing classes/metrics/health_signals.php computations (unchanged) into
   local_admincockpit\health_signal DTOs (component 'local_admincockpit', keys
   'duplicateemails'/'courseswithoutenddate'/'security'/'cron') and calls
   $hook->add_signal() for each. All severity/URL/label logic currently in
   classes/output/dashboard_page.php's export_health_signals()/make_signal_tile()
   moves here unchanged - this step is a refactor extracting existing logic
   into the hook-listener shape, not new business logic.

Do not touch dashboard_page.php's rendering yet (that's step 17) - at this
point the hook exists and is populated, but isn't consumed.
```

---

## Step 17 – Dashboard consumes the hook (+ exception isolation) ✅ done

```
1. Rework classes/output/dashboard_page.php::export_health_signals() to
   dispatch the local_admincockpit\hook\health_signals hook and iterate
   whatever health_signal DTOs come back, feeding each through
   make_signal_tile() (which stays as the single place that knows about
   badge markup/CSS classes), instead of the four hardcoded calls.

2. Resilience: check what step 15's research found about whether core's hook
   manager isolates exceptions between multiple registered callbacks for the
   same hook. If it doesn't, wrap the dispatch (or each listener's
   contribution) so a throwing third-party listener is logged via
   debugging() and skipped, rather than 500ing the whole dashboard for every
   admin. Decide the exact mechanism based on what's actually verified, not
   assumed.

3. Verify with the existing PHPUnit/Behat suite and cli/verify_health_signals.php
   that dashboard output for the four built-in signals is unchanged (same
   labels, values, severities, URLs as before this refactor).
```

---

## Step 18 – Settings: enable/disable/order signal catalog ✅ done

```
Add a settings mechanism (Site administration > Plugins > Local plugins >
Admin Cockpit) that lets the admin enable/disable and order ALL currently
available health signals - both this plugin's own built-in ones and any
third-party hook-contributed ones - by their component+key identity from
step 14.

1. Reuse the existing 'navitems' textarea-based ordering convention (see
   step 7h) rather than inventing a new UI widget: an ordered,
   newline-or-pipe-separated list of 'component:key' entries.

2. The settings page discovers currently available signals by dispatching
   the hook once at render time (same as the dashboard does), so newly
   installed third-party signals - or newly added built-in ones from step 19 -
   show up automatically.

3. Default behavior for a signal not yet mentioned in the stored setting:
   enabled, appended at the end - so upgrading to this step, or installing a
   new signal-producing plugin later, doesn't silently hide anything the
   admin never explicitly disabled.

4. A stored entry referencing a component/key that's no longer available
   (e.g. that plugin got uninstalled) is silently skipped at render time, same
   tolerance pattern as the existing 'activeschools' setting already has for
   stale codes - not an error.

5. classes/output/dashboard_page.php::export_health_signals() filters/orders
   the hook's returned DTOs against this setting before rendering.
```

---

## Step 19 – Dogfood: first additional built-in signal via the same mechanism ✅ done

```
Implement ONE new optional, off-by-default-or-on (decide during
implementation) built-in health signal, registered through the exact same
hook_listener mechanism from step 16 - proving the extension point works
for real, not just in theory, before documenting it for third parties.

Candidate: "unpublished courses" - course.visible = 0 AND course.timecreated
older than a configurable threshold (new setting, e.g.
local_admincockpit/unpublishedcoursedays, default 90). Deliberately NOT a
bare visibility check - a course hidden while still being prepared is normal,
not a health problem; pairing with an age condition avoids the same
false-positive trap already identified and avoided for the rejected
"auth mismatch" signal (see SPEC §11).

Add the query to classes/metrics/health_signals.php (new method, same class,
same caching discipline as the other four), a hook_listener entry for it, a
drill-down page (courseswithoutenddate.php is the template to follow), and
PHPUnit test + cli/verify_ script, same as steps 5/6.
```

---

## Step 20 – Document the extension point + release ✅ done

```
1. README.md: new section "Extending: add your own health signal" with the
   minimal example (db/hooks.php entry + listener class + health_signal
   construction), pointing out this requires no changes to
   local_admincockpit itself and no coordination with its maintainer.

2. SPEC-admincockpit.md §11: move "pluggable health signals via Hooks API"
   and "unpublished courses" from backlog/idea into "implemented", with a
   short note on why hooks were chosen over a raw-SQL settings field (already
   rejected once for the arbitrary-SQL-widget idea, see §10) and over a
   formal Moodle subplugin relationship (too tightly coupled for this case).

3. Version bump + changelog entry per the usual "bump per feature" habit,
   release notes explicitly calling out the new hook (discoverable by other
   plugin authors browsing Marketplace/GitHub release notes).
```

---

## Notes for the session

- Plugin names, capability names, and table names in this file are suggestions,
  not fixed requirements – if a Moodle convention check suggests something else,
  deviate and briefly explain why.
- When unsure about exact core APIs (Security Overview, task log, Boost Union
  settings URL): research instead of guessing, and when unclear, ask explicitly
  instead of implementing a guess.
- Every step should be runnable/testable on its own before the next one starts.
