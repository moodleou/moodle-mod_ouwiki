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
 * Data provider tests for OU Wiki module.
 *
 * @package    mod_ouwiki
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\tests\provider_testcase;
use mod_ouwiki\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

/**
 * Data provider testcase class.
 *
 * @package    mod_ouwiki
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class mod_ouwiki_privacy_testcase extends provider_testcase {

    /** @var array */
    protected $users = [];
    /** @var array */
    protected $contexts = [];
    /** @var array */
    protected $pagepaths = [];
    /** @var array */
    protected $pages = [];
    /** @var stdClass */
    protected $generator;
    /** @var stdClass */
    protected $course;

    /**
     * Set up for each test.
     *
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $this->generator = $dg->get_plugin_generator('mod_ouwiki');
        $this->course = $dg->create_course();
        $this->users[1] = $dg->create_user();
        $this->users[2] = $dg->create_user();
        $this->users[3] = $dg->create_user();

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $dg->enrol_user($this->users[1]->id, $this->course->id, $studentrole->id, 'manual');
        $dg->enrol_user($this->users[2]->id, $this->course->id, $studentrole->id, 'manual');
        $dg->enrol_user($this->users[3]->id, $this->course->id, $studentrole->id, 'manual');

        // Create wiki 1 with subwiki individual mode.
        $ouwiki1 = $this->generator->create_instance(['course' => $this->course->id, 'subwikis' => OUWIKI_SUBWIKIS_INDIVIDUAL]);
        $cm1 = get_coursemodule_from_instance('ouwiki', $ouwiki1->id);

        $ouwiki2 = $this->generator->create_instance(['course' => $this->course->id]);
        $cm2 = get_coursemodule_from_instance('ouwiki', $ouwiki2->id);

        $ouwiki3 = $this->generator->create_instance(['course' => $this->course->id]);
        $cm3 = get_coursemodule_from_instance('ouwiki', $ouwiki3->id);

        $this->contexts = [
                1 => context_module::instance($cm1->id),
                2 => context_module::instance($cm2->id),
                3 => context_module::instance($cm3->id)
        ];
        $pagedata = new stdClass();
        $pagedata->pagename = 'Start page';
        // User 1.
        $this->setUser($this->users[1]);
        // Create Page 1 belong to context 1.
        $pagedata->newpagename = 'Test page 1 - User 1';
        $pagedata->content = 'Test content 1 - User 1';
        $pagedata->userid = $this->users[1];
        $this->pages[1][1] = $this->create_new_page($ouwiki1, $pagedata);
        // Create Page 2 belong to context 1.
        $pagedata->newpagename = 'Test page 2 - User 1';
        $pagedata->content = 'Test content 2 - User 1';
        $this->pages[1][2] = $this->create_new_page($ouwiki1, $pagedata);
        $this->attach_file($ouwiki1, 'attachment', $this->pages[1][2]->currentversionid, "Dog.jpg", 'jpg:Dog', $this->users[1]->id);
        $this->attach_file($ouwiki1, 'content', $this->pages[1][2]->currentversionid, "Dog.jpg", 'jpg:Dog', $this->users[1]->id);
        // Create Page 3 belong to context 2.
        $pagedata->newpagename = 'Test page 3 - User 1';
        $pagedata->content = 'Test content 3 - User 1';
        $this->pages[1][3] = $this->create_new_page($ouwiki2, $pagedata);

        // User2.
        $this->setUser($this->users[2]);
        $pagedata->userid = $this->users[2];
        // Create Page 1 belong to context 1.
        $pagedata->newpagename = 'Test page 1 - User 2';
        $pagedata->content = 'Test content 1 - User 2';
        $this->pages[2][1] = $this->create_new_page($ouwiki1, $pagedata);

        // Create Page 2 belong to context 2.
        $pagedata->newpagename = 'Test page 2 - User 2';
        $pagedata->content = 'Test content 2 - User 2 ' .
                ouwiki_display_user($this->users[2], $this->course->id) . ' some text ' .
                ouwiki_display_user($this->users[1], $this->course->id) .
                ouwiki_display_user($this->users[2], $this->course->id);
        $this->pages[2][2] = $this->create_new_page($ouwiki2, $pagedata);
        $this->attach_file($ouwiki2, 'attachment', $this->pages[2][2]->currentversionid, "Cat.jpg", 'jpg:Cat', $this->users[2]->id);
        $this->attach_file($ouwiki2, 'content', $this->pages[2][2]->currentversionid, "Cat.jpg", 'jpg:Cat', $this->users[2]->id);

        // Create Page 3 belong to context 3.
        $pagedata->newpagename = 'Test page 3 - User 2';
        $pagedata->content = 'Test content 3 - User 2';
        $this->pages[2][3] = $this->create_new_page($ouwiki3, $pagedata);

        $this->pagepaths = [
                1 => [
                        1 => $this->pages[1][1]->pageid . ' ' . $this->pages[1][1]->title,
                        2 => $this->pages[1][2]->pageid . ' ' . $this->pages[1][2]->title,
                        3 => $this->pages[1][3]->pageid . ' ' . $this->pages[1][3]->title
                ],
                2 => [
                        1 => $this->pages[2][1]->pageid . ' ' . $this->pages[2][1]->title,
                        2 => $this->pages[2][2]->pageid . ' ' . $this->pages[2][2]->title,
                        3 => $this->pages[2][3]->pageid . ' ' . $this->pages[2][3]->title
                ]
        ];
    }

    /**
     * Generate a page in ouwiki as current user
     *
     * @param stdClass $ouwiki
     * @param stdClass $pagedata
     * @return stdClass
     */
    protected function create_new_page($ouwiki, $pagedata) {
        $record = [];
        $record['newpage'] = $pagedata;
        return $this->generator->create_content($ouwiki, $record);
    }

    /**
     * Attach file to a ouwiki as a current user
     *
     * @param stdClass $ouwiki
     * @param string $filearea
     * @param $versionid
     * @param string $filename
     * @param string $filecontent
     * @param int $userid
     * @return stored_file
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    protected function attach_file($ouwiki, $filearea, $versionid, $filename, $filecontent, $userid) {
        $fs = get_file_storage();
        return $fs->create_file_from_string([
                'contextid' => context_module::instance($ouwiki->cmid)->id,
                'component' => 'mod_ouwiki',
                'filearea' => $filearea,
                'itemid' => $versionid,
                'filepath' => '/',
                'filename' => $filename,
                'userid' => $userid
        ], $filecontent);
    }

    /**
     * Ensure that export_user_preferences returns no data if the user has no data.
     *
     * @throws coding_exception
     */
    public function test_export_user_preferences_not_defined() {
        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);

        $writer = writer::with_context(context_user::instance($user->id));
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test for provider::test_export_user_preferences().
     *
     * @throws coding_exception
     */
    public function test_export_user_preferences() {
        $this->setUser($this->users[1]);
        set_user_preference('ouwiki_hide_annotations', 1, $this->users[1]);

        // Test the user preferences export contains 1 user preference record for the User.
        provider::export_user_preferences($this->users[1]->id);
        $contextuser = context_user::instance($this->users[1]->id);
        $writer = writer::with_context($contextuser);
        $this->assertTrue($writer->has_any_data());

        $exportedpreferences = $writer->get_user_preferences('mod_ouwiki');
        $this->assertCount(1, (array) $exportedpreferences);
        $this->assertEquals('Yes', $exportedpreferences->ouwiki_hide_annotations->value);
        $this->assertEquals(get_string('privacy:metadata:preferences:ouwiki_hide_annotations', 'mod_ouwiki'),
                $exportedpreferences->ouwiki_hide_annotations->description);
    }

    /**
     * Test get context list for user id.
     */
    public function test_get_contexts_for_userid() {
        // Get contexts for the first user.
        $contextids = provider::get_contexts_for_userid($this->users[1]->id)->get_contextids();
        $this->assertEquals([
                $this->contexts[1]->id,
                $this->contexts[2]->id
        ], $contextids);
        // Get contexts for the second user.
        $contextids = provider::get_contexts_for_userid($this->users[2]->id)->get_contextids();
        $this->assertEquals([
                $this->contexts[1]->id,
                $this->contexts[2]->id,
                $this->contexts[3]->id
        ], $contextids);

    }

    /**
     * Test export data for first user.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_user_data1() {
        // Export all contexts for the first user.
        $contextids = array_values(array_map(function($c) {
            return $c->id;
        }, $this->contexts));
        $appctx = new approved_contextlist($this->users[1], 'mod_ouwiki', $contextids);
        provider::export_user_data($appctx);
        // First wiki has two pages ever touched by this user.
        $data1 = writer::with_context($this->contexts[1])->get_related_data($this->get_param_for_test_export_user_data(1, 1));
        $data2 = writer::with_context($this->contexts[1])->get_related_data($this->get_param_for_test_export_user_data(1, 2));
        // Second wiki has one page ever touched by this user.
        $data3 = writer::with_context($this->contexts[2])->get_related_data($this->get_param_for_test_export_user_data(1, 3));
        $this->assertEquals($this->pagepaths[1][1], array_keys($data1)[0]);
        $this->assertEquals($this->pagepaths[1][2], array_keys($data2)[0]);
        $this->assertEquals($this->pagepaths[1][3], array_keys($data3)[0]);
        // First page was initially created by this user and all its information is returned to this user.
        $data11 = $data1[$this->pagepaths[1][1]];
        $this->assertEquals(1, count($data11['revisions']));
        $lastrevision = array_pop($data11['revisions']);
        $this->assertEquals($this->pages[1][1]->xhtml, $lastrevision['content']);
        $this->assertNotEmpty($lastrevision['timecreated']);
        // Second page has a attachment upload by this user.
        $files = writer::with_context($this->contexts[1])->get_files($this->get_param_for_test_export_user_data(1, 2));
        $this->assertEquals(['Dog.jpg'], array_keys($files));
    }

    /**
     * Test export data for second user.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_user_data2() {
        // Export all contexts for the first user.
        $contextids = array_values(array_map(function($c) {
            return $c->id;
        }, $this->contexts));
        $appctx = new approved_contextlist($this->users[2], 'mod_ouwiki', $contextids);
        provider::export_user_data($appctx);
        $data1 = writer::with_context($this->contexts[1])->get_related_data($this->get_param_for_test_export_user_data(2, 1));
        $data2 = writer::with_context($this->contexts[2])->get_related_data($this->get_param_for_test_export_user_data(2, 2));
        $data3 = writer::with_context($this->contexts[3])->get_related_data($this->get_param_for_test_export_user_data(2, 3));
        $this->assertEquals($this->pagepaths[2][1], array_keys($data1)[0]);
        $this->assertEquals($this->pagepaths[2][2], array_keys($data2)[0]);
        $this->assertEquals($this->pagepaths[2][3], array_keys($data3)[0]);
        // First page was initially created by this user and all its information is returned to this user.
        $data11 = $data1[$this->pagepaths[2][1]];
        $this->assertEquals(1, count($data11['revisions']));
        $lastrevision = array_pop($data11['revisions']);
        $this->assertEquals($this->pages[2][1]->xhtml, $lastrevision['content']);
        $this->assertNotEmpty($lastrevision['timecreated']);
        // Second page has a attachment upload by this user.
        $files = writer::with_context($this->contexts[2])->get_files($this->get_param_for_test_export_user_data(2, 2));
        $this->assertEquals(['Cat.jpg'], array_keys($files));
    }

    /**
     * Test for delete_data_for_all_users_in_context().
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_delete_data_for_all_users_in_context() {
        provider::delete_data_for_all_users_in_context($this->contexts[1]);
        $appctx = new approved_contextlist($this->users[1], 'mod_ouwiki', [
                $this->contexts[1]->id, $this->contexts[2]->id]);
        provider::export_user_data($appctx);
        $this->assertFalse(writer::with_context($this->contexts[1])->has_any_data());
        $this->assertTrue(writer::with_context($this->contexts[2])->has_any_data());

        writer::reset();
        $appctx = new approved_contextlist($this->users[2], 'mod_ouwiki', [$this->contexts[1]->id]);
        provider::export_user_data($appctx);
        $this->assertFalse(writer::with_context($this->contexts[1])->has_any_data());
    }

    /**
     * Test delete individual user data
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_delete_individual_user_data() {
        global $DB;
        // User 1.
        // Before deleted.
        $this->assertCount(2, $DB->get_records('files', ['itemid' => $this->pages[1][2]->currentversionid,
                'userid' => $this->users[1]->id, 'filearea' => 'attachment', 'component' => 'mod_ouwiki']));
        $this->assertCount(2, $DB->get_records('files', ['itemid' => $this->pages[1][2]->currentversionid,
                'userid' => $this->users[1]->id, 'filearea' => 'content', 'component' => 'mod_ouwiki']));
        $appctx = new approved_contextlist($this->users[1], 'mod_ouwiki', [$this->contexts[1]->id, $this->contexts[2]->id]);
        provider::delete_individual_user_data($appctx);
        // After deleted.
        // Files and related data for the pages of this user should be removed.
        $this->assertCount(0, $DB->get_records('ouwiki_versions', ['pageid' => $this->pages[1][1]->pageid]));
        $this->assertCount(0, $DB->get_records('ouwiki_annotations', ['pageid' => $this->pages[1][1]->pageid]));
        $this->assertCount(0, $DB->get_records('ouwiki_locks', ['pageid' => $this->pages[1][1]->pageid]));
        $this->assertCount(0, $DB->get_records('files', ['itemid' => $this->pages[1][2]->currentversionid,
                'userid' => $this->users[1]->id, 'filearea' => 'attachment', 'component' => 'mod_ouwiki']));
        $this->assertCount(0, $DB->get_records('ouwiki_pages', ['id' => $this->pages[1][1]->pageid]));
        $this->assertCount(0, $DB->get_records('ouwiki_pages', ['id' => $this->pages[1][2]->pageid]));
        $this->assertCount(0, $DB->get_records('ouwiki_subwikis', ['userid' => $this->users[1]->id]));
        // Other datas not related with this user should remain.
        $this->assertCount(1, $DB->get_records('ouwiki_versions', ['pageid' => $this->pages[2][1]->pageid]));
        $this->assertCount(1, $DB->get_records('ouwiki_versions', ['pageid' => $this->pages[2][2]->pageid]));
        $this->assertCount(1, $DB->get_records('ouwiki_versions', ['pageid' => $this->pages[2][3]->pageid]));
        $this->assertCount(1, $DB->get_records('ouwiki_subwikis', ['id' => $this->pages[1][3]->subwikiid]));
        $this->assertCount(1, $DB->get_records('ouwiki_subwikis', ['id' => $this->pages[2][1]->subwikiid]));
        $this->assertCount(1, $DB->get_records('ouwiki_pages', ['id' => $this->pages[2][1]->pageid]));
    }

    /**
     * Test for delete population user data
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_process_population_user_data() {
        global $DB;
        // User 2
        // Create annotation.
        $this->generator->create_annotation($this->pages[2][2]->pageid, $this->users[2]->id, 'Annotaion content');
        $this->generator->create_annotation($this->pages[2][2]->pageid, $this->users[1]->id, 'Annotaion content');
        // Before process.
        $this->assertCount(2, $DB->get_records('files', ['itemid' => $this->pages[2][2]->currentversionid,
                'userid' => $this->users[2]->id, 'filearea' => 'attachment', 'component' => 'mod_ouwiki']));
        $this->assertCount(2, $DB->get_records('files', ['itemid' => $this->pages[2][2]->currentversionid,
                'userid' => $this->users[2]->id, 'filearea' => 'content', 'component' => 'mod_ouwiki']));
        $appctx = new approved_contextlist($this->users[2], 'mod_ouwiki', [$this->contexts[2]->id, $this->contexts[3]->id]);
        $adminid = get_admin()->id;
        provider::process_population_user_data($appctx);
        $this->assertCount(0, $DB->get_records('ouwiki_locks', ['pageid' => $this->pages[2][2]->pageid, 'userid' => $adminid]));
        $this->assertCount(1, $DB->get_records('ouwiki_versions', ['pageid' => $this->pages[2][1]->pageid, 'userid' => $adminid]));
        $this->assertCount(1, $DB->get_records('ouwiki_versions', ['pageid' => $this->pages[2][2]->pageid, 'userid' => $adminid]));
        $this->assertCount(1, $DB->get_records('ouwiki_versions', ['pageid' => $this->pages[2][3]->pageid, 'userid' => $adminid]));
        $this->assertCount(1, $DB->get_records('ouwiki_subwikis', ['id' => $this->pages[2][1]->subwikiid, 'userid' => $adminid]));
        $this->assertCount(1, $DB->get_records('ouwiki_pages', ['id' => $this->pages[2][1]->pageid]));
        $this->assertCount(2, $DB->get_records('files', ['itemid' => $this->pages[2][2]->currentversionid, 'userid' => $adminid,
                'filearea' => 'attachment', 'component' => 'mod_ouwiki']));
        $this->assertCount(2, $DB->get_records('files', ['itemid' => $this->pages[2][2]->currentversionid, 'userid' => $adminid,
                'filearea' => 'content', 'component' => 'mod_ouwiki']));
        // Test annotations after replace content.
        $annotation = $DB->get_record('ouwiki_annotations', ['pageid' => $this->pages[2][2]->pageid, 'userid' => $adminid]);
        $this->assertContains(get_string('privacy:annotationdeleted', 'mod_ouwiki'), $annotation->content);
        $annotation = $DB->get_record('ouwiki_annotations', [
                'pageid' => $this->pages[2][2]->pageid, 'userid' => $this->users[1]->id]);
        $this->assertNotEquals(get_string('privacy:annotationdeleted', 'mod_ouwiki'), $annotation->content);
        // Test versions after replace content.
        $version = $DB->get_record('ouwiki_versions', ['pageid' => $this->pages[2][2]->pageid, 'userid' => $adminid]);
        $this->assertContains(get_string('privacy:xhtmlcontentdeleted', 'mod_ouwiki'), $version->xhtml);
        $this->assertContains(ouwiki_display_user($this->users[1], $this->course->id), $version->xhtml);
    }

    /**
     * Get praram to test export user data
     *
     * @param $idxuser Index user
     * @param $idxpage Index context
     * @return array param fo writer::with_context($context)->get_related_data()
     * @throws coding_exception
     */
    private function get_param_for_test_export_user_data($idxuser, $idxpage) {
        $pathouwiki = $this->pages[$idxuser][$idxpage]->subwikiid . ' ' . get_string('subwikis', 'mod_ouwiki');
        $pathpage = $this->pages[$idxuser][$idxpage]->pageid . ' ' . $this->pages[$idxuser][$idxpage]->title;

        return [
                $pathouwiki, $pathpage
        ];
    }
}
