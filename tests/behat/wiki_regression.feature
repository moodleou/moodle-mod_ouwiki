@ou @ou_vle @mod @mod_ouwiki @wiki_basic
Feature: Test OUwiki regressions
  In order to use ouwiki features
  As a user
  I need to be able to complete basic operations

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | G1       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |

  @javascript @_file_upload
  Scenario: Test template
    Given I log in as "admin"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OU wiki" to section "0" using the activity chooser
    And I set the following fields to these values:
      | Name        | W.T                |
      | Description | Separate groups    |
      | Sub-wikis   | One wiki per group |
      | Group mode  | Separate groups    |
    And I upload "mod/ouwiki/tests/fixtures/template.xml" file to "Template" filemanager
    And following "template.xml" should download between "1000" and "1500" bytes
    And I press "Save and display"
    And I am on the "W.T" "ouwiki activity" page
    And I click on "Wiki index" "link"
    And I click on "Geckos" "link"
    Then I should see "C26"
    And I click on "Wiki index" "link"
    And I click on "Zombies" "link"
    Then I should see "C30"
    And I click on "Wiki index" "link"
    And I click on "Frogs" "link"
    Then I should see "C24"

  @javascript
  Scenario: Test time limit
    Given I log in as "admin"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add an ouwiki activity to course "Course 1" section "1" and I fill the form with:
      | Name                  | W.WC                    |
      | Description           | wiki with no groups     |
      | Group mode            | No groups               |
      | Time allowed for edit | 3 minutes (for testing) |
    And I am on "Course 1" course homepage
    And I am on the "W.WC" "ouwiki activity" page
    And I press "Create page"
    And I set the field "Content" to "C10"
    And I press "Save changes"
    And I follow "Edit"
    # Make your changes and click Save or Cancel before the remaining time (to right) reaches zero.
    Then I set the field "Content" to "C11"
    And I wait "100" seconds
    Then I should see "Please finish or cancel your edit now. If you do not save before time runs out, your changes will be saved automatically."
    # Check automatic submit back to previous page.
    Given I wait "90" seconds
    Then I should see "C11"
    And I should not see "C10"

  @javascript
  Scenario: Test editing for past, future and together
    Given I log in as "admin"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OU wiki" to section "1" using the activity chooser
    # Values for Future.
    And I set the following fields to these values:
      | Name               | W.SG                                |
      | Description        | Allow edit for past,present, Future |
      | Sub-wikis          | One wiki per group                  |
      | Group mode         | Separate groups                     |
      | Allow editing from | ## +2 days ##                       |
    And I press "Save and display"
    Then I should see "This wiki is currently locked."
    And I am on "Course 1" course homepage
    And I add a "OU wiki" to section "1" using the activity chooser
    # Values for Past.
    And I set the following fields to these values:
      | Name                 | W.SG                                |
      | Description          | Allow edit for past,present, Future |
      | Sub-wikis            | One wiki per group                  |
      | Group mode           | Separate groups                     |
      | Prevent editing from | ## -2 days ##                       |
    And I press "Save and display"
    Then I should see "This wiki is currently locked and can no longer be edited."
    And I am on "Course 1" course homepage
    And I add a "OU wiki" to section "1" using the activity chooser
    # Values for Past and Future.
    And I set the following fields to these values:
      | Name                 | W.SG                                |
      | Description          | Allow edit for past,present, Future |
      | Sub-wikis            | One wiki per group                  |
      | Group mode           | Separate groups                     |
      | Allow editing from   | ## -1 days ##                       |
      | Prevent editing from | ## +1 days ##                       |
    And I press "Save and display"
    Then I should see "This wiki's start page has not yet been created."
    And I press "Create page"
    And I set the field "Content" to "C17"
    And I press "Save changes"
    Then I should see "C17"
