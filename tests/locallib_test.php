<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * OUWiki unit tests - test locallib functions
 *
 * @package    mod_ouwiki
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
global $CFG;

require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

class ouwiki_locallib_test extends advanced_testcase {

    /**
     * OU Wiki generator reference
     * @var testing_module_generator
     */
    public $generator = null;

    /**
     * Create temporary test tables and entries in the database for these tests.
     * These tests have to work on a brand new site.
     */
    public function setUp() {
        global $CFG;

        parent::setup();

        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_ouwiki');
    }

    /*

    Backend functions covered:

    ouwiki_get_subwiki()
    ouwiki_get_current_page()
    ouwiki_save_new_version()
    ouwiki_create_new_page()
    ouwiki_get_page_history()
    ouwiki_get_page_version()
    ouwiki_get_subwiki_recentpages()
    ouwiki_get_subwiki_recentchanges()
    ouwiki_init_pages()
    ouwiki_get_subwiki_index()
    ouwiki_build_tree()
    ouwiki_tree_index()
    ouwiki_display_entirewiki_page_in_index()
    ouwiki_get_sub_tree_from_index()
    ouwiki_get_last_modified()

    Functions not covered:
    Delete/undelete page version - no backend functions for this process
    File attachment - difficult to test through backend functions due to moodle core handling of files

    */


    public function test_ouwiki_pages_and_versions() {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->get_new_user();
        $course = $this->get_new_course();

        // Setup a wiki to use.
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $this->assertNotEmpty($cm);
        $context = context_module::instance($cm->id);
        $groupid = 0;
        $this->setUser($user);
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id, true);

