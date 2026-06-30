@ou @ou_vle @mod @mod_ouwiki @wiki_basic
Feature: Test OUwiki regressions
  In order to use ouwiki features
  As a user
  I need to be able to complete basic operations

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
      | student2 | Student   | 2        | student2@asd.com |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | teacher1 | C1     | editingteacher |
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

  @javascript @_file_upload
  Scenario: Images and attachments
    Given I log in as "student1"
    And the following "activity" exists:
      | activity  | ouwiki              |
      | course    | C1                  |
      | name      | W.WX                |
      | intro     | wiki with no groups |
      | groupmode | 0                   |
      | section   | 1                   |
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    When I press "Create page"
    And I set the field "Content" to "C23 no groups wiki"
    # Upload image 1.
    And I upload "lib/tests/fixtures/1.jpg" file to "Attachments" filemanager
    And I press "Save changes"
    Then I should see "1.jpg" in the ".ouwiki-post-attachments" "css_element"
    # Edit and Delete the image 1.
    And I click on "Edit" "link"
    And I click on "1.jpg" "link"
    And I click on "Delete" "button" in the ".moodle-dialogue-wrap" "css_element"
    And I press "Yes"
    # Upload image 2.
    And I upload "lib/tests/fixtures/2.jpg" file to "Attachments" filemanager
    And I press "Save changes"
    And I should not see "1.jpg" in the ".ouwiki-post-attachments" "css_element"
    And I should see "2.jpg" in the ".ouwiki-post-attachments" "css_element"
    # View history page.
    And I click on "History" "link"
    # View old version .
    And I click on "View" "link" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    And I should see "You are viewing an old version of this page."
    And "1.jpg" "link" should be visible
    # View current version.
    And I click on "Next: Current version" "link"
    And I should see "2.jpg" in the ".ouwiki-post-attachments" "css_element"

  @javascript
  Scenario: Verify the warning message when the page is edited by another user and the lock is overridden
    # Login as student 1.
    Given I log in as "student1" in session "student1" in ouwiki
    And the following "activity" exists:
      | activity  | ouwiki              |
      | course    | C1                  |
      | name      | W.WX                |
      | intro     | wiki with no groups |
      | groupmode | 0                   |
      | section   | 1                   |
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    When I press "Create page"
    And I set the field "Content" to "student 1 content"
    And I press "Save changes"
    # Click edit link and keep window open without saving changes.
    And I click on "Edit" "link"
    # Login as student 2 in new session.
    And I log in as "student2" in session "student2" in ouwiki
    And  I switch to session "student2" in ouwiki
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    And I click on "Edit" "link"
    # Verify warning message when the page is edited by student 1.
    Then I should see "This page is being edited by somebody else."
    And I should see "Student 1 started editing this page"
    And "Override lock" "button" should not be visible
    And "Try again" "button" should be visible
    And "Cancel" "button" should be visible
    And I press "Try again"
    And I should see "Student 1 started editing this page"
    And I press "Cancel"
    And I should see "student 1 content" in the ".ouwiki_content" "css_element"
    # Login as teacher in new session.
    And I log in as "teacher1" in session "teacher" in ouwiki
    And I switch to session "teacher" in ouwiki
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    And I click on "Edit" "link"
    # Verify warning message when the page is edited by student 1.
    And I should see "This page is being edited by somebody else."
    And "Override lock" "button" should be visible
    And "Try again" "button" should be visible
    And "Cancel" "button" should be visible
    # Verify teacher’s ability to override an edit.
    And I press "Override lock"
    And I set the field "Content" to "teacher 1 content"
    And I press "Save changes"
    And I should see "teacher 1 content" in the ".ouwiki_content" "css_element"
    # Switch to Student 2 session and verify the teacher’s changes.
    And I switch to session "student2" in ouwiki
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    And I should see "teacher 1 content" in the ".ouwiki_content" "css_element"
