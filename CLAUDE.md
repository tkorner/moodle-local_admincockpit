# CLAUDE.md – local_admincockpit

Context file for Claude Code sessions in this project. Read before starting work.

---

## Project overview

Moodle plugin `local_admincockpit`: navigation and metrics dashboard for administrators. Shows user/course metrics per "school" (cohort + top-level category, linked via `idnumber`), health signals with a call-to-action, and direct links to frequently used admin pages.

**Source of truth for requirements:** `SPEC-admincockpit.md` in the same directory. If this file and the spec disagree, the spec wins – ask, don't decide unilaterally.

**Implementation sequence:** `claude-code-prompt-admincockpit.md` in the same directory. Contains the steps, to be worked through **one at a time** – don't implement several steps at once even if the context would allow it. Each step's result is reviewed before the next one starts. The list has grown over time (intermediate steps like 0b, 7b–7h, 13 were added) – the "Progress" line further down and the file itself are the source of truth for the current state, not a fixed step count.

---

## Target environment

- **Moodle version:** 5.2.x
- **Local plugin folder:** `/Users/tkorner/Documents/claude/plugins/local/admincockpit/`
- **Docker mount target:** `/var/www/html/public/local/admincockpit/` (Moodle 5.x `public/` directory layout - verified via `docker inspect claude-moodle-1`, not the older flat `/var/www/html/local/` layout)
- **Docker Compose mount:** `./plugins/local:/var/www/html/public/local`
- **Container:** `claude-moodle-1` (image `erseco/alpine-moodle`), DB in `claude-mariadb-1`
- **PHP/DB:** standard Alpine Moodle setup, no special configuration known

---

## Git

- **Repo:** `tkorner/moodle-local_admincockpit` (private)
- Repo root = plugin root (i.e. `version.php` sits directly in the repo root, no `local/admincockpit/` subfolder inside the git repo itself – Moodle convention for single-plugin repos)
- Commit after each completed and reviewed step from the prompt document, so individual steps can be rolled back if needed
- **Commit messages, release notes, PR descriptions: always in English**, regardless of the language the session itself is conducted in - this is a public GitHub repo (see "Marketplace-Submission" below)

---

## Core architectural principles (from the spec)

- **No own DB schema.** All metrics are computed at request time (`timecreated` filtering instead of a snapshot history). If it ever looks like a table would actually be needed – stop and ask, that would contradict a deliberate scope decision.
- **Cohort↔category matching exclusively via `idnumber`**, never via display names.
- **Business logic strictly separated from output:** metric classes (`classes/metrics/...`) know nothing about pages/blocks. Reason: a later conversion from "own page" to "block" is left open (the implementation deliberately starts as a page, not a block) and should be able to leave the calculation logic untouched.
- **Health signals are always number + click-target**, never a plain statistic without an action.

---

## Mandatory research points (don't guess)

The following things must not be implemented from training data/assumption, but must be verified against the actual Moodle core code or the installed instance. All four were open at the start of implementation; as of step 12 all are verified and referenced in the code (see README "Reused core APIs" for details):

- ✅ Core class/method behind the security overview report (`admin/report/security/`) – reused: `\core\check\manager::get_checks('security')` (`classes/metrics/health_signals.php`)
- ✅ Core table/class for task log/cron status – `get_config('tool_task', 'lastcronstart')` + `task_log.result` (`classes/metrics/health_signals.php`)
- ✅ Exact URL/parameters for the scheduled tasks overview – `/admin/tool/task/scheduledtasks.php` (`classes/navitems_parser.php`)
- ✅ Exact section URL of the `theme_boost_union` settings – `/theme/boost_union/settings_overview.php` (`classes/navitems_parser.php`)
- If unsure about future, not-yet-verified points: ask explicitly instead of implementing a plausible-sounding but unverified assumption

---

## Testing strategy

No local GUI. Three levels (as of the 2026-07-16 code review – contrary to the long-standing assumption in this document, PHPUnit is actually installed and runnable on the running Docker instance, see point 2):

1. **CLI smoke scripts** (`cli/verify_*.php`) – immediate feedback during the session, run directly against the real data of the running Docker instance, no test framework:
   ```bash
   docker exec -it claude-moodle-1 php /var/www/html/public/local/admincockpit/cli/verify_school_matcher.php
   ```
   Not a substitute for real tests, just a visual check during development.

