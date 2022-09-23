@ou @ou_vle @mod @mod_ouwiki @lastmodified
Feature: Show last updated information on OU Wiki activity link
  In know when a wiki was last updated
  As a user
  I need to see the last post date on the wiki link

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | G1       |
      | G2   | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |

  Scenario: No groups - basic test etc
    Given I log in as "admin"
    And the following "activity" exists:
      | activity  | ouwiki              |
      | course    | C1                  |
      | name      | W.WC                |
      | intro     | wiki with no groups |
      | groupmode | 0                   |
      | section   | 1                   |
    And I am on "Course 1" course homepage
    And I turn editing mode on
    Then I should see "W.WC"
    And ".lastmodtext.ouwikilmt" "css_element" should not exist
    Given I follow "W.WC"
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "C1 no groups wiki"
    And I press "Save changes"
    When I am on "Course 1" course homepage
    Then ".lastmodtext.ouwikilmt" "css_element" should exist

  Scenario: Group wikis
    Given the following "activity" exists:
      | activity  | ouwiki          |
      | course    | C1              |
      | name      | W.SG            |
      | intro     | Separate groups |
      | groupmode | 1               |
      | section   | 1               |
      | subwikis  | 1               |
    And the following "activity" exists:
      | activity  | ouwiki         |
      | course    | C1             |
      | name      | W.VG           |
      | intro     | Visible groups |
      | groupmode | 2              |
      | section   | 2              |
      | subwikis  | 1              |
    # Test for student1 in group 1.
    Given I log in as "student1"
    And I am on site homepage
    When I am on "Course 1" course homepage
    # Check for Last edit details
    Then "/descendant::div[contains(@class, 'activity-item')][1]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should not exist
    And "/descendant::div[contains(@class, 'activity-item')][2]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should not exist
    Given I follow "W.SG"
    And I press "Create page"
    And I set the field "Content" to "C2 separate groups wiki"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    When I follow "W.VG"
    And I press "Create page"
    And I set the field "Content" to "C3 visible groups wiki"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    # Check for Last edit details
    Then "/descendant::div[contains(@class, 'activity-item')][1]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should exist
    And "/descendant::div[contains(@class, 'activity-item')][2]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should exist
    And I log out
    # Test for student 2 in group 2.
    Given I log in as "student2"
    And I am on site homepage
    When I am on "Course 1" course homepage
    # Check for Last edit details
    Then "/descendant::div[contains(@class, 'activity-item')][1]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should not exist
    And "/descendant::div[contains(@class, 'activity-item')][2]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should exist

  Scenario: Individual wikis
    Given I log in as "admin"
    And the following "activity" exists:
      | activity  | ouwiki           |
      | course    | C1               |
      | name      | W.SI             |
      | intro     | individual wikis |
      | groupmode | 0                |
      | section   | 1                |
      | subwikis  | 2                |
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I follow "W.SI"
    And I press "Create page"
    And I set the field "Content" to "C4 individual wiki"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    # Check for Last edit details
    Then "/descendant::div[contains(@class, 'activity-item')][1]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should exist
    And I log out
    # Checking individual wiki for student 1 (visible info only).
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    # Check for Last edit details
    Then "/descendant::div[contains(@class, 'activity-item')][1]//span[@class='lastmodtext ouwikilmt']" "xpath_element" should not exist
