# SPEC – local_admincockpit[cite: 1]

Navigation and metrics dashboard for Moodle administrators.[cite: 1] Bundles user/course metrics per "school", health signals with a call-to-action, and direct links to frequently used admin pages.[cite: 1] The system is selectively extended with direct execution commands (quick actions) to bypass deeply nested Moodle menus.

---

## 1. Purpose

An admin currently spends time gathering the state of the instance across several menu items (user counts, course counts per school, data-hygiene issues).[cite: 1] The dashboard bundles this on one page:[cite: 1]

- **Metrics** – current state + change over a configurable time range[cite: 1]
- **Health signals** – number + direct click to fix/investigate (not plain reporting)[cite: 1]
- **Quick actions** – immediate execution of critical and frequent admin tasks directly from the dashboard (e.g. impersonation, cache management).
- **Navigation** – shortcuts to admin tasks, grouped by task type and per school[cite: 1]

Not a rebuild of Moodle Workplace Report Builder – deliberately leaner, tailored to this instance's concrete needs.[cite: 1]

---

## 2. Core concept "school"

A school consists of two independently maintained Moodle objects, linked via a shared code (`idnumber`):[cite: 1]

- a **global cohort** (`idnumber` = code, e.g. `TBZ`)[cite: 1]
- a **top-level course category** (`idnumber` = the same code)[cite: 1]

There is no fixed naming convention (display names can stay free-form), matching runs exclusively via `idnumber`.[cite: 1] `idnumber` is a free string (alphanumeric), so codes like `TBZ` are no problem.[cite: 1]

**Important:** not every top-level category and not every global cohort is necessarily a "school" – further categories/cohorts without any dashboard relevance may exist.[cite: 1] The plugin must therefore:[cite: 1]

1. Determine all codes for which both a cohort and a top-level category with an identical `idnumber` exist (= complete pairs)[cite: 1]
2. Show codes with only a one-sided match (cohort without category or vice versa) as a warning in the plugin settings, not silently ignore them[cite: 1]
3. Build a selection list from the complete pairs, from which the admin picks the codes to show on the dashboard via a multi-select[cite: 1]

**No own mapping table** in the database is needed – the mapping is resolved at request time via `idnumber`; only the selection ("which codes are active") is stored as a plugin setting.[cite: 1]

---

## 3. Metrics

All "in period" values refer to a globally configurable time range (default values 30 / 90 / 180 / 360 days, selectable as an admin setting, no snapshot history – a plain `timecreated` filter).[cite: 1]

### Global
| Metric | Source / logic |
|---|---|
| Total users[cite: 1] | Number of active (not deleted) user accounts[cite: 1] |
| of which active | `lastaccess` within the selected time range (**revised 2026-07-29**: shares the global time range from section 3 with "New users", no own fixed value anymore – see §11 "Deliberately not pursued further") |
| New users in period[cite: 1] | `timecreated` within the selected time range[cite: 1] |

### Per school (for each selected code)
| Metric | Source / logic |
|---|---|
| Member count[cite: 1] | Cohort members (`cohort_members`)[cite: 1] |
| New joins in period[cite: 1] | `cohort_members.timeadded` within the time range[cite: 1] |
| Active members | Members with `lastaccess` within the selected time range (**revised 2026-07-29**: shares the global time range from section 3 with "New joins", no own fixed value anymore, analogous to global "of which active" – see §11 "Deliberately not pursued further") |
| Course count[cite: 1] | Courses in the assigned top-level category, **without** subcategory breakdown – to clarify: do courses in subcategories count, or only courses directly in the top-level category? (Assumption: including subcategories, but without a separate display per subcategory – please confirm during implementation)[cite: 1] |
| New courses in period[cite: 1] | `course.timecreated` within the time range, filtered to the category[cite: 1] |

---

## 4. Health signals (v1)

Every health signal is a number **with a click-target** (call-to-action) – never a plain statistic tile.[cite: 1]

| Signal | Logic | Click-target |
|---|---|---|
| Duplicate email addresses[cite: 1] | Users with an identical `email`, grouped[cite: 1] | Own list in the plugin with the affected user pairs/groups, as prep for `tool_mergeusers`[cite: 1] |
| Courses without an end date[cite: 1] | `course.enddate = 0`[cite: 1] | Own filtered course list in the plugin, jumping from there into the respective course settings[cite: 1] |
| Security overview traffic light[cite: 1] | Aggregated status of the core security checks (green/yellow/red)[cite: 1] | Direct link to the existing Site administration → Reports → Security overview page[cite: 1] |
| Cron status[cite: 1] | Timestamp of the last cron run + number of failed scheduled tasks[cite: 1] | Direct link to the existing scheduled tasks overview[cite: 1] |

