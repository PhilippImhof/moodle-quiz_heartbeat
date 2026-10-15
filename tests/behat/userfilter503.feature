@quiz @quiz_heartbeat @javascript
Feature: Filter for user name via the action bar widget

  Background:
    Given the site is running Moodle version 5.3 or higher
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher  | T1        | Teacher  |
      | student1 | Alice     | Student1 |
      | student2 | Bob       | Student2 |
      | student3 | Charlie   | Student3 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
      | group2 | C1     | G2       |
      | group3 | C1     | G3       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
      | student3 | G2    |
      | student3 | G3    |
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

  Scenario: Filtering among all names if no group selected
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher"
    And I set the field "Search users" to "ice"
    And I wait until "Bob Student2" "option_role" does not exist
    And I wait until "Charlie Student3" "option_role" does not exist
    When I click on "Alice Student1" "list_item"
    And I wait until the page is ready
    Then the following should exist in the "heartbeatoverview" table:
      | -1-            |
      | Alice Student1 |
    And the following should not exist in the "heartbeatoverview" table:
      | -1-              |
      | Bob Student2     |
      | Charlie Student3 |
    When I follow "Clear"
    Then the following should exist in the "heartbeatoverview" table:
      | -1-              |
      | Alice Student1   |
      | Bob Student2     |
      | Charlie Student3 |

  Scenario: Filtering among group students only, if group selected
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher"
    And I click on "group2" in the "Search groups" search combo box
    And I set the field "Search users" to "ice"
    And I wait until "Alice Student1" "option_role" does not exist
    Then I should see "No results for" in the ".usersearchdropdown" "css_element"

  Scenario: Filter for all users matching a string
    When I am on the "Quiz 1" "quiz_heartbeat > heartbeat report" page logged in as "teacher"
    And I set the field "Search users" to "li"
    And I wait until "Bob Student2" "option_role" does not exist
    When I click on "View all results (2)" "list_item"
    And I wait until the page is ready
    Then the following should exist in the "heartbeatoverview" table:
      | -1-              |
      | Alice Student1   |
      | Charlie Student3 |
    And the following should not exist in the "heartbeatoverview" table:
      | -1-          |
      | Bob Student2 |
