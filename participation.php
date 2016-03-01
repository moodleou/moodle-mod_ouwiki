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
 * Page for viewing all user participation
 *
 * @package mod
 * @subpackage ouwiki
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');
require_once($CFG->libdir.'/gradelib.php');

$id         = required_param('id', PARAM_INT); // Course Module ID
$groupid    = optional_param('group', 0, PARAM_INT);
$pagename   = optional_param('pagename', '', PARAM_TEXT);
$download   = optional_param('download', '', PARAM_TEXT);
$page       = optional_param('page', 0, PARAM_INT); // flexible_table page

$params = array(
    'id'        => $id,
    'group'     => $groupid,
    'pagename'  => $pagename,
    'download'  => $download,
    'page'      => $page,
);
$url = new moodle_url('/mod/ouwiki/participation.php', $params);
$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('ouwiki', $id)) {
    print_error('invalidcoursemodule');
}

// Checking course instance
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

if (!$ouwiki = $DB->get_record('ouwiki', array('id' => $cm->instance))) {
    print_error('invalidcoursemodule');
}

$PAGE->set_cm($cm);
$context = context_module::instance($cm->id);
$PAGE->set_pagelayout('incourse');
require_course_login($course, true, $cm);

// participation capability check
$canview = ouwiki_can_view_participation($course, $ouwiki, $subwiki, $cm);
if ($canview != OUWIKI_USER_PARTICIPATION) {
    print_error('nopermissiontoshow');
}
$viewfullnames = has_capability('moodle/site:viewfullnames', $context);

$groupname = '';
if ($groupid) {
    $groupname = $DB->get_field('groups', 'name', array('id' => $groupid));
}

// all enrolled users for table pagination
$coursecontext = context_course::instance($course->id);
$participation = ouwiki_get_participation($ouwiki, $subwiki, $context, $groupid);

// is grading enabled and available for the current user
$grading_info = array();
if ($ouwiki->grade != 0 && has_capability('mod/ouwiki:grade', $context) &&
        (!$groupid || ($groupid && has_capability('moodle/site:accessallgroups', $context)
                || ($groupid && groups_is_member($groupid))))) {
    $grading_info = grade_get_grades($course->id, 'mod',
        'ouwiki', $ouwiki->id, array_keys($participation));
}

$ouwikioutput = $PAGE->get_renderer('mod_ouwiki');

// Headers
if (empty($download)) {
    echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, get_string('userparticipation', 'ouwiki'), $context);

    // gets a message after grades updated
    if (isset($SESSION->ouwikigradesupdated)) {
        $message = $SESSION->ouwikigradesupdated;
        unset($SESSION->ouwikigradesupdated);
        echo $OUTPUT->notification($message, 'notifysuccess');
    }
}

$ouwikioutput->ouwiki_render_participation_list($cm, $course, $pagename, $groupid, $ouwiki,
    $subwiki, $download, $page, $grading_info, $participation, $coursecontext, $viewfullnames,
    $groupname);

// Footer
if (empty($download)) {
    $pageversion = ouwiki_get_current_page($subwiki, $pagename);
    echo $ouwikioutput->get_link_back_to_wiki($cm);
    echo $ouwikioutput->get_bottom_buttons($subwiki, $cm, $context, $pageversion, false);
    ouwiki_print_footer($course, $cm, $subwiki, $pagename, null, 'view');
}
