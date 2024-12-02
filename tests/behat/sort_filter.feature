@quiz @quiz_heartbeat @javascript
Feature: Check settings of the display table

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


# Default sorting: last name; then first name asc/desc, last name asc/desc
#
  Scenario: Filtering by initial of first or name
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher1"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r0" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r1" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r2" "css_element"
    And I click on "J" "link" in the "#firstinitial_page-item_J" "css_element"
    Then the following should not exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
    When I follow "Reset table preferences"
    Then the following should exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
    When I click on "F" "link" in the "#lastinitial_page-item_F" "css_element"
    Then the following should not exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
      | John Doe      |
    When I follow "Reset table preferences"
    Then the following should exist in the "heartbeatoverview" table:
      | -1-           |
      | Alan Anderson |
      | John Doe      |

  Scenario: Sorting by first or last name
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher1"
    Then I should see "Alan Anderson" in the "#heartbeatoverview_r0" "css_element"
    And I should see "John Doe" in the "#heartbeatoverview_r1" "css_element"
    And I should see "Jane Foo" in the "#heartbeatoverview_r2" "css_element"
    And I follow "Last name"
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






