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
 * Confirms reverting to previous version
 * when confirmed, reverts to previous version then redirects back to that page.
 * @copyright &copy; 2008 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$id = required_param('id', PARAM_INT);
$versionid = required_param('version', PARAM_INT);
$confirmed = optional_param('confirm', null, PARAM_TEXT);
$cancelled = optional_param('cancel', null, PARAM_TEXT);

$url = new moodle_url('/mod/ouwiki/view.php', array('id' => $id, 'page' => $pagename));
$PAGE->set_url($url);

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
$PAGE->set_pagelayout('incourse');
require_course_login($course, true, $cm);
$ouwikioutput = $PAGE->get_renderer('mod_ouwiki');

// Get the page version to be reverted back to (must not be deleted page version)
$pageversion = ouwiki_get_page_version($subwiki, $pagename, $versionid);
if (!$pageversion || !empty($pageversion->deletedat)) {
    print_error('reverterrorversion', 'ouwiki');
}

// Check for cancel
if (isset($cancelled)) {
    redirect('history.php?'.ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL));
    exit;
}

// Check permission - Allow anyone with edit capability to revert to a previous version
$canrevert = has_capability('mod/ouwiki:edit', $context);
if (!$canrevert) {
    print_error('reverterrorcapability', 'ouwiki');
}

// Check if reverting to previous version has been confirmed
if ($confirmed) {

    // Lock something - but maybe this should be the current version
    list($lockok, $lock) = ouwiki_obtain_lock($ouwiki, $pageversion->pageid);

    // Revert to previous version
    ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pagename, $pageversion->xhtml, -1, -1, -1, null, null, $pageversion->versionid);

    // Unlock whatever we locked
    ouwiki_release_lock($pageversion->pageid);

    // Redirect to view what is now the current version
    redirect('view.php?'.ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL));
    exit;

} else {
    // Display confirm form
    $nav = get_string('revertversion', 'ouwiki');
    echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context, array(array('name' => $nav, 'link' => null)), true, true);

    $date = ouwiki_nice_date($pageversion->timecreated);
    print get_string('revertversionconfirm', 'ouwiki', $date);
    print '<form action="revert.php" method="post">';
    print ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_FORM);
    print
        '<input type="hidden" name="version" value="'.$versionid.'" />'.
        '<input type="submit" name="confirm" value="'.get_string('revertversion', 'ouwiki').'"/> '.
        '<input type="submit" name="cancel" value="'.get_string('cancel').'"/>';
    print '</form>';

    // Footer
    ouwiki_print_footer($course, $cm, $subwiki, $pagename);
}