        // Create the start page.
        $startpagename = 'startpage';
        $formdata = null;
        $startpageversion = ouwiki_get_current_page($subwiki, $startpagename, OUWIKI_GETPAGE_CREATE);
        $verid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $startpagename, $startpagename,
                -1, -1, -1, null, $formdata);

        // Create a page.
        $pagename1 = 'testpage1';
        $content1 = 'testcontent';
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $startpagename, $pagename1, $content1, $formdata);

        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename1);
        $this->assertEquals($pageversion->title, $pagename1);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Make some more versions.
        $content2 = 'testcontent2';
        $content3 = 'testcontent3';
        ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pagename1, $content2, -1, -1, -1, null, $formdata);
        $verid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pagename1, $content3, -1, -1, -1, null, $formdata);
        $versions = $DB->get_records('ouwiki_versions');
        $versionids = array();
        foreach ($versions as $version) {
            $versionids[] = $version->id;
        }
        $this->assertEquals(max($versionids), $verid);
        $pageversion = ouwiki_get_current_page($subwiki, $pagename1);
        $this->assertEquals($content3, $pageversion->xhtml);

        // Get the history.
        $history = ouwiki_get_page_history($pageversion->pageid, true);
        $this->assertEquals('array', gettype($history));

        // Last version should match $content3.
        $version = array_shift($history);
        $this->assertEquals(max($versionids), $version->versionid);
        $this->assertEquals($user->id, $version->id);
        $this->assertEquals(1, $version->wordcount);
        $this->assertEquals($pageversion->previousversionid, $version->previousversionid);
        $this->assertNull($version->importversionid);

        // Add another page.
        $pagename2 = 'testpage2';
        $content4 = 'testcontent4';

        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $startpagename, $pagename2, $content4, $formdata);

        // Test recent pages.
        $changes = ouwiki_get_subwiki_recentpages($subwiki->id);
        $this->assertEquals('array', gettype($changes));
        $this->assertEquals(fullname($user), fullname(current($changes)));
        // First page should be startpage.
        $this->assertEquals(end($changes)->title, $startpagename);
        // 3rd page should be pagename2.
        $this->assertEquals(reset($changes)->title, $pagename2);

        $testfullname = fullname(current($changes));
        $this->assertEquals(fullname($user), $testfullname);

        // Test recent wiki changes.
        $changes = ouwiki_get_subwiki_recentchanges($subwiki->id);
        $testfullname = fullname(reset($changes));
        $this->assertEquals(fullname($user), $testfullname);
        $this->assertEquals(reset($changes)->title, $startpagename);
        // Sixth change should be to testpage2  - when we created testpage2.
        $this->assertEquals(next($changes)->title, $pagename2);
        // Seventh change shouldbe start page again - when we linked to testpage2 to startpage.
        $this->assertEquals(end($changes)->title, $startpagename);

    }

    public function test_ouwiki_init_course_wiki_access() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $user = $this->get_new_user();
        $course = $this->get_new_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $this->assertNotEmpty($cm);
        $context = context_module::instance($cm->id);
        // Add annotation for student role as not allowed by default.
        role_change_permission($studentrole->id, $context, 'mod/ouwiki:annotate', CAP_ALLOW);
        $this->setUser($user);
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, 0, $user->id, true);
        $createdsubwikiid = $subwiki->id;
        $this->check_subwiki($ouwiki, $subwiki, true);

        // Get the same one we created above (without 'create').
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, 0, $user->id);
        $this->assertEquals($subwiki->id, $createdsubwikiid);
    }

    public function test_ouwiki_init_group_wiki_access() {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Create course, ouwiki, course module, context, groupid, userid.
        $user = $this->get_new_user();
        $course = $this->get_new_course();
        // Enrol user as student on course.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        // Store admin user id for later use.
        $adminuserid = $USER->id;

        $this->setUser($user);

        // Test group wikis (visible - test access across groups).
        $this->setAdminUser();
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_GROUPS, array('groupmode' => VISIBLEGROUPS));
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $this->assertNotEmpty($cm);
        $context = context_module::instance($cm->id);

        $group1 = $this->get_new_group($course->id);
        $group2 = $this->get_new_group($course->id);

        $this->setUser($user);

        // Subwiki with 'create'.
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $group1->id, $user->id, true);
        $createdsubwikiid = $subwiki->id;
        $this->check_subwiki($ouwiki, $subwiki, false, $group1->id);

        // Add annotation for student role as not allowed by default.
        role_change_permission($studentrole->id, $context, 'mod/ouwiki:annotate', CAP_ALLOW);
        $member = $this->get_new_group_member($group1->id, $user->id);// Adds our user to group1.

        // Check student can access, now in group.
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $group1->id, $user->id, true);
        $this->assertEquals($subwiki->id, $createdsubwikiid);
        $this->check_subwiki($ouwiki, $subwiki, true, $group1->id);

        // Check student edit/annotate access to other group wiki when has specific capabilities.
        role_change_permission($studentrole->id, $context, 'mod/ouwiki:annotateothers', CAP_ALLOW);
        role_change_permission($studentrole->id, $context, 'mod/ouwiki:editothers', CAP_ALLOW);
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $group2->id, $user->id, true);
        $this->check_subwiki($ouwiki, $subwiki, true, $group2->id);

        // Check admin has access to any group.
        $this->setAdminUser();
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $group1->id, $USER->id);
        $this->check_subwiki($ouwiki, $subwiki, true, $group1->id);

        // Check separate groups (student should only edit own group).
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_GROUPS, array('groupmode' => SEPARATEGROUPS));
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $this->assertNotEmpty($cm);
        $context = context_module::instance($cm->id);
        $this->setUser($user);
        try {
            $exceptionoccured = false;
            $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $group2->id, $user->id, true);
        } catch (\moodle_exception $e) {
            $this->assertContains('Wiki error: You do not have permission to see the content of this page', $e->getMessage());
            $exceptionoccured = true;
         }
        $this->assertTrue($exceptionoccured);
    }

    public function test_ouwiki_init_individual_wiki_access() {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        // Create course, ouwiki, course module, context, groupid, userid.
        $user = $this->get_new_user();
        $course = $this->get_new_course();
        // Enrol user as student on course.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        // Store admin user id for later use.
        $adminuserid = $USER->id;

        $this->setUser($user);

        // Test invididual wikis.
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_INDIVIDUAL);
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $this->assertNotEmpty($cm);
        $context = context_module::instance($cm->id);
        $groupid = 0;
        // Add annotation for student role as not allowed by default.
        role_change_permission($studentrole->id, $context, 'mod/ouwiki:annotate', CAP_ALLOW);

        // Subwiki with 'create'.
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id, true);
        $this->check_subwiki($ouwiki, $subwiki, true, $user->id);

        // Check admin can access students wiki just created.
        $this->setAdminUser();
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id);
        $this->check_subwiki($ouwiki, $subwiki, true, $user->id);

        // Check student viewing someone else's wiki throws exception (add nothing after this).
        $this->setUser($user);
        $this->setExpectedException('moodle_exception');
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $adminuserid, true);
        $this->fail('Expected exception on access to another users wiki');// Shouldn't get here.
    }

    public function test_ouwiki_word_count() {
        $tests = array();

        $test['string'] = "This is four words";
        $test['count'] = 4;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = " ";
        $test['count'] = 0;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = "word";
        $test['count'] = 1;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = "Two\n\nwords";
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = "<p><b>two <i>words</i></b></p>";
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = "Isnâ€™t it three";
        $test['count'] = 3;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = "Isn't it three";
        $test['count'] = 3;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = "three-times-hyphenated words";
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = "one,two,さん";
        $test['count'] = 3;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);

        $test['string'] = 'Two&nbsp;words&nbsp;&nbsp;&nbsp;&nbsp;';
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEquals($test['count'], $testcount);
    }

    /*
     *  Test the OU Wiki structure functions.
     *  The tree structure should be set up as below. The numbering of pages is done deliberatly as follows to aid testing.
     *
     *                                      P1
     *                                      |
     *                         ------------------------------
     *                         P2           P3              P9
     *                                 ----------        --------
     *                                 P4      P8        P10   P12
     *                              --------              |
     *                              P5    P7             P11
     *                              P6
     *
     *  An Alternative way to view the structure above is shown below. The data is created to reflect the structure,
     *  though that does not effect the testing of the processes since the data is created out of sequence from the way
     *  the structure is shown below as it might be in reality.
     *
     *     P1
     *       P2
     *       P3
     *         P4
     *           P5
     *             P6
     *           P7
     *         P8
     *       P9
     *         P10
     *           P11
     *         P12
     */

    public function test_ouwiki_structure() {
        $this->resetAfterTest(true);
        $user = $this->get_new_user();
        $course = $this->get_new_course();

        // Setup a wiki to use.
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $this->assertNotEmpty($cm);
        $context = context_module::instance($cm->id);
        $groupid = 0;
        $this->setUser($user);
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id, true);

        // Create the start page.
        $startpagename = 'testpage1';
        $formdata = null;
        $startpageversion = ouwiki_get_current_page($subwiki, $startpagename, OUWIKI_GETPAGE_CREATE);
        $verid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $startpagename, $startpagename,
                -1, -1, -1, null, $formdata);

        // Create a page with no sub pages.
        $pagename2 = 'testpage2';
        $content2 = 'testcontent2';
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $startpagename, $pagename2, $content2, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename2);
        $this->assertEquals($pageversion->title, $pagename2);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add another page to start page.
        $pagename3 = 'testpage3';
        $content3 = 'testcontent3';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $startpagename, $pagename3, $content3, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename3);
        $this->assertEquals($pageversion->title, $pagename3);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add another page to start page.
        $pagename9 = 'testpage9';
        $content9 = 'testcontent9';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $startpagename, $pagename9, $content9, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename9);
        $this->assertEquals($pageversion->title, $pagename9);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add pages to testpage3.

        // Add page to test page 3.
        $pagename4 = 'testpage4';
        $content4 = 'testcontent4';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename3, $pagename4, $content4, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename4);
        $this->assertEquals($pageversion->title, $pagename4);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add another page to testpage 3.
        $pagename8 = 'testpage8';
        $content8 = 'testcontent8';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename3, $pagename8, $content8, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename8);
        $this->assertEquals($pageversion->title, $pagename8);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add pages to testpage4.

        // Add page to test page 4.
        $pagename5 = 'testpage5';
        $content5 = 'testcontent5';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename4, $pagename5, $content5, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename5);
        $this->assertEquals($pageversion->title, $pagename5);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add another page to testpage 4.
        $pagename7 = 'testpage7';
        $content7 = 'testcontent7';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename4, $pagename7, $content7, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename7);
        $this->assertEquals($pageversion->title, $pagename7);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add page to test page 5.
        $pagename6 = 'testpage6';
        $content6 = 'testcontent6';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename5, $pagename6, $content6, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename6);
        $this->assertEquals($pageversion->title, $pagename6);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add page to test page 9.
        $pagename10 = 'testpage10';
        $content10 = 'testcontent10';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename9, $pagename10, $content10, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename10);
        $this->assertEquals($pageversion->title, $pagename10);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add another page to testpage 9.
        $pagename12 = 'testpage12';
        $content12 = 'testcontent12';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename9, $pagename12, $content12, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename12);
        $this->assertEquals($pageversion->title, $pagename12);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Add page to test page 10.
        $pagename11 = 'testpage11';
        $content11 = 'testcontent11';
        // We don't get anything returned for this.
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $pagename10, $pagename11, $content11, $formdata);
        // Try get that page.
        $pageversion = ouwiki_get_current_page($subwiki, $pagename11);
        $this->assertEquals($pageversion->title, $pagename11);
        // Test fullname info from ouwiki_get_current_page.
        $this->assertEquals(fullname($user), fullname($pageversion));

        // Create the index.
        $index = ouwiki_get_subwiki_index($subwiki->id);

        // Check to see that there are 12 posts.
        $this->assertEquals(count($index), 12);
        reset($index);

        $orphans = false;
        // Check for orphan posts - there should be none.
        foreach ($index as $indexitem) {
            if (count($indexitem->linksfrom) == 0 && $indexitem->title !== 'testpage1') {
                $orphans = true;
                break;
            }
        }
        $this->assertEquals($orphans, false);

        // Test tree structure functions.
        // Build tree.
        ouwiki_build_tree($index);

        // Check to see whether pages have the correct number of children in them including the root node.
        $page = $this->get_page_from_index_by_pagename('testpage3', $index);
        $subtree = ouwiki_get_sub_tree_from_index($page->pageid, $index);
        $this->assertEquals(6, count($subtree));

        // Check to see whether pages have the correct number of children in them including the root node.
        $page = $this->get_page_from_index_by_pagename('testpage4', $index);
        $subtree = ouwiki_get_sub_tree_from_index($page->pageid, $index);
        $this->assertEquals(4, count($subtree));

        // Check linkto, linksfrom, and children arrays for testpage 4
        // - see structure diagram in function description for links to, from, and children for P4.
        $linksfrom = $page->linksfrom;
        $linksto = $page->linksto;
        $children = $page->children;
        $this->assertEquals(count($linksfrom), 1);
        $this->assertEquals(count($linksto), 2);
        $this->assertEquals(count($children), 2);
        // Test linksfrom from testpage 4.
        $p = $this->get_page_from_index_by_pageid($linksfrom[0], $index);
        $this->assertEquals($p->title, $pagename3);
        // Test linksto for testpage 4.
        $p = $this->get_page_from_index_by_pageid($linksto[0], $index);
        $this->assertEquals($p->title, $pagename5);
        $p = $this->get_page_from_index_by_pageid($linksto[1], $index);
        $this->assertEquals($p->title, $pagename7);
        // Test children for testpage 4.
        $p = $this->get_page_from_index_by_pageid($children[0], $index);
        $this->assertEquals($p->title, $pagename5);
        $p = $this->get_page_from_index_by_pageid($children[1], $index);
        $this->assertEquals($p->title, $pagename7);

        // Check to see whether pages have the correct number of children in them including the root node.
        $page = $this->get_page_from_index_by_pagename('testpage5', $index);
        $this->assertEquals(2, count(ouwiki_get_sub_tree_from_index($page->pageid, $index)));

        // Check to see whether pages have the correct number of children in them including the root node.
        $page = $this->get_page_from_index_by_pagename('testpage9', $index);
        $this->assertEquals(4, count(ouwiki_get_sub_tree_from_index($page->pageid, $index)));

        // Check to see whether pages have the correct number of children in them including the root node.
        $page = $this->get_page_from_index_by_pagename('testpage10', $index);
        $this->assertEquals(2, count(ouwiki_get_sub_tree_from_index($page->pageid, $index)));
    }

    /**
     * Simple test of last modified time returning
     */
    public function test_ouwiki_get_last_modified() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $user = $this->get_new_user();
        $user2 = $this->get_new_user('testouwikiuser2');
        $course = $this->get_new_course();
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $context = context_module::instance($cm->id);

        $result = ouwiki_get_last_modified($cm, $course);
        $this->assertEmpty($result);

        // Create page + test last modified returns something.
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, 0, $user2->id, true);
        $page = ouwiki_get_current_page($subwiki, 'startpage', OUWIKI_GETPAGE_CREATE);
        ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, 'startpage', 'content', -1, -1, -1);

        $result = ouwiki_get_last_modified($cm, $course, $user2->id);
        $this->assertNotEmpty($result);
        // Check other user gets a time.
        $result2 = ouwiki_get_last_modified($cm, $course, $user->id);
        $this->assertNotEmpty($result2);
        $this->assertEquals($result, $result2);
        // Check admin gets cached.
        $result = ouwiki_get_last_modified($cm, $course);
        $this->assertEmpty($result);
    }

    /*
     These functions enable us to create database entries and/or grab objects to make it possible to test the
     many permuations required for OU Wiki.
    */

    public function get_page_from_index_by_pagename($pagename, $index) {
        foreach ($index as $indexitem) {
            if ($indexitem->title === $pagename) {
                return $indexitem;
            }
        }
        return null;
    }

    public function get_page_from_index_by_pageid($pageid, $index) {
        foreach ($index as $indexitem) {
            if ($indexitem->pageid === $pageid) {
                return $indexitem;
            }
        }
        return null;
    }

    public function get_new_user($username = 'testouwikiuser') {
        return $this->getDataGenerator()->create_user(array('username' => $username));
    }


    public function get_new_course() {
        return $this->getDataGenerator()->create_course(array('shortname' => 'ouwikitest'));
    }

    public function get_new_ouwiki($courseid, $subwikis = null, $options = array()) {

        $ouwiki = new stdClass();
        $ouwiki->course = $courseid;

        if ($subwikis != null) {
            $ouwiki->subwikis = $subwikis;
        }

        $ouwiki->timeout = null;
        $ouwiki->template = null;
        $ouwiki->editbegin = null;
        $ouwiki->editend = null;

        $ouwiki->completionpages = 0;
        $ouwiki->completionedits = 0;

        $ouwiki->introformat = 0;

        return $this->generator->create_instance($ouwiki, $options);

    }

    public function get_new_group($courseid) {
        static $counter = 0;
        $counter++;
        $group = new stdClass();
        $group->courseid = $courseid;
        $group->name = 'test group' . $counter;
        return $this->getDataGenerator()->create_group($group);
    }

    public function get_new_group_member($groupid, $userid) {
        $member = new stdClass();
        $member->groupid = $groupid;
        $member->userid = $userid;
        return $this->getDataGenerator()->create_group_member($member);
    }

    /**
     * Checks subwiki object created as expected
     * @param object $ouwiki
     * @param object $subwiki
     * @param boolean $canaccess - true if user can access + edit etc
     * @param int $userorgroup - set to expected user or group id for group/individual wikis
     */
    public function check_subwiki($ouwiki, $subwiki, $canaccess = true, $userorgroup = null) {
        $this->assertInstanceOf('stdClass', $subwiki);
        $this->assertEquals($ouwiki->id, $subwiki->wikiid);
        if ($ouwiki->subwikis == OUWIKI_SUBWIKIS_SINGLE) {
            $this->assertNull($subwiki->groupid);
            $this->assertNull($subwiki->userid);
        } else if ($ouwiki->subwikis == OUWIKI_SUBWIKIS_GROUPS) {
            $this->assertEquals($userorgroup, $subwiki->groupid);
        } else if ($ouwiki->subwikis == OUWIKI_SUBWIKIS_INDIVIDUAL) {
            $this->assertEquals($userorgroup, $subwiki->userid);
        }
        if ($ouwiki->annotation == 1) {
            $this->assertEquals(1, $subwiki->annotation);
        }
        $this->assertEquals($canaccess, $subwiki->canedit);
        $this->assertEquals($canaccess, $subwiki->canannotate);
    }

}
