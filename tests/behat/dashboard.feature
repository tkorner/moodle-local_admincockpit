@local @local_admincockpit
Feature: Open the Admin Cockpit dashboard
  In order to see site metrics and health signals in one place
  As an administrator or manager
  I need to reach the dashboard and see only the actions my role allows

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  Scenario: An administrator reaches the dashboard through the site administration
    Given I log in as "admin"
    When I navigate to "Reports > Admin Cockpit" in site administration
    Then I should see "Global user metrics"
    And I should see "Health signals"
    And I should see "Navigation"
    And I should see "Purge ALL site caches"

  Scenario: An administrator changes the time range on the dashboard
    Given I log in as "admin"
    And I visit "/local/admincockpit/index.php"
    When I set the field "local-admincockpit-timerangedays" to "30 days"
    And I press "Update"
    Then the field "local-admincockpit-timerangedays" matches value "30 days"

  Scenario: An administrator refreshes the cached numbers
    Given I log in as "admin"
    And I visit "/local/admincockpit/index.php"
    When I press "Refresh now"
    Then I should see "Cache cleared - the numbers below are freshly computed."
    And I should see "Global user metrics"

  Scenario: A manager sees the dashboard but not the site-wide cache purge
    Given I log in as "manager1"
    When I visit "/local/admincockpit/index.php"
    Then I should see "Global user metrics"
    And I should see "Refresh now"
    And I should not see "Purge ALL site caches"
    And I should not see "Turn on developer debugging"
    And I should not see "Debugging is set in config.php"
