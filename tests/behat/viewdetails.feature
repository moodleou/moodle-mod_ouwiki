@ou @ou_vle @mod @mod_ouwiki @ouwiki_viewdetails
Feature: Test view details against a user
  In order to use ouwiki features
  As a user
  I need to be able to view user participation details

  Background:
    Given the following "users" exist:
        | username | firstname | lastname | email |
        | teacher1 | Teacher | 1 | teacher1@asd.com |
        | student1 | Student | 1 | student1@asd.com |
        | student2 | Student | 2 | student2@asd.com |
        | teacher2 | Teacher | 2 | teacher2@asd.com |
    And the following "courses" exist:
        | fullname | shortname | category |
        | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
        | user | course | role |
        | teacher1 | C1 | editingteacher |
        | student1 | C1 | student |
        | student2 | C1 | student |
        | teacher2 | C1 | teacher |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "OU wiki" to section "1" and I fill the form with:
      | Name | Test 1 |
      | Description | Test ouwiki description |
      | grade[modgrade_type] | Point |
      | grade[modgrade_point] | 100 |
    And I log out

  @javascript
  Scenario: View details
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test 1"
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "Test content 1"
    And I press "Save changes"
    And I log out
    # Add extra content as student.
    Given I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test 1"
    When I click on "Edit" "link"
    And I set the field "Content" to "Test content 1 some other stuff"
    And I press "Save changes"
    And I log out
    # Set up 'frog' wiki subpage.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test 1"
    And I click on "Edit" "link"
    And I set the field "Content" to "Test content 1 some other stuff [[frog]]"
    And I press "Save changes"
    When I click on "frog" "link"
    Then "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "Frog test "
    When I press "Save changes"
    Then "Start page" "link" should exist
    When I click on "Start page" "link"
    And  "frog" "link" should exist
    And I log out
    # Add extra content to 'frog' page as student.
    Given I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test 1"
    And  "frog" "link" should exist
    And I click on "frog" "link"
    When I click on "Edit" "link"
    And I set the field "Content" to "Frog test content stuff"
    And I press "Save changes"
    Then "Start page" "link" should exist
    When I click on "Start page" "link"
    Then  "frog" "link" should exist    
    And I log out
    # Check student has particpated and their grade can be displayed.
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test 1"
    Given I click on "Participation by user" "link"
    When I click on "detail" "link" in the "Student 2" "table_row"
    And I should see "User grade"
    And I should see "+3"
    And I should see "+2"
