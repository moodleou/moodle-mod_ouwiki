@ou @ou_vle @mod @mod_ouwiki @ouwiki_feed
Feature: Test that Atom and RSS feeds are displayed for separate and visible groups in chrome
  In order to use ouwiki features
  As a user
  I need to be able to display Atom and RSS feeds correctly.
  Note: 'No groups - verify Atom and RSS' is covered in wiki_regression.feature test

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1 | C1 | G1 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |

  @javascript
  Scenario: Separate groups - verify Atom and RSS
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OU wiki" to section "1"
    And I set the following fields to these values:
      | Name | W.SG |
      | Description | Separate groups |
      | Sub-wikis | One wiki per group |
      | Group mode | Separate groups |
    And I press "Save and return to course"
      # test for student1 in group 1
    And I log out
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.SG"
    Then I should see "Viewing wiki for: G1" in the ".ouw_subwiki" "css_element"
    And "Create page" "button" should exist
    # Create start page start page for group 1
    And I press "Create page"
    And I set the field "Content" to "C2 separate groups wiki"
    And I press "Save changes"
	And I follow "Wiki changes"
    And I follow "RSS"
    Then I should see "rss version"
    And I am on "Course 1" course homepage
    And I follow "W.SG"
    And I follow "Wiki changes"
    And I follow "Atom"
    Then I should not see "rss version"

  @javascript
  Scenario: Visible groups - verify Atom and RSS
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "OU wiki" to section "1" and I fill the form with:
      | Name | W.VG |
      | Description | visible groups |
      | Sub-wikis | One wiki per group |
      | Group mode | Visible groups |
    And I log out
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.VG"
    # Create start page
    And I press "Create page"
    And I set the field "Content" to "C3 visible groups wiki"
    And I press "Save changes"
	And I follow "Wiki changes"
    And I follow "RSS"
    Then I should see "rss version"
    And I am on "Course 1" course homepage
    And I follow "W.VG"
    And I follow "Wiki changes"
    And I follow "Atom"
    Then I should not see "rss version"
