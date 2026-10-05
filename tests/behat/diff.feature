@ou @ou_vle @mod @mod_ouwiki
Feature: Test diff page

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |

  Scenario: Group selector should not appear on diff page
    Given the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | G1       |
      | G2   | C1     | G2       |
      | G3   | C1     | G3       |
    And the following "activity" exists:
      | activity  | ouwiki             |
      | course    | C1                 |
      | name      | Test Wiki          |
      | intro     | one wiki per group |
      | groupmode | 2                  |
      | section   | 1                  |
      | subwikis  | 1                  |
      | idnumber  | wiki1              |
    And I am on the "wiki1" "activity" page logged in as admin
    And I press "Create page"
    And I set the field "Content" to "This is the group 1 start page"
    And I press "Save changes"
    And I follow "Edit"
    And I set the field "Content" to "This is the edited start page"
    And I press "Save changes"
    When I follow "History"
    Then "Visible groups" "select" should exist
    When I click on "changes" "link" in the "ouwiki_belowtabs" "region"
    Then "Visible groups" "select" should not exist

  Scenario: User selector should not appear on diff page
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following "activity" exists:
      | activity  | ouwiki           |
      | course    | C1               |
      | name      | Test Wiki        |
      | intro     | individual wikis |
      | groupmode | 0                |
      | section   | 1                |
      | subwikis  | 2                |
      | idnumber  | wiki1            |
    And I am on the "wiki1" "activity" page logged in as student1
    And I press "Create page"
    And I set the field "Content" to "This is the student 1 start page"
    And I press "Save changes"
    And I am on the "wiki1" "activity" page logged in as admin
    And I press "Create page"
    And I set the field "Content" to "This is the group 1 start page"
    And I press "Save changes"
    And I follow "Edit"
    And I set the field "Content" to "This is the edited start page"
    And I press "Save changes"
    When I follow "History"
    Then "Viewing wiki for" "select" should exist
    When I click on "changes" "link" in the "ouwiki_belowtabs" "region"
    Then "Viewing wiki for" "select" should not exist
