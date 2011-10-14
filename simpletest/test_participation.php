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
 * Unit tests for mod/ouwiki participation feature
 *
 * @package mod
 * @subpackage ouwiki
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/mod/ouwiki/locallib.php'); // Include the code to test

/** This class contains the test cases for the functions in editlib.php. */
class ouwiki_participation_test extends UnitTestCaseUsingDatabase {
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
                                      'role',
                                      'role_assignments',
                                      'role_capabilities',
                                      ),
                                  'mod/ouwiki' => array(
                                      'ouwiki',
                                      'ouwiki_subwikis',
                                      'ouwiki_pages',
                                      'ouwiki_versions',
                                      'ouwiki_links',
                                  )
                         );

    /**
     * Backend functions covered:
     *      ouwiki_can_view_participation($course, $ouwiki, $cm, $userid)
     *      ouwiki_get_user_participation($userid, $subwiki)
     *      ouwiki_get_participation($allusers, $ouwiki, $subwiki, $offset,
     *                  $limit, $sort='u.firstname, u.lastname')
     *      ouwiki_sort_participation($data)
     *
     *  Backend functions not covered:
     *      ouwiki_grade_item_update($ouwiki, $grades = null)
     *      ouwiki_grade_item_delete($ouwiki)
     *      ouwiki_update_grades($newgrades, $cm, $ouwiki, $course)
     *      ouwiki_get_user($userid)
     *      ouwiki_render_participation_list($cm, $course, $pagename, $groupid,
     *                  $ouwiki, $subwiki, $download)
     *      ouwiki_render_user_participation($user, $changes, $cm, $course, $ouwiki,
     *                  $subwiki, $pagename, $groupid, $download, $canview)
     *      ouwiki_render_grade($course, $cm, $ouwiki, $user, $pagename, $groupid)
     **/

    public function test_canview_course_wiki() {
        $ouwiki = $this->get_new_ouwiki($this->courses['single']->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = $this->get_new_course_module($this->courses['single']->id, $ouwiki->id, NOGROUPS);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $subwiki = ouwiki_get_subwiki($this->courses['single'], $ouwiki, $cm,
            $context, 0, $this->admin->id, true);

        // can view all user participation
        $canview = ouwiki_can_view_participation($this->courses['single'],
            $ouwiki, $subwiki, $cm, $this->admin->id);
        $this->assertEqual($canview, OUWIKI_USER_PARTICIPATION);

        $canview = ouwiki_can_view_participation($this->courses['single'],
            $ouwiki, $subwiki, $cm, $this->teacher->id);
        $this->assertEqual($canview, OUWIKI_USER_PARTICIPATION);

        // can only view own participation
        $canview = ouwiki_can_view_participation($this->courses['single'],
            $ouwiki, $subwiki, $cm, $this->student->id);
        $this->assertEqual($canview, OUWIKI_MY_PARTICIPATION);

        // can't view anything
        $canview = ouwiki_can_view_participation($this->courses['single'],
            $ouwiki, $subwiki, $cm, $this->nouser->id);
        $this->assertEqual($canview, OUWIKI_NO_PARTICIPATION);
    }

    public function test_canview_group_wiki() {
        $ouwiki = $this->get_new_ouwiki($this->courses['group']->id, OUWIKI_SUBWIKIS_GROUPS);
        $cm = $this->get_new_course_module($this->courses['group']->id,
            $ouwiki->id, SEPARATEGROUPS);
        $context = get_context_instance(CONTEXT_MODULE, $cm->instance);
        $group = $this->get_new_group($this->courses['group']->id);
        $subwiki = ouwiki_get_subwiki($this->courses['group'], $ouwiki, $cm,
            $context, $group->id, $this->admin->id, true);

        // student is a member of the group
        $member = $this->get_new_group_member($group->id, $this->student->id);

        // can only view own participation
        $canview = ouwiki_can_view_participation($this->courses['group'],
            $ouwiki, $subwiki, $cm, $this->student->id);
        $this->assertEqual($canview, OUWIKI_MY_PARTICIPATION);

        // can view all user participation
        $canview = ouwiki_can_view_participation($this->courses['group'],
            $ouwiki, $subwiki, $cm, $this->admin->id);
        $this->assertEqual($canview, OUWIKI_USER_PARTICIPATION);
    }

    public function test_participation() {
        $ouwiki = $this->get_new_ouwiki($this->courses['single']->id, OUWIKI_SUBWIKIS_SINGLE);
        $cm = $this->get_new_course_module($this->courses['single']->id, $ouwiki->id, NOGROUPS);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $subwiki = ouwiki_get_subwiki($this->courses['single'], $ouwiki, $cm,
            $context, 0, $this->admin->id, true);
        $pageversion = ouwiki_get_current_page($subwiki, 'TEST PAGE', OUWIKI_GETPAGE_CREATE);
        $user = $this->get_new_user('student');

        $content = 'content';
        $plus = ' plus';
        for ($i = 1; $i <= 5; $i++) {
            $content .= $plus . $i;
            $wordcount = ouwiki_count_words($content);
            $this->save_new_version($pageversion->pageid, $content, $user->id, $wordcount);
        }
        // remove one word
        $content = preg_replace('/plus3/', '', $content);
        $wordcount = ouwiki_count_words($content);
        $this->save_new_version($pageversion->pageid, $content, $user->id, $wordcount);

        list($returneduser, $participation) = ouwiki_get_user_participation($user->id, $subwiki);
        $this->assertEqual($returneduser->id, $user->id);
        $this->assertNotNull($participation);

        // another user
        $user2 = $this->get_new_user('student');
        $users[0] = $user->id;
        $users[1] = $user2->id;
        $participation = ouwiki_get_participation($ouwiki, $subwiki, $context, 0);
        $this->assertNotNull($participation);

        $userexists = array_key_exists($user->id, $participation);
        $this->assertTrue($userexists);

        // a user who isn't enrolled
        $userexists = array_key_exists($this->nouser->id, $participation);
        $this->assertFalse($userexists);
    }

    public function setUp() {
        global $ACCESSLIB_PRIVATE;
        parent::setUp();

        // All operations until end of test method will happen in test DB
        $this->switch_to_test_db();

        foreach ($this->tables as $dir => $tables) {
            $this->create_test_tables($tables, $dir); // Create tables
        }

        // clear context cache for testing with dummy system context etc
        $ACCESSLIB_PRIVATE->contexcache = new context_cache();
        $context = new stdClass();
        $context->contextlevel = CONTEXT_SYSTEM;
        $context->instanceid   = 0;
        $context->depth        = 1;
        $context->path         = null; //not known before insert
        $context->id = $this->testdb->insert_record('context', $context);
        $context->path         = '/' . $context->id;
        $this->testdb->update_record('context', $context);
        $ACCESSLIB_PRIVATE->systemcontext = $context->id;

        $this->load_course_category();
        $this->load_modules();
        $this->load_roles();
        $this->load_capabilities();
        $this->load_role_capabilities();

        // load test courses and contexts
        $this->courses['single'] = $this->get_new_course(0);
        $this->contexts['single'] = get_context_instance(CONTEXT_COURSE,
            $this->courses['single']->id);

        $this->courses['group'] = $this->get_new_course(1);
        $this->contexts['group'] = get_context_instance(CONTEXT_COURSE,
            $this->courses['group']->id);

        $this->admin      = $this->get_new_user('admin');
        $this->teacher    = $this->get_new_user('editingteacher');
        $this->student    = $this->get_new_user('student');
        $this->nouser     = $this->get_new_user(null); // this user will have no role in courses
    }

    public function load_course_category() {
        $cat = new stdClass();
        $cat->name = 'misc';
        $cat->depth = 1;
        $cat->path = '/1';
        $cat->id = $this->testdb->insert_record('course_categories', $cat);
        $this->category = $cat;
    }

    public function load_modules() {
        $module = new stdClass();
        $module->name = 'ouwiki';
        $module->id = $this->testdb->insert_record('modules', $module);
        $this->modules[] = $module;
    }

    public function load_roles() {
        $count = 1;
        $types = array('admin', 'editingteacher', 'student');
        foreach ($types as $type) {
            $role = new stdClass;
            $role->name = $type;
            $role->shortname = $type;
            $role->description = $type;
            $role->sortorder = $count;
            $role->archetype = $type;
            $role->id = $this->testdb->insert_record('role', $role);
            $this->roles[$type] = $role;
            $count++;
        }
    }

    public function load_capabilities() {
        $cap = new stdClass();
        $cap->name = 'moodle/site:accessallgroups';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:viewparticipation';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:edit';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;

        $cap = new stdClass();
        $cap->name = 'mod/ouwiki:annotate';
        $cap->id = $this->testdb->insert_record('capabilities', $cap);
        $this->capabilities[] = $cap;
    }

    public function load_role_capabilities() {
        foreach ($this->roles as $role) {
            // all can edit
            $rolecap = new stdclass;
            $rolecap->contextid = 1;
            $rolecap->roleid = $role->id;
            $rolecap->capability = 'mod/ouwiki:edit';
            $rolecap->permission = 1;
            $rolecap->timemodified = time();
            $rolecap->modifier = 2;
            $rolecap->id = $this->testdb->insert_record('role_capabilities', $rolecap);
            $this->rolecapablities[] = $rolecap;

            if ($role->shortname === 'admin'
                || $role->shortname === 'editingteacher') {
                $rolecap = new stdclass;
                $rolecap->contextid = 1;
                $rolecap->roleid = $role->id;
                $rolecap->capability = 'mod/ouwiki:viewparticipation';
                $rolecap->permission = 1;
                $rolecap->timemodified = time();
                $rolecap->modifier = 2;
                $rolecap->id = $this->testdb->insert_record('role_capabilities', $rolecap);
                $this->rolecapablities[] = $rolecap;

                if ($role->shortname === 'admin') {
                    $rolecap = new stdclass;
                    $rolecap->contextid = 1;
                    $rolecap->roleid = $role->id;
                    $rolecap->capability = 'moodle/site:accessallgroups';
                    $rolecap->permission = 1;
                    $rolecap->timemodified = time();
                    $rolecap->modifier = 2;
                    $rolecap->id = $this->testdb->insert_record('role_capabilities', $rolecap);
                }
            }
        }
    }

    public function get_new_user($rolename) {
        $this->usercount++;
        $user = new stdClass();
        $user->username = 'testuser' . $this->usercount;
        $user->firstname = 'Test';
        $user->lastname = 'User';
        $user->id = $this->testdb->insert_record('user', $user);

        // assign roles
        if ($rolename) {
            foreach ($this->contexts as $context) {
                $role = $this->roles[$rolename];
                $roleassign =  new stdClass;
                $roleassign->roleid = $role->id;
                $roleassign->userid = $user->id;
                $roleassign->contextid = $context->id;
                $this->testdb->insert_record('role_assignments', $roleassign);
            }
        }

        return $user;
    }

    public function get_new_course($groupmode = 0) {
        $course = new stdClass;
        $course->id = 1;
        $course->category = $this->category->id;
        $course->fullname = 'Anonymous test course';
        $course->shortname = 'ANON';
        $course->summary = '';
        $course->modinfo = null;
        $course->groupmode = $groupmode;
        $course->id = $this->testdb->insert_record('course', $course);
        return $course;
    }

    public function get_new_ouwiki($courseid, $subwikis = 0) {
        $ouwiki = new stdClass();
        $ouwiki->course = $courseid;
        $ouwiki->name = 'Test ouwiki';
        $ouwiki->subwikis = $subwikis;
        $ouwiki->timeout = null;
        $ouwiki->template = null;
        $ouwiki->editbegin = null;
        $ouwiki->editend = null;
        $ouwiki->completionpages = 0;
        $ouwiki->completionedits = 0;
        $ouwiki->annotation = 0;
        $ouwiki->introformat = 0;
        $ouwiki->wordcount = 1;
        $ouwiki->grade = 100;

        $ouwiki->id = $this->testdb->insert_record('ouwiki', $ouwiki);
        return $ouwiki;
    }

    public function save_new_version($pageid, $xhtml, $userid, $wordcount) {
        $version = new stdClass;
        $version->pageid = $pageid;
        $version->xhtml = $xhtml;
        $version->xhtmlformat = 1;
        $version->timecreated = time();
        $version->userid = $userid;
        $version->wordcount = $wordcount;
        $version->id = $this->testdb->insert_record('ouwiki_versions', $version);
        $this->versions[] = $version;
    }

    public function get_new_course_module($courseid, $ouwikiid, $groupmode = 0) {
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
