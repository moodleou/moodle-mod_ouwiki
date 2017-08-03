@ou @ou_vle @mod @mod_ouwiki @ouwiki_importpages
Feature: import pages into one wiki from another
  As an editor of an ouwiki
  In order to save time
  I need to copy wiki pages from one wiki to another

  Background:
    Given the following "users" exist:
       | username | firstname | lastname   | email            |
       | student1 | Student   | One        | student1@asd.com |
    And the following "courses" exist:
       | fullname                 | shortname | format |
       | Wiki Import Pages Test 1 | WIP1      | oustudyplan |
    And the following "course enrolments" exist:
       | user     | course   | role           |
       | student1 | WIP1     | student        |
    And the following "activities" exist:
      | activity | name                 | intro                        | course | idnumber | allowimport | Annotation system |
      | ouwiki   | WIP.coursewiki       | This wiki contains info      | WIP1   | ouwiki1  | 0           | Yes               |
      | ouwiki   | WIP.importwiki       | Imports go here              | WIP1   | ouwiki2  | 1           | Yes               |

  @javascript
  Scenario: Creating wikis and data, part one
    Given I log in as "student1"
    And I am on "Wiki Import Pages Test 1" course homepage
    And I press "All weeks"
    And I press "oustudyplan-expandall"
    And I follow "WIP.coursewiki"
    And I press "Create page"
    And I set the field "Content" to "WIP.coursewiki start page, [[WIPcoursewiki1]]"
    And I press "Save changes"
    And I follow "WIPcoursewiki1"
    And I press "Create page"
    And I set the field "Content" to "WIPcoursewiki1 page, [[WIPcoursewiki2]]"
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachments" filemanager
    And I press "Save changes"
    And I follow "WIPcoursewiki2"
    And I press "Create page"
    And I set the field "Content" to "WIPcoursewiki2 page"
    And I upload "lib/tests/fixtures/gd-logo.png" file to "Attachments" filemanager
    When I press "Save changes"
    Then I should see "WIP.coursewiki"
    Given I am on site homepage
    And I follow "Wiki Import Pages Test 1"
    And I press "All weeks"
    And I follow "WIP.importwiki"
    And I press "Create page"
    And I press "Save changes"
    And I follow "Import pages"
    And "Import from WIP.coursewiki" "button" should exist
    And I press "Import from WIP.coursewiki"
    And I set the field "Import page, " to "1"
    And I press "Import pages"
    And I wait until the page is ready
    When I press "Import pages"
    Then I should see "The import completed successfully"
    Given I press "Continue"
    When I follow "WIPcoursewiki1"
    Then I should see "empty.txt"
    When I follow "WIPcoursewiki2"
    Then I should see "gd-logo.png"
