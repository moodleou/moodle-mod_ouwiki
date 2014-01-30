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
 * Page for saving grades for all or one user participation
 *
 * @package mod
 * @subpackage ouwiki
 * @copyright 2011 The Open University
 * @author Stacey Walker <stacey@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

$id         = required_param('id', PARAM_INT); // Course Module ID
$groupid    = optional_param('group', 0, PARAM_INT);
$userid     = optional_param('user', 0, PARAM_INT);
$page       = optional_param('page', '', PARAM_TEXT);

$params = array();
$params['id'] = $id;
$params['group'] = $groupid;
$params['page'] = $page;
$url = new moodle_url('/mod/ouwiki/savegrades.php');
if ($id) {
    if (!$cm = get_coursemodule_from_id('ouwiki', $id)) {
        print_error('invalidcoursemodule');
    }

    // Checking course instance
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

    if (!$ouwiki = $DB->get_record('ouwiki', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }

    $PAGE->set_cm($cm);
}
$context = context_module::instance($cm->id);
require_course_login($course, true, $cm);

$mode = '';
if (!empty($_POST['menu'])) {
    $mode = 'bulk';
    $gradeinfo = $_POST['menu'];
} else if ($userid && !empty($_POST['grade'])) {
    $gradeinfo[$userid] = $_POST['grade'];
}
// update grades
if (!empty($gradeinfo)) {
    ouwiki_update_user_grades($gradeinfo, $cm, $ouwiki, $course);
}

// redirect
redirect('participation.php?id=' . $id . '&pagename=' . $page . '&group=' . $groupid);
