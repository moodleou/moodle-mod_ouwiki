<?php
/**
 * Unit tests for (some of) mod/ouwiki/locallib.php.
 *
 * @author Catalyst IT Ltd. 
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

class ouwiki_locallib_test extends UnitTestCaseUsingDatabase {

    public static $includecoverage = array('mod/ouwiki/locallib.php');
    public $tables = array('lib' => array(
                                      'course_categories',
                                      'course',
                                      'files',
                                      'modules',
                                      'context',
                                      'course_modules',
                                      'user',
                                      'groups',
                                      'groups_members',
                                      'capabilities',
                                      'role_assignments',
                                      'role_capabilities',
                                      ),
                                  'mod/ouwiki' => array(
                                      'ouwiki',
                                      'ouwiki_annotations',
                                      'ouwiki_links',
                                      'ouwiki_locks',
                                      'ouwiki_pages',
                                      'ouwiki_sections',
                                      'ouwiki_subwikis'
                                     ,'ouwiki_versions')
                            );

     public $usercount = 0;

     /**
     * Create temporary test tables and entries in the database for these tests.
     * These tests have to work on a brand new site.
     */
    function setUp() {
        global $CFG;

        parent::setup();

        $this->switch_to_test_db(); // All operations until end of test method will happen in test DB

        // additional tables required if ousearch module is present
        if (ouwiki_search_installed()) {
            $this->tables['local/ousearch'] = array(
                'local_ousearch_documents',
                'local_ousearch_words',
                'local_ousearch_occurrences');
        }

        foreach ($this->tables as $dir => $tables) {
            $this->create_test_tables($tables, $dir); // Create tables
            foreach ($tables as $table) { // Fill them if load_xxx method is available
                $function = "load_$table";
                if (method_exists($this, $function)) {
                    $this->$function();
                }
            }
        }

    }

    function tearDown() {
        parent::tearDown(); // All the test tables created in setUp will be dropped by this
    }

    function load_course_categories() {
        $cat = new stdClass();
        $cat->name = 'misc';
        $cat->depth = 1;
        $cat->path = '/1';
        $this->testdb->insert_record('course_categories', $cat);
    }

    /**
     * Load module entries in modules table
     */
    function load_modules() {
        $module = new stdClass();
        $module->name = 'ouwiki';
        $module->id = $this->testdb->insert_record('modules', $module);
        $this->modules[] = $module;
    }
    
    function load_capabilities() {
        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:edit';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:view';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:overridelock';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:viewgroupindividuals';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:viewallindividuals';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:deletepage';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:lock';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:annotate';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

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

     Functions not covered:
         Delete/undelete page version - no backend functions for this process
         File attachment - difficult to test through backend functions due to moodle core handling of files
          
    */

    function test_ouwiki_get_subwiki() {

        // create course, ouwiki, course module, context, groupid, userid, 
        $user   = $this->get_new_user();
        $course = $this->get_new_course();

    // Test whole course wiki
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = $this->get_new_course_module($course->id, $ouwiki->id, NOGROUPS);
        $context = get_context_instance(CONTEXT_MODULE, $cm->instance);
        $groupid = 0;

        // subwiki with 'create'
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id, true);
        $createdsubwikiid = $subwiki->id;
        $this->assertIsA($subwiki, "stdClass");

        // re: get the same one we created above (without 'create') 
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id);
        $this->assertEqual($subwiki->id, $createdsubwikiid);

    // Test group wikis 
        $groupmodes = array(SEPARATEGROUPS, VISIBLEGROUPS);

        foreach($groupmodes as $groupmode) {

            $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_GROUPS);
            $cm = $this->get_new_course_module($course->id, $ouwiki->id, $groupmode);
            $context = get_context_instance(CONTEXT_MODULE, $cm->instance);
            $group = $this->get_new_group($course->id);

            // subwiki with 'create'
            $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $group->id, $user->id, true);
            $createdsubwikiid = $subwiki->id;
            $this->assertIsA($subwiki, "stdClass");

            // re: get the same one we created above (without 'create') 
            $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id);
            $this->assertEqual($subwiki->id, $createdsubwikiid);

            // try with a member enrolled
            $member = $this->get_new_group_member($groupid, $user->id); // enrols the default user

            // subwiki with 'create'
            $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $group->id, $user->id, true);
            $createdsubwikiid = $subwiki->id;
            $this->assertIsA($subwiki, "stdClass");

            // re: get the same one we created above (without 'create') 
            $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id);
            $this->assertEqual($subwiki->id, $createdsubwikiid);
       }

    // Test invididual wikis
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_INDIVIDUAL);
        $cm = $this->get_new_course_module($course->id, $ouwiki->id);
        $context = get_context_instance(CONTEXT_MODULE, $cm->instance);
        $groupid = 0;

        // subwiki with 'create'
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id, true);
        $createdsubwikiid = $subwiki->id;
        $this->assertIsA($subwiki, "stdClass");

        // re: get the same one we created above (without 'create') 
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id);
        $this->assertEqual($subwiki->id, $createdsubwikiid);
    }

    function test_ouwiki_pages_and_versions() {

        $user   = $this->get_new_user();
        $course = $this->get_new_course();

        // setup a wiki to use
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = $this->get_new_course_module($course->id, $ouwiki->id, NOGROUPS);
        $context = get_context_instance(CONTEXT_MODULE, $cm->instance);
        $groupid = 0;
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id, true);

        // create the start page
        $startpagename = 'startpage';
        $formdata = null;
        $startpageversion = ouwiki_get_current_page($subwiki, $startpagename, OUWIKI_GETPAGE_CREATE);
        ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $startpagename, $startpagename, -1, -1, -1, null, $formdata);
        $this->assertIsA($subwiki, "stdClass");

        // create a page
        $pagename1 = 'testpage1';
        $content1 = 'testcontent';

        // we don't get anything returned for this
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $startpagename, $pagename1, $content1, $formdata);  

        // try get that page
        $pageversion = ouwiki_get_current_page($subwiki, $pagename1);
        $this->assertIsA($subwiki, "stdClass");
        $this->assertEqual($pageversion->title, $pagename1);

        // make some more version
        $content2 = "testcontent2";
        $content3 = "testcontent3";
        ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pagename1, $content2, -1, -1, -1, null, $formdata);
        ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pagename1, $content3, -1, -1, -1, null, $formdata);

        // get the history
        $history = ouwiki_get_page_history($pageversion->pageid, true);
        $this->assertIsA($history, "array");

        // last version should match $content3
        $lastversion = array_shift($history);
        $pageversion = ouwiki_get_page_version($subwiki, $pagename1, $lastversion->versionid);
        $this->assertEqual($pageversion->xhtml, $content3);
 
        // add another page
        $pagename2 = 'testpage2';
        $content4  = 'testcontent4';

        // we don't get anything returned for this
        ouwiki_create_new_page($course, $cm, $ouwiki, $subwiki, $startpagename, $pagename2, $content4, $formdata); 

        // test recent pages
        $changes = ouwiki_get_subwiki_recentpages($subwiki->id);
        $this->assertIsA($changes, "array");

        // first page should be startpage
        $this->assertEqual($changes[1]->title, $startpagename);
        // 3rd page should be pagename2
        $this->assertEqual($changes[3]->title, $pagename2);

        // test recent wiki changes
        $changes = ouwiki_get_subwiki_recentchanges($subwiki->id);

        $this->assertEqual($changes[1]->title, $startpagename);
        // sixth change should be to testpage2  - when we created testpage2
        $this->assertEqual($changes[6]->title, $pagename2);
        // seventh change shouldbe start page again - when we linked to testpage2 to startpage
        $this->assertEqual($changes[7]->title, $startpagename);

        // test deleting a version

    }

    function test_ouwiki_init_pages() {
 
        $user   = $this->get_new_user();
        $course = $this->get_new_course();
        $ouwiki = $this->get_new_ouwiki($course->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = $this->get_new_course_module($course->id, $ouwiki->id, NOGROUPS);
        $context = get_context_instance(CONTEXT_MODULE, $cm->instance);
        $groupid = 0;
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $user->id, true);

        // doesn't return anything
        ouwiki_init_pages($course, $cm, $ouwiki, $subwiki, $ouwiki);
    } 

    function test_ouwiki_word_count() {
        $tests = array();

        $test['string'] = "This is four words";
        $test['count'] = 4;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = " ";
        $test['count'] = 0;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = "word";
        $test['count'] = 1;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = "Two\n\nwords";
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = "<p><b>two <i>words</i></b></p>";
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = "Isn’t it three";
        $test['count'] = 3;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = "Isn't it three";
        $test['count'] = 3;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = "three-times-hyphenated words";
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = "one,two,さん";
        $test['count'] = 3;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);

        $test['string'] = 'Two&nbsp;words&nbsp;&nbsp;&nbsp;&nbsp;';
        $test['count'] = 2;
        $testcount = ouwiki_count_words($test['string']);
        $this->assertEqual($testcount, $test['count']);
    }

    /* 
     These functions enable us to create database entries and/or grab objects to make it possible to test the 
     many permuations required for OU Wiki.

    */

    function get_new_user() {

        $this->usercount++;

        $user = new stdClass();
        $user->username = 'testuser' . $this->usercount; 
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $user->id = $this->testdb->insert_record('user', $user);
        return $user;
    }


    function get_new_course() {
        $course = new stdClass();
        $course->category = 1;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $course->id = $this->testdb->insert_record('course', $course);
        return $course;
    }
  
    public function get_new_ouwiki($courseid, $subwikis=0, $options=array()) {

        $ouwiki = new stdClass();
        $ouwiki->course = $courseid;
        $ouwiki->name = 'Test wiki';
        $ouwiki->subwikis = $subwikis;

        $ouwiki->timeout = null;
        $ouwiki->template = null;
        $ouwiki->editbegin = null;
        $ouwiki->editend = null;

        $ouwiki->completionpages = 0;
        $ouwiki->completionedits = 0;
        $ouwiki->annotation = 0;
        $ouwiki->introformat =0 ;

        $ouwiki->id = $this->testdb->insert_record('ouwiki', $ouwiki);
        return $ouwiki;

    }

    public function get_new_course_module($courseid, $ouwikiid, $groupmode=0) {
        $cm = new stdClass();
        $cm->course = $courseid;
        $cm->module = $this->modules[0]->id;
        $cm->instance = $ouwikiid;
        $cm->groupmode = $groupmode;
        $cm->groupingid = 0;
        $cm->id = $this->testdb->insert_record('course_modules', $cm);
        return $cm;
    }

    public function get_new_group($courseid) {
        $group = new stdClass();
        $group->courseid = $courseid;
        $group->name = 'test group';
        $group->id = $this->testdb->insert_record('groups', $group);
        return $group;
    }

    public function get_new_group_member($groupid, $userid) {
        $member = new stdClass();
        $member->groupid = $groupid;
        $member->userid = $userid;
        $member->id = $this->testdb->insert_record('groups_members', $member);
        return $member;

    }

}
