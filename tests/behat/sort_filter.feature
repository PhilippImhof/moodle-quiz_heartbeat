@quiz @quiz_heartbeat @javascript
Feature: Check settings of the display table pre 5.3

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | T1        | Teacher1 |
      | student1 | John      | Doe      |
      | student2 | Jane      | Foo      |
      | student3 | Alan      | Anderson |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity | name   | intro              | course | groupmode |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | 1         |
    And the following "questions" exist:
      | questioncategory | qtype | name | questiontext   |
      | Test questions   | essay | Q1   | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | Q1       | 1    | 1.0     |
    And user "student1" has started an attempt at quiz "Quiz 1"
    And user "student1" has input answers in their attempt at quiz "Quiz 1":
      | slot | response |
      | 1    | Foo Bar  |
    And user "student2" has started an attempt at quiz "Quiz 1"
    And user "student2" has input answers in their attempt at quiz "Quiz 1":
      | slot | response  |
      | 1    | Foo Bar 2 |
    And user "student3" has started an attempt at quiz "Quiz 1"
    And user "student3" has input answers in their attempt at quiz "Quiz 1":
      | slot | response  |
      | 1    | Foo Bar 3 |

  Scenario: Filtering by initial of first or last name pre 5.3
    Given the site is running Moodle version 5.2 or lower
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher1"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r0" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r1" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r2" "css_element"
    And I click on "J" "link" in the ".firstinitial" "css_element"
    Then the following should not exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
    When I follow "Reset table preferences"
    Then the following should exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
    When I click on "F" "link" in the ".lastinitial" "css_element"
    Then the following should not exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
      | John Doe      |
    When I follow "Reset table preferences"
    Then the following should exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
      | John Doe      |

  Scenario: Filtering by initial of first or last name for 5.3+
    Given the site is running Moodle version 5.3 or higher
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher1"
    Then "" "quiz_heartbeat > Legacy initials bars" should not exist
    And I should see "Alan Anderson" in the "#heartbeatoverview_r0" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r1" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r2" "css_element"
    And I select "J" in the "First name" "core_grades > initials bar"
    And I press "Apply"
    Then the following should not exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
    When I follow "Clear all"
    Then the following should exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
    When I select "F" in the "Last name" "core_grades > initials bar"
    And I press "Apply"
    Then the following should not exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
      | John Doe      |
    When I follow "Clear all"
    Then the following should exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
      | John Doe      |

  Scenario: Sorting by first or last name
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher1"
    And I follow "Last name"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r0" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r1" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r2" "css_element"
    When I follow "Last name"
    And I should see "Jane Foo" in the "#heartbeatoverview_r0" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r1" "css_element"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r2" "css_element"
    When I follow "First name"
    And I should see "John Doe" in the "#heartbeatoverview_r0" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r1" "css_element"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r2" "css_element"
    When I follow "First name"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r0" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r1" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r2" "css_element"

  Scenario: Sorting by time since last contact
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher1"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r0" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r1" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r2" "css_element"
    # We cannot know for sure in what order the names will appear, so we don't check the sort order.
    # But we still do the sorting to make sure that there is no error in the DB query.
    When I follow "Time since last contact"
    Then I should see "Alan Anderson"
    When I follow "Time since last contact"
    Then I should see "Alan Anderson"
