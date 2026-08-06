# Moodle Admin Cockpit (`local_admincockpit`)

[![Moodle Plugin CI](https://github.com/tkorner/moodle-local_admincockpit/actions/workflows/ci.yml/badge.svg)](https://github.com/tkorner/moodle-local_admincockpit/actions/workflows/ci.yml)
[![Moodle Version](https://img.shields.io/badge/Moodle-4.1%2B%20%7C%204.5%2B%20%7C%205.0%2B-orange.svg)](https://moodle.org)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Moodle Plugin Type](https://img.shields.io/badge/Plugin%20Type-local-green.svg)](https://docs.moodle.org/dev/Local_plugins)

**`local_admincockpit`** is an executive single-page dashboard plugin for Moodle administrators. It consolidates global and group-level user metrics, data-hygiene indicators, and infrastructure health signals into one actionable view with direct click-through targets for instant investigation and resolution.

---

## 🌟 Why This Plugin Exists

Getting a clear picture of a Moodle site's health today requires navigating through half a dozen disparate core pages: user management, cohort lists, security reports (`report_security`), scheduled task logs (`tool_task`), and course categories. 

`local_admincockpit` solves this by:
1. **Aggregating Key Indicators**: Putting all essential operational metrics on a single dashboard.
2. **Actionable Health Signals**: Transforming passive statistics into clickable diagnostic links.
3. **Group-Level Data Hygiene**: Grouping user counts, active logins, and course metrics per organizational unit (e.g. Department, Faculty, School, Site).

---

## ✨ Key Features

- 🚦 **Interactive Health Signals**: Real-time traffic-light indicators for:
  - **Cron Execution Status**: Detects stalled or delayed task execution using core `lastcronstart`.
  - **Security Overview**: Directly surfaces `report_security` check results via core `\core\check\manager`.
  - **Data Hygiene Alerts**: Identifies duplicate user emails and courses missing end dates or visibility.
- 🏢 **Organizational Grouping ("Groupings / Schools")**: Automatically matches system-wide **Cohorts** with top-level **Course Categories** using `idnumber`.
- 🔗 **Grouped Admin Shortcuts**: Quick access to high-frequency administrative tools (restore course, bulk user actions, course creation).
- 🧩 **Extensible via Hooks**: Third-party plugins can inject custom health signals into the dashboard using Moodle's native Hook API.
- ⚡ **Zero Database Overhead**: Computes metrics dynamically at request time without maintaining custom database tables.

---

## 📐 Design Principles & Architecture

- **No Custom Database Tables**: Operates on live Moodle core tables (`timecreated`, `timeadded`, `lastaccess`), ensuring 100% data integrity and zero schema drift.
- **Strict `idnumber` Matching**: Matches cohorts to top-level course categories exclusively by `idnumber` (never by display name) to prevent accidental collisions.
- **Separation of Concerns**: Metric calculation classes (`classes/metrics/*.php`) are completely decoupled from rendering (`classes/output/`).
- **Core API Reuse**: Prefers native Moodle core APIs over custom logic:
  - Security checks: `\core\check\manager::get_checks('security')`
  - Cron monitoring: `admin/tool/task/classes/check/cronrunning.php`
  - Category tree traversal: `core_course_category` prefix-matching (`path LIKE '{path}/%'`)
  - Status formatting: `format_time()` and core `lib/templates/check/result/*.mustache` templates.

---

## 🔌 Extending: Adding Custom Health Signals

Any third-party Moodle plugin can register custom health signals on the Admin Cockpit dashboard using the `local_admincockpit\hook\health_signals` hook:

### 1. Register the Callback in `db/hooks.php`
```php
// your_plugin/db/hooks.php
$callbacks = [
    [
        'hook' => \local_admincockpit\hook\health_signals::class,
        'callback' => [\your_plugin\hook_listener::class, 'add_signal'],
    ],
];
```

### 2. Implement the Listener Class
```php
// your_plugin/classes/hook_listener.php
namespace your_plugin;

use local_admincockpit\health_signal;
use local_admincockpit\hook\health_signals;

class hook_listener {
    public static function add_signal(health_signals $hook): void {
        $hook->add_signal(new health_signal(
            component: 'your_plugin',
            key: 'yoursignal',
            label: get_string('yoursignallabel', 'your_plugin'),
            value: 5,
            status: health_signal::STATUS_WARNING,
            url: new \moodle_url('/your_plugin/admin_action.php')
        ));
    }
}
```

---

## 🚀 Installation & Setup

1. Clone or extract this plugin into your Moodle installation at `local/admincockpit`:
   ```bash
   git clone https://github.com/tkorner/moodle-local_admincockpit.git local/admincockpit
   ```
2. Run the Moodle CLI upgrade command:
   ```bash
   php admin/cli/upgrade.php
   ```
3. Purge Moodle caches:
   ```bash
   php admin/cli/purge_caches.php
   ```
4. Access the dashboard under **Site Administration → Reports → Admin Cockpit** (or via URL `/local/admincockpit/index.php`).

---

## 🧪 Testing & Quality Assurance

- **PHPUnit Tests**:
  ```bash
  vendor/bin/phpunit local_admincockpit/tests/metrics_test.php
  ```
- **Behat Acceptance Tests**:
  ```bash
  vendor/bin/behat --config /path/to/behat.yml local_admincockpit/tests/behat/cockpit.feature
  ```

---

## 🔒 Privacy & GDPR Compliance

This plugin implements the Moodle Privacy API (`\core_privacy\local\metadata\null_provider`). It does not store or process any personal data independently.

---

## 📜 License

Licensed under the [GNU General Public License v3.0 or later](http://www.gnu.org/licenses/gpl.html).  
Copyright (C) 2026 Antigravity & Contributors.
