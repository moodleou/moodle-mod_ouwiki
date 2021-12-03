@ou @ou_vle @mod @mod_ouwiki @ouwiki_lock
Feature: Test locking wiki pages
  As a tutor or member of the module team I need to be able to lock pages from being edited

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | format      | theme |
      | Course 1 | C1        | oustudyplan | osep  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | G1       |
      | G2   | C1     | G2       |
      | G3   | C1     | G3       |

  Scenario: Lock and unlock buttons
    Given I log in as "admin"
    And the following "activities" exist:
      | activity | name      | intro          | course | idnumber | subwikis | groupmode |
      | ouwiki   | Test Wiki | visible groups | C1     | ouwiki1  | 1        | 2         |
    And I am using the OSEP theme
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "Test Wiki"
    And I press "Create page"
    And I set the field "Content" to "This is the start page"
    And I press "Save changes"
    And "Lock page" "button" should exist
    And I press "Lock page"
    And "Unlock page" "button" should exist
    And "//li/a[@title='Edit']" "xpath_element" should not exist
    And I press "Unlock page"
    And "//li/a[@title='Edit']" "xpath_element" should exist

  @javascript @_file_upload
  Scenario: Lock start pages setting
    Given I log in as "admin"
    And I am using the OSEP theme
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on in the OSEP theme
    And I add a "OU wiki" to section "1"
    And I set the following fields to these values:
      | Name             | Test Wiki          |
      | Description      | visible groups     |
      | Sub-wikis        | One wiki per group |
      | Lock start pages | No                 |
      | Group mode       | Visible groups     |
    And I press "Save and display"
    And the "Visible groups" select box should contain "G1"
    And I press "Create page"
    And I set the field "Content" to "This is the group 1 start page"
    And I press "Save changes"
    And "Lock page" "button" should exist

    # Check existing pages get locked when the setting is changed
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Lock start pages | Yes |
    And I press "Save and display"
    And the "Visible groups" select box should contain "G1"
    And "Unlock page" "button" should exist
    
    # Check new empty pages don't get locked
    And I set the field "Visible groups" to "G2"
    And I press "Create page"
    And I set the field "Content" to "This is the group 2 start page"
    And I press "Save changes"
    And "Lock page" "button" should exist

    # Check existing pages don't get locked if the settings page is saved but setting is not changed
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Name             | Test Wiki 1 |
      | Lock start pages | Yes         |
    And I press "Save and display"
    And I set the field "Visible groups" to "G2"
    And "Lock page" "button" should exist

    # Check new pages from templates do get locked
    And I follow "Edit settings"
    And I expand all fieldsets
    And I upload "mod/ouwiki/tests/fixtures/template.xml" file to "Template" filemanager
    And I press "Save and display"
    And I set the field "Visible groups" to "G3"
    And "Unlock page" "button" should exist
