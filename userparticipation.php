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
 * Page for viewing single user participation
 *
 * @package mod
 * @subpackage ouwiki
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$id         = required_param('id', PARAM_INT); // Course Module ID
// Pick up userid from either querytext or user - if not user cur user.
$querytext = optional_param('querytext', $USER->id, PARAM_INT);
$userid = optional_param('user', $querytext, PARAM_INT);
$groupid    = optional_param('group', 0, PARAM_INT);
$pagename   = optional_param('page', '', PARAM_TEXT);
$download   = optional_param('download', '', PARAM_TEXT);

$params = array(
    'id'        => $id,
    'user'      => $userid,
    'group'     => $groupid,
    'pagename'  => $pagename,
    'download'  => $download,
);
$url = new moodle_url('/mod/ouwiki/userparticipation.php', $params);
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

$canview = ouwiki_can_view_participation($course, $ouwiki, $subwiki, $cm, $USER->id);
if (($canview == OUWIKI_NO_PARTICIPATION)
    || ($USER->id != $userid && $canview == OUWIKI_MY_PARTICIPATION)) {
    print_error('nopermissiontoshow');
}

list($user, $changes) = ouwiki_get_user_participation($userid, $subwiki);

$fullname = fullname($user, has_capability('moodle/site:viewfullnames', $context));
$cangrade = has_capability('mod/ouwiki:grade', $context);

$groupname = '';
if ($groupid) {
    $groupname = $DB->get_field('groups', 'name', array('id' => $groupid));
    if ($cangrade && (!has_capability('moodle/site:accessallgroups', $context) &&
            !groups_is_member($groupid))) {
        // Only grade own group (unless access all groups).
        $cangrade = false;
    }
}

$ouwikioutput = $PAGE->get_renderer('mod_ouwiki');

// Headers
if (empty($download)) {
    $nav = array();
    $groupparams = ($groupid) ? '&group=' . $groupid : '';
    if ($canview == OUWIKI_USER_PARTICIPATION) {
        $nav[] = array(
            'name' => get_string('userparticipation', 'ouwiki'),
            'link' => "/mod/ouwiki/participation.php?id=$cm->id" .
            "&pagename=$pagename$groupparams"
        );
    }
    $nav[] = array('name' => $fullname, 'link' => null);
    echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course,
        $subwiki, null, $context, $nav, null, null, '', '', $userid);
}

echo $ouwikioutput->ouwiki_render_user_participation($user, $changes, $cm, $course, $ouwiki,
    $subwiki, $pagename, $groupid, $download, $canview, $context, $fullname,
    $cangrade, $groupname);

// Footer
if (empty($download)) {
    ouwiki_print_footer($course, $cm, $subwiki, $pagename, null, 'view');
}
