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
 * Shared initialisation from wiki PHP pages.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

$id = required_param('id', PARAM_INT);           // Course Module ID that defines wiki
$pagename = optional_param('page', null, PARAM_RAW);    // Which page to show. Omitted for start page
$groupid = optional_param('group', 0, PARAM_INT); // Group ID. If omitted, uses first appropriate group
$userid  = optional_param('user', 0, PARAM_INT);   // User ID (for individual wikis). If omitted, uses own

// Special logic to handle legacy code that gets pagename wrong
if ($pagename === '') {
    debugging('Please try to make code omit page parameter when it is null', DEBUG_DEVELOPER);
}
if (is_null($pagename)) {
    $pagename = '';
}

// Restrict page name
if (core_text::strlen($pagename) > 200) {
    print_error('pagenametoolong', 'ouwiki');
}
// Convert nbsp to space
$pagename = str_replace(html_entity_decode('&nbsp;', ENT_QUOTES, 'UTF-8'), ' ', $pagename);
$pagename = trim($pagename);
if (strtolower($pagename) == strtolower(get_string('startpage', 'ouwiki'))) {
    print_error('pagenameisstartpage', 'ouwiki');
}

// Load efficiently (and with full $cm data) using get_fast_modinfo
$course = $DB->get_record_select('course',
        'id = (SELECT course FROM {course_modules} WHERE id = ?)', array($id),
        '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$cm = $modinfo->get_cm($id);
if ($cm->modname !== 'ouwiki') {
    print_error('invalidcoursemodule');
}

$ouwiki = $DB->get_record('ouwiki', array('id' => $cm->instance));
if (!$ouwiki) {
    print_error("Wiki ID is incorrect in database");
}
$context = context_module::instance($cm->id);

global $ouwiki_nologin;
if (empty($ouwiki_nologin)) {
    // Make sure they're logged in and check they have permission to view
    require_course_login($course, true, $cm);
    require_capability('mod/ouwiki:view', $context);
}

// Get subwiki, creating it if necessary
$subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $userid, true);
