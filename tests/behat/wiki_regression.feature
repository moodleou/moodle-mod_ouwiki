@ou @ou_vle @mod @mod_ouwiki @wiki_basic
Feature: Test OUwiki regressions
  In order to use ouwiki features
  As a user
  I need to be able to complete basic operations

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@asd.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |

    And the following "groups" exist:
      | name | course | idnumber |
      | G1 | C1 | G1 |

    And the following "group members" exist:
      | user | group |
      | student1 | G1 |

  @javascript @_file_upload
  Scenario: Verify Template
    Given I log in as "admin"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OU wiki" to section "1"
    And I set the following fields to these values:
      | Name        | W.T                                   |
      | Description | Separate groups                       |
      | Sub-wikis   | One wiki per group                    |
      | Group mode  | Separate groups                       |
    And I upload "mod/ouwiki/tests/fixtures/template.xml" file to "Template" filemanager
    And following "template.xml" should download between "1000" and "1500" bytes
    And I press "Save and display"
    And I follow "W.T"
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
  Scenario: Verify Time limit
    Given I log in as "admin"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "OU wiki" to section "1"
    And I set the following fields to these values:
      | Name | W.WC |
      | Description | wiki with no groups |
      | Group mode | No groups |
      |Time allowed for edit| 3 minutes (for testing) |
    And I press "Save and display"
    And I am on "Course 1" course homepage
    And I follow "W.WC"
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

  Scenario: Verify Allow editing for Past,Future and together
    Given I log in as "admin"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "OU wiki" to section "1"
    #Values for Future
    And I set the following fields to these values:
      | Name                | W.SG                                  |
      | Description         | Allow edit for past,present, Future   |
      | Sub-wikis           | One wiki per group                    |
      | Group mode          | Separate groups                       |
      | editbegin[enabled]  | 1                                     |
      | editbegin[day]      | ## +2 days ## j ##                    |
      | editbegin[month]    | ## +2 days ## n ##                    |
      | editbegin[year]     | ## +2 days ## Y ##                    |
    And I press "Save and display"
    Then I should see "This wiki is currently locked."
    And I am on "Course 1" course homepage
    And I add a "OU wiki" to section "1"
    #Values for Past
    And I set the following fields to these values:
      | Name                | W.SG                                  |
      | Description         | Allow edit for past,present, Future   |
      | Sub-wikis           | One wiki per group                    |
      | Group mode          | Separate groups                       |
      | editend[enabled]    | 1                                     |
      | editend[day]        | ## -2 days ## j ##                    |
      | editend[month]      | ## -2 days ## n ##                    |
      | editend[year]       | ## -2 days ## Y ##                    |
    And I press "Save and display"
    Then I should see "This wiki is currently locked and can no longer be edited."
    And I am on "Course 1" course homepage
    And I add a "OU wiki" to section "1"
    #Values for Past and Future
    And I set the following fields to these values:
      | Name                | W.SG                                  |
      | Description         | Allow edit for past,present, Future   |
      | Sub-wikis           | One wiki per group                    |
      | Group mode          | Separate groups                       |
      | editbegin[enabled]  | 1                                     |
      | editbegin[day]      | ## -1 days ## j ##                    |
      | editbegin[month]    | ## -1 days ## n ##                    |
      | editbegin[year]     | ## -1 days ## Y ##                    |
      | editend[enabled]    | 1                                     |
      | editend[day]        | ## +1 days ## j ##                    |
      | editend[month]      | ## +1 days ## n ##                    |
      | editend[year]       | ## +1 days ## Y ##                    |
    And I press "Save and display"
    Then I should see "This wiki's start page has not yet been created."
    And I press "Create page"
    And I set the field "Content" to "C17"
    And I press "Save changes"
    Then I should see "C17"





