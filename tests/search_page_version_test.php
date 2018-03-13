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
 * OUWiki unit tests - test classes/search/page_version functions
 *
 * @package    mod_ouwiki
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');
require_once($CFG->dirroot . '/mod/ouwiki/tests/ouwiki_test_utils.php');
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');

/**
 * OUWiki unit tests - test classes/search/page_version functions
 *
 * @package mod_ouwiki
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ouwiki_search_page_version_testcase extends advanced_testcase {

    /**
     * Tests get_recordset_by_timestamp function (obtains modified document page versions) and get_document
     * function (converts them into the format the search system wants).
     */
    public function test_ouwiki_search_index() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Enable global search for this test.
        set_config('enableglobalsearch', true);
        $search = testable_core_search::instance();

        // First check there are no results with empty database.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_ouwiki');
        $page = new \mod_ouwiki\search\page_version();
        $rs = $page->get_recordset_by_timestamp();
        $this->assertCount(0, ouwiki_test_utils::recordset_to_array($rs));

        // Set up data.
        $course = $this->getDataGenerator()->create_course();

        $etuser = $this->getDataGenerator()->create_user();
        $suser1 = $this->getDataGenerator()->create_user();
        $suser2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($etuser->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($suser1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($suser2->id, $course->id, 'student');

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        $this->getDataGenerator()->create_group_member(array('groupid' => $group1->id, 'userid' => $suser1->id));
        $this->getDataGenerator()->create_group_member(array('groupid' => $group2->id, 'userid' => $suser2->id));

        $grouping = $this->getDataGenerator()->create_grouping(array('courseid' => $course->id));
        $this->getDataGenerator()->create_grouping_group(
                array('groupingid' => $grouping->id, 'groupid' => $group1->id));
        $this->getDataGenerator()->create_grouping_group(
                array('groupingid' => $grouping->id, 'groupid' => $group2->id));

        $wiki = $generator->create_instance(array('course' => $course->id, 'groupmode' => SEPARATEGROUPS,
                'subwikis' => OUWIKI_SUBWIKIS_GROUPS, 'groupingid' => $grouping->id));
        $context = context_module::instance($wiki->cmid);

        $this->setUser($suser1);
        $newpage1 = $generator->create_content($wiki);
        $this->setUser($suser2);
        $newpage2 = $generator->create_content($wiki);

        self::fix_timemodified_order();

        // Now check we get results.
        $results = ouwiki_test_utils::recordset_to_array($page->get_recordset_by_timestamp());

        $this->assertCount(4, $results);
        $out = $page->get_document($results[0], array('lastindexedtime' => 0));
        $this->assertEquals('OU Wiki Test Page1', $out->get('title'));
        $this->assertEquals('Test content', $out->get('content'));
        $this->assertEquals($context->id, $out->get('contextid'));
        $this->assertEquals($newpage1->versionid, $out->get('itemid'));

        // Check group access.
        // For students in group1, they can access pages in group1 only.
        $this->setUser($suser1);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($newpage1->versionid));
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $page->check_access($newpage2->versionid));

        // For editing teachers, they can access pages in both group1 and group2.
        $this->setUser($etuser);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($newpage1->versionid));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($newpage2->versionid));

        // Check search result url for first page.
        $discussionurl = $page->get_doc_url($out)->out(false);
        $this->assertEquals($CFG->wwwroot . '/mod/ouwiki/view.php?id=' . $wiki->cmid .
                '&page=OU%20Wiki%20Test%20Page1', $discussionurl);

        // Edit first page then check search result again.
        $newpage2editedid = $generator->create_content($wiki, array(
                'newversion' => (object)array('content' => 'Test content revised', 'pagename' => 'OU Wiki Test Page1')));
        $results = ouwiki_test_utils::recordset_to_array($page->get_recordset_by_timestamp());
        $this->assertCount(4, $results);

        $out2 = $page->get_document($results[3], array('lastindexedtime' => 0));
        $this->assertEquals('Test content revised', $out2->get('content'));

        // Check individual access.
        // Create wiki with subwiki individual mode.
        $wiki2 = $generator->create_instance(array('course' => $course->id, 'subwikis' => OUWIKI_SUBWIKIS_INDIVIDUAL));
        $this->setUser($suser1);
        $newpage1wiki2 = $generator->create_content($wiki2);
        $this->setUser($suser2);
        $newpage2wiki2 = $generator->create_content($wiki2);
        $this->setUser($suser1);

        self::fix_timemodified_order();

        // For students, they just can access their own pages.
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($newpage1wiki2->versionid));
        $this->assertEquals(\core_search\manager::ACCESS_DENIED, $page->check_access($newpage2wiki2->versionid));

        // For editting teachers, they can access all pages.
        $this->setUser($etuser);
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($newpage1wiki2->versionid));
        $this->assertEquals(\core_search\manager::ACCESS_GRANTED, $page->check_access($newpage2wiki2->versionid));

        // Check page attachment.
        $fs = get_file_storage();
        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'mod_ouwiki',
            'filearea'  => \mod_ouwiki\search\page_version::FILEAREA['ATTACHMENT'],
            'itemid'    => $newpage1->versionid,
            'filepath'  => '/',
            'filename'  => 'file1.txt'
        );
        $file1 = $fs->create_file_from_string($filerecord, 'File 1 content');

        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'mod_ouwiki',
            'filearea'  => \mod_ouwiki\search\page_version::FILEAREA['CONTENT'],
            'itemid'    => $newpage1->versionid,
            'filepath'  => '/',
            'filename'  => 'file2.txt'
        );
        $file2 = $fs->create_file_from_string($filerecord, 'File 2 content');

        $ouwikipageareaid = \core_search\manager::generate_areaid('mod_ouwiki', 'page_version');
        $searcharea = \core_search\manager::get_search_area($ouwikipageareaid);

        $this->assertCount(0, $out->get_files());
        $searcharea->attach_files($out);
        $files = $out->get_files();
        $this->assertCount(2, $files);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            switch ($file->get_filearea()) {
                case \mod_ouwiki\search\page_version::FILEAREA['ATTACHMENT']:
                    $this->assertEquals('file1.txt', $file->get_filename());
                    $this->assertEquals('File 1 content', $file->get_content());
                    break;

                case \mod_ouwiki\search\page_version::FILEAREA['CONTENT']:
                    $this->assertEquals('file2.txt', $file->get_filename());
                    $this->assertEquals('File 2 content', $file->get_content());
                    break;

                default:
                    break;
            }
        }

        // Create a second wiki.
        $otherwiki = $generator->create_instance(['course' => $course->id,
                'groupmode' => NOGROUPS, 'subwikis' => OUWIKI_SUBWIKIS_SINGLE]);
        $generator->create_content($otherwiki);
        $othercontext = context_module::instance($otherwiki->cmid);

        // Test get_document_recordset with and without context.
        $results = self::recordset_to_array($page->get_document_recordset(0));
        $this->assertCount(10, $results);
        $results = self::recordset_to_array($page->get_document_recordset(0, $othercontext));
        $this->assertCount(2, $results);
    }

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param moodle_recordset $rs Record set to convert
     * @return \stdClass[] Array of converted records
     */
    protected static function recordset_to_array(moodle_recordset $rs) {
        $result = array();
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }

    /**
     * Ensures everything in ouwiki_versions has a unique timecreated in same order as
     * the creation id.
     */
    public static function fix_timemodified_order() {
        global $DB;

        $index = 100;
        foreach ($DB->get_fieldset_sql('SELECT id FROM {ouwiki_versions} ORDER BY id') as $id) {
            $DB->set_field('ouwiki_versions', 'timecreated', $index++, ['id' => $id]);
        }
    }

    public function test_ouwiki_posts_group_support() {
        $this->resetAfterTest(true);
        set_config('enableglobalsearch', true);
        $search = testable_core_search::instance();

        // Get the search area and test generators.
        $ouwikipageareaid = \core_search\manager::generate_areaid('mod_ouwiki', 'page_version');
        $searcharea = \core_search\manager::get_search_area($ouwikipageareaid);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_ouwiki');
        $page = new \mod_ouwiki\search\page_version();

        // Set up data.
        $course = $this->getDataGenerator()->create_course();

        $etuser = $this->getDataGenerator()->create_user();
        $suser1 = $this->getDataGenerator()->create_user();
        $suser2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($etuser->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($suser1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($suser2->id, $course->id, 'student');

        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $suser1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $suser2->id]);

        $wiki = $generator->create_instance(['course' => $course->id, 'groupmode' => SEPARATEGROUPS,
                'subwikis' => OUWIKI_SUBWIKIS_GROUPS]);

        $this->setUser($suser1);
        $newpage1 = $generator->create_content($wiki);
        $this->setUser($suser2);
        $newpage2 = $generator->create_content($wiki);

        // Create a second wiki.
        $otherwiki = $generator->create_instance(['course' => $course->id,
                'groupmode' => NOGROUPS, 'subwikis' => OUWIKI_SUBWIKIS_SINGLE]);
        $generator->create_content($otherwiki);
        self::fix_timemodified_order();
        $results = self::recordset_to_array($page->get_document_recordset(0));
        $this->assertCount(6, $results);

        $out1 = $page->get_document($results[0]);
        $this->assertTrue($out1->is_set('groupid'));
        $this->assertEquals($group1->id, $out1->get('groupid'));
        $out2 = $page->get_document($results[1]);
        $this->assertTrue($out2->is_set('groupid'));
        $this->assertEquals($group1->id, $out2->get('groupid'));

        $out3 = $page->get_document($results[2]);
        $this->assertTrue($out3->is_set('groupid'));
        $this->assertEquals($group2->id, $out3->get('groupid'));
        $out4 = $page->get_document($results[3]);
        $this->assertTrue($out4->is_set('groupid'));
        $this->assertEquals($group2->id, $out4->get('groupid'));

        $out5 = $page->get_document($results[4]);
        $this->assertFalse($out5->is_set('groupid'));
        $out6 = $page->get_document($results[5]);
        $this->assertFalse($out6->is_set('groupid'));

        // While we're here, also test that the search area requests restriction by group.
        $modinfo = get_fast_modinfo($course);
        $this->assertTrue($searcharea->restrict_cm_access_by_group($modinfo->get_cm($wiki->cmid)));

        // In visible groups mode, it won't request restriction by group.
        set_coursemodule_groupmode($wiki->cmid, VISIBLEGROUPS);
        $modinfo = get_fast_modinfo($course);
        $this->assertFalse($searcharea->restrict_cm_access_by_group($modinfo->get_cm($wiki->cmid)));
    }
}