2. **PHPUnit directly in the container** – runnable, `vendor/bin/phpunit` exists (as a Composer dev dependency; if it was removed by a container rebuild, `composer install` in the container brings it back, see 2026-07-29 note below):
   ```bash
   docker exec -it claude-moodle-1 sh -c "cd /var/www/html && vendor/bin/phpunit --configuration phpunit.xml --testsuite local_admincockpit_testsuite"
   ```
   Immediate, complete test feedback without waiting for CI – new `*_test.php` files under `tests/` are picked up automatically (the registered test suite scans by file suffix), no `--buildconfig` needed; that would only be required for a brand-new test suite/plugin component. After every change to `version.php` (e.g. a version bump), PHPUnit reports "was initialised for different version" and needs a one-off re-init:

   ```bash
   docker exec -it claude-moodle-1 sh -c "cd /var/www/html && php public/admin/tool/phpunit/cli/init.php"
   ```

   **2026-07-29 note:** on that date `vendor/bin/phpunit` was missing (phpunit/phpunit dev dependency not installed, despite this doc saying it's runnable) - fixed with `docker exec claude-moodle-1 sh -c "cd /var/www/html && composer install"`, then the re-init above. Unclear yet whether this was a one-off (e.g. after a container rebuild) or will recur - check first before assuming it's needed again.

3. **GitHub Actions with `moodlehq/moodle-plugin-ci`** – additional safety net on every push/PR (independent environment/matrix: PHP 8.3/8.4 × Moodle 5.1/5.2 × MariaDB/PostgreSQL, PostgreSQL added since the 2026-07-22 marketplace-submission prep, not just MariaDB anymore): PHPUnit, Behat, phpcs (moodle ruleset), phplint, mustache lint etc. Check the result in the GitHub Actions tab even if point 2 was already green locally – other PHP/Moodle versions and the DB engine can differ.

**Progress:** Steps 0 through 12 are implemented and released (see release 1.0.0/1.1.0/1.1.1/2.0.0 commits); the code-review follow-up pass (2026-07-16) is complete and released (1.1.1). Marketplace-submission prep has been running since 2026-07-22, see the "Marketplace-Submission" section below. Step 13 (fix: "active" metrics follow the selected time range instead of a fixed 4 weeks, both globally and per school) is implemented and released as 2.1.0/2.1.1 (2026-07-29).

---

## Coding standards

- Moodle Coding Guidelines / Moodle Coding Style (phpcs with the moodle ruleset, if available locally)
- Core-contribution-grade quality from the start, even though (unlike `qbank_cffpoc`) this isn't intended for a core merge
- Moodle Data Manipulation API for all DB access (no raw mysqli, no unfiltered string concatenation in SQL)
- PHPUnit tests for all metric/matching classes (see prompt document, steps 1, 3, 4)
- Language files: maintain `lang/en/` and `lang/de/` in parallel, no hardcoded strings in the code

---

## Known open assumptions (from SPEC section 8)

As of step 12:

1. Course count per school including subcategories – assumption "yes", explicitly commented in the code (`classes/metrics/school_metrics.php`), deliberately revisable, not "open" in the sense of undecided
2. Security overview aggregation – ✅ identified, see research points above
3. Scheduled tasks URL – ✅ verified, see research points above
4. Boost Union settings URL – ✅ verified, see research points above

Further decisions documented along the way (no longer open, but deliberate) live in README.md under "Known open assumptions", including the interpretation of the SPEC navigation items and the missing capability restriction on the default navigation links (step 7h).

Extend this list as needed if new open points come up during implementation – don't silently make assumptions and carry on.

---

## Marketplace submission

Checklist lives outside this repo (`Marketplace.md` in the Claude project directory, not in the plugin repo itself). As of 2026-07-22:

- **Compliance audit done:** license headers (all `.php`), GPLv3 `LICENSE`, privacy provider (`null_provider`, since there's no own DB schema, see above), no hits for `eval()`/`unserialize()`/raw `$_REQUEST`/`$_GET`/`$_POST`, no raw SQL concatenation (`$DB->...` with placeholders throughout), settings exclusively via `get_config('local_admincockpit', ...)`/`config_plugins`, no `composer.json` needed, public GitHub issue tracker in place (repo `tkorner/moodle-local_admincockpit`, public, issues enabled) – all ✅, no fixes needed.
- **CI matrix extended:** previously MariaDB only, now PostgreSQL as well (guideline requires both cross-DB engines) – see `.github/workflows/ci.yml`.
- **Maturity raised:** `version.php` from `MATURITY_RC`/`1.1.1` to `MATURITY_STABLE`/`1.2.0` for the submission.
- **Deliberate deviation documented:** `lang/de/` ships already ahead of official AMOS approval – see README.md "Known open assumptions", last item.
- **Not automated (deliberately, see 2026-07-22 check-in):** screenshots (dashboard + settings) are taken by the user themselves via browser login; actually testing the release zip on a fresh instance (checklist item 8 – the `admin_externalpage_setup()` error class) also remains a manual task before the next day.
- **Git tag + GitHub release + actual submission to marketplace.moodle.com:** explicitly only after checking in again, not automatically at the end of this session.
- **Frankenstyle rename (2026-07-23):** `local_admindashboard` was already taken by two unrelated GitHub repos (`UzainAliSiddiqui/moodle-local_admindashboard`, an embedded plugin in `Integer-Training/integermoodle1`) and therefore unusable for the marketplace submission. New, collision-checked name: `local_admincockpit`, visible product name consistently changed to "Admin Cockpit". GitHub repo renamed accordingly (`tkorner/moodle-local_admincockpit`). Version bumped to `2026072300`/`2.0.0` (a major bump rather than a patch, since the rename is a breaking change for existing installations – the old plugin must be uninstalled before installing `local_admincockpit`). Old tags/releases `v1.0.0`–`v1.2.0` remain under the old name as historical commits (not deleted/rewritten); new history from `v2.0.0` onward runs under `local_admincockpit`.

---

## Additional external reference

Also consult for security and CI best practices (if available/cloned locally):
**MoMoPDA** – "Modular Moodle Plugin Development Assistant", originally by wilenius
(https://github.com/wilenius/momopda), forked at https://github.com/tkorner/momopda,
GPL-3.0. Relevant generic building blocks: `.prompts/core/security-checklist.md`,
`.prompts/core/ci-validation.md`, `.prompts/patterns/database.md`,
`.prompts/patterns/forms.md`, `.prompts/patterns/navigation.md`,
`.prompts/patterns/api-usage.md`. No plugin-type-specific `local` guide exists
(as of now) – the guides there cover block/enrol/filter/mod/qbank/qtype/report/
tiny, local plugins only via the generic core files. Use as an additional
cross-reference where helpful, not as the primary guide (that remains SPEC +
claude-code-prompt-admincockpit.md).

---

## Do not

- Don't auto-generate runbook/documentation entries – that happens separately, after completion and its own review
- Don't build a block variant in parallel – v1 is deliberately page-only
- Don't add health signals beyond the four named in the spec without checking in first (deliberately reduced v1 scope)
