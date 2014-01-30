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
 * Diff. Displays the difference between two versions of a wiki page.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$id = required_param('id', PARAM_INT);
$v1 = required_param('v1', PARAM_INT);
$v2 = required_param('v2', PARAM_INT);
$pagename = optional_param('page', '', PARAM_TEXT);

$url = new moodle_url('/mod/ouwiki/diff.php', array('id' => $id, 'v1' => $v1, 'v2' => $v2, 'page' => $pagename));
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

$candelete = has_capability('mod/ouwiki:deletepage', $context);

// Get the current page [and current version, which we ignore]
$pageversion1 = ouwiki_get_page_version($subwiki, $pagename, $v1);
$pageversion2 = ouwiki_get_page_version($subwiki, $pagename, $v2);
if (!$pageversion1 || !$pageversion2 ||
   ((!empty($pageversion1->deletedat) || !empty($pageversion2->deletedat)) && !$candelete)) {
    print_error('Specified version does not exist');
}
if ($pageversion1 >= $pageversion2) {
    print_error('Versions out of order');
}

// Print header
$tabhistparams = ouwiki_shared_url_params($pagename, $subwiki, $cm);
echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context,
    array(
        array('name' => get_string('tab_history', 'ouwiki'), 'link' => new moodle_url('/mod/ouwiki/history.php', $tabhistparams)),
        array('name' => get_string('changesnav', 'ouwiki'), 'link' => null)),
    true, true
);

// Obtain difference between two versions
list($diff1, $diff2, $numchanges) = ouwiki_diff_html($pageversion1->xhtml, $pageversion2->xhtml);

$fs = get_file_storage();
$files1 = ($files1 = $fs->get_area_files($context->id, 'mod_ouwiki', 'attachment', $pageversion1->versionid, "timemodified", false)) ? $files1 : null;
$files2 = ($files2 = $fs->get_area_files($context->id, 'mod_ouwiki', 'attachment', $pageversion2->versionid, "timemodified", false)) ? $files2 : null;

list($attachdiff1, $attachdiff2, $attachnumchanges) = ouwiki_diff_attachments($files1, $files2, $context->id,
    $pageversion1->versionid, $pageversion2->versionid);

$numchanges = $numchanges + $attachnumchanges;
// if there are no changes then check if there are any annotations in the new version
if ($numchanges == 0) {
    $annotations = ouwiki_get_annotations($pageversion2);
    if (count($annotations) === 0) {
        $advice = get_string('diff_nochanges', 'ouwiki');
    } else {
        $advice = get_string('diff_someannotations', 'ouwiki');
    }
} else {
    $advice = get_string('advice_diff', 'ouwiki');
}

print get_accesshide(get_string('changedifferences', 'ouwiki'), 'h1');
print '<p class="ouw_advice">'.
     $advice.' '.
     get_string('returntohistory', 'ouwiki',
    'history.php?'.ouwiki_display_wiki_parameters($pagename, $subwiki, $cm)).'</p>';

// Obtain pluginfile urls.
$pageversion1->xhtml = file_rewrite_pluginfile_urls($pageversion1->xhtml, 'pluginfile.php',
    $context->id, 'mod_ouwiki', 'content', $pageversion1->versionid);
$pageversion2->xhtml = file_rewrite_pluginfile_urls($pageversion2->xhtml, 'pluginfile.php',
    $context->id, 'mod_ouwiki', 'content', $pageversion2->versionid);

// Obtain difference between two versions
list($diff1, $diff2) = ouwiki_diff_html($pageversion1->xhtml, $pageversion2->xhtml);

// To make it look like a user object
$pageversion1->id = $pageversion1->userid;
$v1name = ouwiki_display_user($pageversion1, $course->id);
$pageversion2->id = $pageversion2->userid;
$v2name = ouwiki_display_user($pageversion2, $course->id);

// Disply the two versions
$v1 = new StdClass;
$v1->version = get_string('olderversion', 'ouwiki');
$v1->date = userdate($pageversion1->timecreated);
$v1->savedby = get_string('savedby', 'ouwiki', $v1name);
$v1->content = $diff1;
$v1->attachments = $attachdiff1;

$v2 = new StdClass;
$v2->version = get_string('newerversion', 'ouwiki');
$v2->date = userdate($pageversion2->timecreated);
$v2->savedby = get_string('savedby', 'ouwiki', $v2name);
$v2->content = $diff2;
$v2->attachments = $attachdiff2;

echo $ouwikioutput->ouwiki_print_diff($v1, $v2);

// Footer
echo '<div>';
ouwiki_print_footer($course, $cm, $subwiki, $pagename);