**Deliberately not in v1:** unconfirmed accounts, an auth-method overview, a plugin-update overview, courses without a participant/teacher (possible v2 candidates).[cite: 1]

**Open during implementation:** the exact API/query for aggregating the security overview results (identify the core class, don't reinvent it) and the exact URL/parameters of the scheduled tasks overview – verify both in code before building.[cite: 1]

---

## 5. Quick actions

The dashboard is extended with operational tools. This replaces the tedious path through standard navigation for routine interventions.

**One-click impersonate (quick login)**
- **Logic:** a Moodle autocomplete form element directly on the dashboard. Enables immediate search for users (by name or email). Clicking a search hit directly triggers the "Login as" process.
- **Technical implementation:** call `\core\session\manager::loginas($userid, $context)`.
- **Security requirement:** mandatory check of the `moodle/user:loginas` capability in the system context. To prevent privilege escalation, it must be programmatically ruled out that an administrator can log in as the site admin via this function.

**Quick cache purge & debug switch**
- **Logic:** two functional controls on the dashboard. A button for "Purge all caches" and a direct toggle switch for "Developer debug mode on/off".
- **Technical implementation:** use of the core function `purge_all_caches()`. For debugging, `set_config('debug', DEBUG_DEVELOPER)` and `set_config('debugdisplay', 1)` are called (or `0` when disabling).
- **Security requirement:** these interventions in the system configuration require strict CSRF protection via `require_sesskey()` and a check of the `moodle/site:config` permission.

---

## 6. Navigation

Grouped by task type, plain links without logic:[cite: 1]

**Per school** (for each selected code, directly next to the school's metric group)[cite: 1]
- Link to course management for the assigned category (`course/management.php?categoryid=X`)[cite: 1]

**User management**[cite: 1]
- Upload users[cite: 1]
- Manage/upload cohorts[cite: 1]
- Merge Users (`tool_mergeusers`)[cite: 1]

**Course management**[cite: 1]
- Create course[cite: 1]
- Manage categories[cite: 1]
- Course backup/restore[cite: 1]

**Reports/logs**[cite: 1]
- Report Builder (Custom Reports)[cite: 1]
- Site logs[cite: 1]
- Config change log (`report_configlog`)[cite: 1]

**System**[cite: 1]
- Scheduled tasks overview[cite: 1]
- Plugin overview (Site administration → Plugins → Plugins overview)[cite: 1]

**Theme/appearance**[cite: 1]
- Direct link to the Boost Union theme settings (check the exact section URL during implementation, the theme has several tabs)[cite: 1]

---

## 7. Configuration page (plugin settings)

- **Time range** for "new in period" values: choice of 30 / 90 / 180 / 360 days (a single global value for v1, no per-metric setting)[cite: 1]
- **Active school codes**: multi-select from all found complete cohort/category pairs[cite: 1]
- **Warning list**: read-only display of codes with only a one-sided match (cohort without a matching category or vice versa)[cite: 1]

---

## 8. Technical design (proposal)

- **Plugin type:** `local_admincockpit` – own admin page (`admin_externalpage`) under Site administration → Reports, no block (avoids block-region/theme constraints)[cite: 1]
- **No own DB schema needed** – all values are computed at request time (no snapshot mechanism, since the design deliberately relies on a `timecreated` filter instead of historical delta values)[cite: 1]
- **Capability:** `local/admincockpit:view`, default system context, intended only for users with an admin role[cite: 1]
- **Rendering:** own renderer + Mustache templates for the tile layout; numbers computed server-side, no AJAX lazy-loading logic in v1[cite: 1]
- **Reuse core APIs where sensible:** e.g. reference the existing security overview logic instead of reimplementing the checks[cite: 1]
- **Quick-actions security design:** consistent use of Moodle core functions (`\core\session\manager::loginas`, `purge_all_caches()`) combined with strict `sesskey` checks and specific capabilities (`moodle/user:loginas`, `moodle/site:config`), to technically rule out security risks and unauthorized access.

---

## 9. Open points before implementation start

As of step 12 (see CLAUDE.md "Mandatory research points" for the code locations): all four points are resolved, left unchanged here for historical traceability.[cite: 1]

1. Does the course count per school include courses from subcategories? (Assumption: yes, see section 3) – ✅ confirmed and implemented (`classes/metrics/school_metrics.php`)[cite: 1]
2. Identify the exact core class/API for the security overview aggregation – ✅ `\core\check\manager::get_checks('security')` (`classes/metrics/health_signals.php`)[cite: 1]
3. Exact URL/parameters for the scheduled tasks overview and for filtered user lists – ✅ `/admin/tool/task/scheduledtasks.php`; filtered user lists weren't needed (the health-signal click-targets are own drill-down pages, see section 4)[cite: 1]
4. Exact section URL of the Boost Union theme settings – ✅ `/theme/boost_union/settings_overview.php` (`classes/navitems_parser.php`)[cite: 1]

---

## 10. Explicitly out of scope (v1)

- Historical trend/delta values via a snapshot table (see earlier discussion) – only plain `timecreated` filters[cite: 1]
- Further health signals (unconfirmed accounts, auth mismatch, plugin updates, courses without a participant/teacher)[cite: 1]
- Automated cohort↔category mapping via anything other than `idnumber`[cite: 1]
- Block variant (own admin page only in v1)[cite: 1]
- Building a dashboard widget for arbitrary, freely definable SQL queries (effort and complexity are out of proportion to the v1 release).

---

## 11. v2 backlog (as of after v1 release)

### New settings (editable extension)
| Setting | Description |
|---|---|
| Cron status window[cite: 1] | Choice of 6/12/24/48h for counting failed tasks[cite: 1] |
| Ignored security checks[cite: 1] | Multi-select of the check IDs excluded from the security overview traffic light (relevant for structurally unfixable warnings in managed hosting)[cite: 1] |
| Boost Union link visibility[cite: 1] | auto-hide when `theme_boost_union` is not the active theme (no manual checkbox needed – checkable at request time)[cite: 1] |

### New health signal
| Signal | Logic | Click-target | Note |
|---|---|---|---|
| Courses without a participant/teacher[cite: 1] | Courses with no enrolled user in the Student role OR none in the Teacher role, visible[cite: 1] | Own filtered list in the plugin, link into the respective course settings[cite: 1] | Suspected dual benefit: besides genuine dead courses, likely also a good indicator of abandoned test courses – keep both cases in mind during implementation (possibly report them separately, if that turns out to be useful)[cite: 1] |

### Deliberately not pursued further (rejected for good, not just deferred)
- **Own "active threshold" (1/2/4/8 weeks) as a separate setting for "active users" (global) and "active members" (per school)**: rejected, 2026-07-29. Instead of a second, independent time-range setting, both "of which active" (global, `classes/metrics/user_metrics.php`) and "Active members" (per school, `classes/metrics/school_metrics.php`) now use the same `local_admincockpit/timerangedays` value as "New users"/"New joins in period" – a shared time range is easier for admins to reason about than two separate values.
- **Auth-method mismatch as a health signal**: rejected.[cite: 1] Reason: the problem occurred once during the migration and has since been fixed; also, there are legitimate accounts with `auth=manual`, so a blanket signal without school-specific extra configuration would produce too many false positives.[cite: 1] The extra effort of a per-school configuration is out of proportion to the (one-off) benefit.[cite: 1]
- **Plugin-update overview as a health signal**: rejected in favor of a plain navigation link to the existing plugin overview (see section 6, System) – no own aggregation effort needed for something that's primarily a "just take a quick look" need, not an acute action signal.[cite: 1]
- **Delegated school admins/context-sensitive capability**: rejected.[cite: 1] The dashboard deliberately remains exclusively for system-wide administrators; no restricted per-school view for coordinators is planned.[cite: 1] This keeps the capability check everywhere (page + navigation links) a simple `has_capability()` check with no category/context scoping.[cite: 1]

### Implemented after the v1 release
| Item | Details |
|---|---|
| Caching[cite: 1] | Moodle Cache API (MUC), application cache, TTL 1 day, plus a manual "Purge cache now" button directly on the dashboard page (not only reachable via the general cache management)[cite: 1] |
| Bootstrap 4 cleanup[cite: 1] | Full review of all templates for remaining BS4 class names, replaced with BS5 equivalents[cite: 1] |
| Dashboard event[cite: 1] | `local_admincockpit\event\dashboard_viewed`, appears in site logs[cite: 1] |

### Still unchanged in the backlog (no decision yet)
- Historical trend/delta values via a snapshot table[cite: 1]
- Expired enrolments (`enrolenddate` in the past, status active)[cite: 1]
- Self-enrolment without a key/without an end date[cite: 1]
- Block variant[cite: 1]
- Automated cohort↔category mapping beyond `idnumber`[cite: 1]
