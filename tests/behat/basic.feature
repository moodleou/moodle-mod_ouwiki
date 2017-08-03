@ou @ou_vle @mod @mod_ouwiki @ouwiki_basic
Feature: Test Post and Comment on OUwiki entry
  In order to use ouwiki features
  As a user
  I need to be able to complete basic operations

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
    And the following "groups" exist:
        | name | course | idnumber |
        | G1 | C1 | G1 |
        | G2 | C1 | G2 |
    And the following "group members" exist:
        | user | group |
        | student1 | G1 |
        | student2 | G2 |
        | teacher2 | G1 |

  Scenario: No groups - basic access etc
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "OU wiki" to section "1" and I fill the form with:
        | Name | W.WC |
        | Description | wiki with no groups |
        | Group mode | No groups |
    And I am on "Course 1" course homepage
    And I follow "W.WC"
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "C1 no groups wiki"
    And I press "Save changes"
    # Confirm start page set up
    Then I should see "C1 no groups wiki" in the ".ouwiki_content" "css_element"
    And "Teacher 1" "link" should exist
    # unable to check for date
    And I log out
    # Check edit and preview page (though we can not test to see whether altered content in preview mode can be seen by otherusers)
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.WC"
    Then I should see "C1 no groups wiki" in the ".ouwiki_content" "css_element"
    And I log out
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.WC"
    When I click on "Edit" "link"
    Then I should see "C1 no groups wiki"
    And I set the field "Content" to "C7 no groups wiki"
    When I press "Preview"
    Then I should see "C7 no groups wiki" in the ".ouwiki_content" "css_element"
    And I press "Save changes"
    And I log out
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.WC"
    And I should see "C7 no groups wiki" in the ".ouwiki_content" "css_element"
    And I log out

  Scenario: Separate groups - basic access etc
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
    And I press "Save and display"
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
    And I log out
    # Create start page for group 2
    Given I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.SG"
    Then I should see "Viewing wiki for: G2" in the ".ouw_subwiki" "css_element"
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "C6 separate groups wiki"
    Given I press "Save changes"
    # Check that it has been correctly created
    Then I should see "Viewing wiki for: G2" in the ".ouw_subwiki" "css_element"
    And I should see "C6 separate groups wiki" in the ".ouwiki_content" "css_element"
    And "Student 2" "link" should exist
    # unable to check for date
    And I log out
    # Check for correct content and creator for each group
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.SG"
    And "div.singleselect" "css_element" should exist
    And the "Separate groups" select box should contain "G1"
    And the "Separate groups" select box should contain "G2"
    Given I set the field "Separate groups" to "G1"
    When I press "Go"
    Then "div.singleselect" "css_element" should exist
    And the "Separate groups" select box should contain "G2"
    And I should see "C2 separate groups wiki" in the ".ouwiki_content" "css_element"
    And "Student 1" "link" should exist
    Given I set the field "Separate groups" to "G2"
    When I press "Go"
    Then "div.singleselect" "css_element" should exist
    And I should see "C6 separate groups wiki" in the ".ouwiki_content" "css_element"
    And "Student 2" "link" should exist
    And I log out
    # Check adding wiki pages - by adding a link to start page
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.SG"
    When I click on "Edit" "link"
    Then I should see "C2 separate groups wiki"
    # Then I should see "C1 no groups wiki" in the "div.editor_atto_content" "css_element" - why is this not working ???
    And I set the field "Content" to "C7 separate groups wiki [[frog]]"
    And I press "Save changes"
    When I click on "frog" "link"
    Then "Create page" "button" should exist
    # Create start page start page for group 1
    And I press "Create page"
    And I set the field "Content" to "C8 separate groups wiki"
    When I press "Save changes"
    Then I should see "C8 separate groups wiki"
    And "Start page" "link" should exist
    When I click on "Start page" "link"
    Then I should see "C7 separate groups wiki"
    And  "frog" "link" should exist
    And I log out
    # Check adding wiki pages - by 'Create a new page' name in text field
    Given I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.SG"
    Then I should see "C6 separate groups wiki"
    And I set the field "Create new page" to "frog"
    Given I press "Create"
    Then I set the field "Content" to "C9 separate groups wiki"
    When I press "Save changes"
    Then I should see "C9 separate groups wiki"
    And "Start page" "link" should exist
    When I click on "Start page" "link"
    Then I should see "C6 separate groups wiki"
    And  "frog" "link" should exist
    When I click on "frog" "link"
    Then I should see "C9 separate groups wiki"
    And I set the field "Create new page" to "sect test"
    Given I press "Create"
    Then I set the field "Content" to "C10"
    When I press "Save changes"
    # Checking creating sections in an ouwiki page
    Given I set the field "Add new section to this page" to "SECT1"
    And I press "Add"
    Then I should see "SECT1"
    And I should see "Student 2"
    # Can only be tested without amending the original text
    And I press "Save changes"
    Given I click on "Edit section" "link"
    Then I should see "SECT1"
    And I should see "Student 2"
    And I log out

  Scenario: Visible groups - basic access etc
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
    # Check selected dropdown option is G1
    And "div.singleselect" "css_element" should exist
    And ".groupselector select" "css_element" should exist
    And the field "Visible groups" matches value "G1"
    And the "Visible groups" select box should contain "G2"
    And "Create page" "button" should exist
    # Create start page
    And I press "Create page"
    And I set the field "Content" to "C3 visible groups wiki"
    And I press "Save changes"
    # Check to see that user student1 can not create the start page for any other group
    And the "Visible groups" select box should contain "G2"
    Given I set the field "Visible groups" to "G2"
    When I press "Go"
    Then "Create page" "button" should not exist
    And "Content" "text" should not exist
    And I log out
    # Check for correct content and creator for each group
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.VG"
    And "div.singleselect" "css_element" should exist
    And the "Visible groups" select box should contain "G1"
    And the "Visible groups" select box should contain "G2"
    Given I set the field "Visible groups" to "G1"
    When I press "Go"
    Then "div.singleselect" "css_element" should exist
    And I should see "C3 visible groups wiki" in the ".ouwiki_content" "css_element"
    And "Student 1" "link" should exist
    Given I set the field "Visible groups" to "G2"
    When I press "Go"
    Then "Create page" "button" should exist
    And I log out

  Scenario: Individual - basic access etc
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "OU wiki" to section "1" and I fill the form with:
        | Name | W.I |
        | Description | individual wikis |
        | Sub-wikis |Separate wiki for every user |
        | Group mode | No groups |
    And I log out
    # Checking to set up individual wiki for student 1
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.I"
    Then "Viewing wiki for:" "text" should exist
    And "Student 1" "link" should exist
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "C4 individual wiki"
    And I press "Save changes"
    And I log out
    # Checking to set up individual wiki for student 2
    Given I log in as "student2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.I"
    Then "Viewing wiki for:" "text" should exist
    And "Student 2" "link" should exist
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "C5 individual wiki"
    And I press "Save changes"
    And I log out
    # Check to see that a non-editing teacher can view individual wiki of students belonging to their group
    Given I log in as "teacher2"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.I"
    And "div.individualselector" "css_element" should exist
    And the field "Viewing wiki for:" matches value "Teacher 2"
    And the "Viewing wiki for:" select box should contain "Student 1"
    Given I set the field "Viewing wiki for:" to "Student 1"
    When I press "Go"
    Then I should see "C4 individual wiki" in the ".ouwiki_content" "css_element"
    And "Student 1" "link" should exist
    # unable to check for date
    And I log out
    # Check that editing teacher can view and visit all individual wikis
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.I"
    And "div.individualselector" "css_element" should exist
    And the field "Viewing wiki for:" matches value "Teacher 1"
    And the "Viewing wiki for:" select box should contain "Student 1"
    And the "Viewing wiki for:" select box should contain "Student 2"
    And the "Viewing wiki for:" select box should contain "Teacher 2"
    Given I set the field "Viewing wiki for:" to "Student 1"
    When I press "Go"
    Then I should see "C4 individual wiki" in the ".ouwiki_content" "css_element"
    And "Student 1" "link" should exist
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.I"
    And "div.individualselector" "css_element" should exist
    Given I set the field "Viewing wiki for:" to "Student 2"
    When I press "Go"
    Then I should see "C5 individual wiki" in the ".ouwiki_content" "css_element"
    And "Student 2" "link" should exist
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.I"
    And "div.individualselector" "css_element" should exist
    Given I set the field "Viewing wiki for:" to "Teacher 2"
    When I press "Go"
    Then "Create page" "button" should exist
    And I log out

  Scenario: Wiki history No groups -
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "OU wiki" to section "1" and I fill the form with:
        | Name | W.WX |
        | Description | wiki with no groups |
        | Group mode | No groups |
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "C23 no groups wiki"
    And I press "Save changes"
    # Confirm start page set up
    Then I should see "C23 no groups wiki" in the ".ouwiki_content" "css_element"
    And "Teacher 1" "link" should exist
    # unable to check for date
    And I add a ouwiki page with the following data:
        | Create new page | Frogs |
        | Content | C24 |
    Then I should see "C24"
    And I add a ouwiki page with the following data:
        | Create new page | Zombies |
        | Content | C25 |
    Then I should see "C25"
    And "Frogs" "link" should exist
    When I click on "Frogs" "link"
    Then I should see "C24"
    And "Start page" "link" should exist
    When I click on "Start page" "link"
    Then I should see "C23 no groups wiki"
    And I add a ouwiki page with the following data:
        | Create new page | Geckos |
        | Content | C26 |
    Then I should see "C26"
    # Check wiki index for correct order
    When I click on "Wiki index" "link"
    Then "Start page" "text" should appear before "Frogs" "text"
    Then "Frogs" "text" should appear before "Geckos" "text"
    Then "Geckos" "text" should appear before "Zombies" "text"
    And "Teacher 1" "link" should exist
    And "Student 1" "link" should not exist
    And "Student 2" "link" should not exist
    And "Teacher 2" "link" should not exist
    # Check view online produces the correct order
    When I click on "View online" "link"
    Then "C23 no groups wiki" "text" should appear before "C24" "text"
    Then "C24" "text" should appear before "C26" "text"
    Then "C26" "text" should appear before "C25" "text"
    # Check wiki index structure view for correct order
    Given I click on "Wiki index" "link"
    And I click on "Structure" "link"
    Then "Start page" "text" should appear before "Frogs" "text"
    Then "Frogs" "text" should appear before "Zombies" "text"
    Then "Zombies" "text" should appear before "Geckos" "text"
    # Check view online structure produces the correct order
    When I click on "View online" "link"
    Then "C23 no groups wiki" "text" should appear before "C24" "text"
    Then "C24" "text" should appear before "C25" "text"
    Then "C25" "text" should appear before "C26" "text"
    # Check editing history - 3 rows being shown incstead of 5 as per regression test.
    Given I click on "Start page" "link"
    And I add a ouwiki page with the following data:
        | Create new page | Gremlins |
        | Content | C23 |
    And I edit a ouwiki page with the following data:
        | Content | C27 A C27 B C27 C |
    And I edit a ouwiki page with the following data:
        | Content | C27 A C27 B C28 B |
    When I click on "History" "link"
    And "Teacher 1" "link" should exist
    And "Student 1" "link" should not exist
    And "Student 2" "link" should not exist
    And "Teacher 2" "link" should not exist
    And I should see "View" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    And I should see "Delete" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    And I should not see "Revert" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    And I should see "changes" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    And I should see "View" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    And I should see "Delete" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    And I should see "Revert" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    And I should see "changes" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    And I should see "View" in the "//form[@name='ouw_history']//table//tbody//tr[3]//td[3]" "xpath_element"
    And I should see "Delete" in the "//form[@name='ouw_history']//table//tbody//tr[3]//td[3]" "xpath_element"
    And I should see "Revert" in the "//form[@name='ouw_history']//table//tbody//tr[3]//td[3]" "xpath_element"
    And I should not see "changes" in the "//form[@name='ouw_history']//table//tbody//tr[3]//td[3]" "xpath_element"
    Given I click on "View" "link" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    Then I should see "C27 A C27 B C28 B"
    And I click on "History" "link"
    Given I click on "View" "link" in the "//form[@name='ouw_history']//table//tbody//tr[3]//td[3]" "xpath_element"
    Then I should see "C23"
    # Page changes
    And I click on "History" "link"
    Given I click on "changes" "link" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    Then I should see "C27 C" in the "//span[@class='ouw_deleted']" "xpath_element"
    And I should see "C28 B" in the "//span[@class='ouw_added']" "xpath_element"
    And I log out
    # Check against number of changes made - WIC07
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    And "Frogs" "link" should exist
    When I click on "Frogs" "link"
    When I click on "Zombies" "link"
    Then I should see "C25"
    And I edit a ouwiki page with the following data:
      | Content | C29|
    And I edit a ouwiki page with the following data:
      | Content | C30|
    And I edit a ouwiki page with the following data:
      | Content | C31|
    And I edit a ouwiki page with the following data:
      | Content | C32|
    And I edit a ouwiki page with the following data:
      | Content | C33|
    And I edit a ouwiki page with the following data:
      | Content | C34|
    And I edit a ouwiki page with the following data:
      | Content | C35|
    And I edit a ouwiki page with the following data:
      | Content | C36|
    And I edit a ouwiki page with the following data:
      | Content | C37|
    And I edit a ouwiki page with the following data:
      | Content | C38|
    And I edit a ouwiki page with the following data:
      | Content | C39|
    And I edit a ouwiki page with the following data:
      | Content | C40|
    And I edit a ouwiki page with the following data:
      | Content | C41|
    And I edit a ouwiki page with the following data:
      | Content | C42|
    And I edit a ouwiki page with the following data:
      | Content | C43|
    And I edit a ouwiki page with the following data:
      | Content | C44|
    And I edit a ouwiki page with the following data:
      | Content | C45|
    And I edit a ouwiki page with the following data:
      | Content | C46|
    And I edit a ouwiki page with the following data:
      | Content | C47|
    And I edit a ouwiki page with the following data:
      | Content | C48|
    And I edit a ouwiki page with the following data:
      | Content | C49|
    And I edit a ouwiki page with the following data:
      | Content | C50|
    And I edit a ouwiki page with the following data:
      | Content | C51|
    And I edit a ouwiki page with the following data:
      | Content | C52|
    And I edit a ouwiki page with the following data:
      | Content | C53|
    And I edit a ouwiki page with the following data:
      | Content | C54|
    And I edit a ouwiki page with the following data:
      | Content | C56|
    And I edit a ouwiki page with the following data:
      | Content | C57|
    And I edit a ouwiki page with the following data:
      | Content | C58|
    And I edit a ouwiki page with the following data:
      | Content | C59|
    And I edit a ouwiki page with the following data:
      | Content | C60|
    And I edit a ouwiki page with the following data:
      | Content | C61|
    And I edit a ouwiki page with the following data:
      | Content | C62 |
    And I edit a ouwiki page with the following data:
      | Content | C63 |
    And I edit a ouwiki page with the following data:
      | Content | C64 |
    And I edit a ouwiki page with the following data:
      | Content | C65 |
    And I edit a ouwiki page with the following data:
      | Content | C66 |
    And I edit a ouwiki page with the following data:
      | Content | C67 |
    And I edit a ouwiki page with the following data:
      | Content | C68 |
    And I edit a ouwiki page with the following data:
      | Content | C69 |
    And I edit a ouwiki page with the following data:
      | Content | C70 |
    Given I click on "Wiki changes" "link"
    Then "Older changes" "link" should exist
    Given I click on "Older changes" "link"
    # Check for wiki change deatils - can not test Atom or RSS
    Then "Newer changes" "link" should exist
    Given I click on "Newer changes" "link"
    Then "Older changes" "link" should exist
    And I should see "6"
    And I should see "1"
    And I should see "Atom"
    And I should see "RSS"
    # Check to make sure we can not see "Annotate" tab
    Given I click on "W.WX" "link"
    Then I should not see "Annotate"
    # Check reverting pages
    When I click on "Frogs" "link"
    Then I should see "C24"
    And I should see "Zombies"
    When I edit a ouwiki page with the following data:
      | Content | MISCHIEF |
    Then I should see "MISCHIEF"
    And I should not see "C24"
    And I should not see "Zombies"
    When I click on "History" "link"
    Then "Student 1" "link" should exist
    And I should see "Revert" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    Given I click on "Revert" "link" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    When I click on "Revert" "button"
    Then I should see "C24"
    And I should see "Zombies"
    # Check deleting pages
    Then I edit a ouwiki page with the following data:
      | Content | PORNOGRAPHY |
    And I log out
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    When I click on "Frogs" "link"
    Then I should see "PORNOGRAPHY"
    When I click on "History" "link"
    And I should see "Delete" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    Given I click on "Delete" "link" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    Then I should see "Undelete" in the "//form[@name='ouw_history']//table//tr[contains(@class, 'ouw_deletedrow')]//td[3]" "xpath_element"
    And I should see "changes" in the "//form[@name='ouw_history']//table//tr[contains(@class, 'ouw_deletedrow')]//td[3]" "xpath_element"
    When I click on "W.WX" "link"
    And I click on "Frogs" "link"
    Then I should see "C24"
    When I click on "History" "link"
    Then I should see "Undelete" in the "//form[@name='ouw_history']//table//tr[contains(@class, 'ouw_deletedrow')]//td[3]" "xpath_element"
    Given I click on "Undelete" "link" in the "//form[@name='ouw_history']//table//tr[contains(@class, 'ouw_deletedrow')]//td[3]" "xpath_element"
    When I click on "W.WX" "link"
    And I click on "Frogs" "link"
    Then I should see "PORNOGRAPHY"
    When I click on "History" "link"
    Given I click on "Delete" "link" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    Given I click on "Delete" "link" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    Given I click on "Delete" "link" in the "//form[@name='ouw_history']//table//tbody//tr[3]//td[3]" "xpath_element"
    Given I click on "Delete" "link" in the "//form[@name='ouw_history']//table//tbody//tr[4]//td[3]" "xpath_element"
    Given I click on "Delete" "link" in the "//form[@name='ouw_history']//table//tbody//tr[5]//td[3]" "xpath_element"
    When I click on "W.WX" "link"
    And I click on "Frogs" "link"
    Then "Create page" "button" should exist
    Given I click on "Wiki index" "link"
    And I click on "Frogs" "link"
    Then "Create page" "button" should exist
    Given I press "Create page"
    And I set the field "Content" to "SAFE"
    And I press "Save changes"
    When I click on "History" "link"
    Then I should see "Delete" in the "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element"
    And I should see "Undelete" in the "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element"
    And I should see "Undelete" in the "//form[@name='ouw_history']//table//tbody//tr[3]//td[3]" "xpath_element"
    And I should see "Undelete" in the "//form[@name='ouw_history']//table//tbody//tr[4]//td[3]" "xpath_element"
    And I should see "Undelete" in the "//form[@name='ouw_history']//table//tbody//tr[5]//td[3]" "xpath_element"
    And I should see "Undelete" in the "//form[@name='ouw_history']//table//tbody//tr[6]//td[3]" "xpath_element"
    And I log out
    Given I log in as "student1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I follow "W.WX"
    And I click on "Frogs" "link"
    Then I should see "SAFE"
    When I click on "History" "link"
    Then "//form[@name='ouw_history']//table//tbody//tr[1]//td[3]" "xpath_element" should exist
    And "//form[@name='ouw_history']//table//tbody//tr[2]//td[3]" "xpath_element" should not exist
    And I log out

  @javascript
  Scenario: Attachments No groups
    Given I log in as "teacher1"
    And I am on homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "OU wiki" to section "1" and I fill the form with:
        | Name | W.X |
        | Description | wiki with no groups |
        | Group mode | No groups |
    And I am on "Course 1" course homepage
    And I follow "W.X"
    And "Create page" "button" should exist
    And I press "Create page"
    And I set the field "Content" to "C71 no groups wiki"
    And I press "Save changes"
    # Confirm start page set up
    Then I should see "C71 no groups wiki" in the ".ouwiki_content" "css_element"
    And "Teacher 1" "link" should exist
    # unable to check for date
    And I add a ouwiki page with the following data:
      | Create new page | Attest |
      | Content | C72 |
    Then I should see "C72"
    # Add attachments - we can/should not access a users hardisk so pull from system
    Given I click on "Edit" "link"
    And I upload "lib/tests/fixtures/empty.txt" file to "Attachments" filemanager
    And I press "Save changes"
    And I wait to be redirected
    Then "empty.txt" "link" should exist
    # Check for annotations (and test edit settings at the same time) - note we can not test for locking
    And I click on "Topic 1" "link" in the ".breadcrumb-nav" "css_element"
    When I click on "Edit" "link" in the "li.modtype_ouwiki div.menubar" "css_element"
    And I click on "Edit settings" "link" in the "li.modtype_ouwiki div.menu" "css_element"
    And I expand all fieldsets
    And I set the field "Annotation system" to "Yes"
    When I press "Save and display"
    Then I should see "Annotate"
    And I add a ouwiki page with the following data:
      | Create new page | Vampires |
      | Content | A1 A2 |
    Given I click on "Annotate" "link"
    And "span.ouwiki-annotation-marker" "css_element" should exist
    When I click on "#marker0" "css_element"
    Then I set the field "Add annotation:" to "web"
    And I press "Add"
    And I should see "web"
    When I click on "#marker3" "css_element"
    Then I set the field "Add annotation:" to "spider"
    And I press "Add"
    And I should see "spider"
    When I press "Save changes"
    Then "Hide annotations" "link" should be visible
    And "Expand annotations" "link" should be visible
    When I click on "span.ouwiki-annotation-tag:nth-of-type(1)" "css_element"
    Then I should see "web"
    And I should see "Teacher 1"
    When I click on "span.ouwiki-annotation-tag:nth-of-type(2)" "css_element"
    Then I should see "spider"
    And I should see "Teacher 1"
    # Can not test for photos
    And "Collapse annotations" "link" should be visible
    When I click on "span.ouwiki-annotation-tag:nth-of-type(1)" "css_element"
    Then I should not see "web"
    And "Collapse annotations" "link" should not be visible
    And "Hide annotations" "link" should be visible
    And "Expand annotations" "link" should be visible
    When I click on "Expand annotations" "link"
    Then I should see "web"
    And I should see "Teacher 1"
    And I should see "spider"
    And I should see "Teacher 1"
    And "Hide annotations" "link" should be visible
    When I click on "Hide annotations" "link"
    Then I should not see "web"
    And I should not see "spider"
    And "Show annotations" "link" should be visible
    When I click on "Show annotations" "link"
    Then "span.ouwiki-annotation-tag:nth-of-type(2)" "css_element" should be visible
    And "span.ouwiki-annotation-tag:nth-of-type(1)" "css_element" should be visible
    # collapse "web" annotation, but leave "spider" open
    When I click on "span.ouwiki-annotation-tag:nth-of-type(1)" "css_element"
    Then I should not see "web"
    And I should see "spider"
    And "Expand annotations" "link" should be visible
    When I click on "Expand annotations" "link"
    Then I should see "web"
    And I should see "Teacher 1"
    And I should see "spider"
    And I should see "Teacher 1"
    And "Hide annotations" "link" should be visible
    When I click on "Hide annotations" "link"
    Then I should not see "web"
    And I should not see "spider"
    And "Show annotations" "link" should be visible
    When I click on "Show annotations" "link"
    Then I should see "web"
    And I should see "Teacher 1"
    And I should see "spider"
    And I should see "Teacher 1"
    And I log out
