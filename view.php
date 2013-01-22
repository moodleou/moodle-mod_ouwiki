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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * View page. Displays wiki pages.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/ajax/ajaxlib.php');

require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$url = new moodle_url('/mod/ouwiki/view.php', array('id' => $id, 'page' => $pagename));
$PAGE->set_url($url);
$PAGE->set_cm($cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$PAGE->set_pagelayout('incourse');
require_course_login($course, true, $cm);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$ouwikioutput = $PAGE->get_renderer('mod_ouwiki');

echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context);

// Check consistency in setting subwikis and group mode
$courselink = new moodle_url('/course/view.php?id=', array('id' =>  $cm->course));
if (($cm->groupmode == 0) && isset($subwiki->groupid)) {
    print_error("Sub-wikis is set to 'One wiki per group'.
        Please change Group mode to 'Separate groups' or 'Visible groups'.", $courselink);
}
if (($cm->groupmode > 0) && !isset($subwiki->groupid)) {
    print_error("Sub-wikis is NOT set to 'One wiki per group'.
        Please change Group mode to 'No groups'.", $courselink);
}

// Get the current page version
$pageversion = ouwiki_get_current_page($subwiki, $pagename);
$locked = ($pageversion) ? $pageversion->locked : false;

ouwiki_print_tabs('view', $pagename, $subwiki, $cm, $context, $pageversion ? true : false, $locked);

if (($pagename === '' || $pagename === null) && strlen(preg_replace('/\s|<br\s*\/?>|<p>|<\/p>/',
        '', $ouwiki->intro)) > 0) {
    $intro = format_text($ouwiki->intro);
    $intro = file_rewrite_pluginfile_urls($intro, 'pluginfile.php', $context->id,
            'mod_ouwiki', 'intro', null);
    print '<div class="ouw_intro">' . $intro . '</div>';
}

if ($pageversion) {
    // Print warning if page is large (more than 75KB)
    if (strlen($pageversion->xhtml) > 75 * 1024) {
        print '<div class="ouwiki-sizewarning"><img src="' . $OUTPUT->pix_url('warning', 'ouwiki') .
                '" alt="" />' . get_string('sizewarning', 'ouwiki') .
                '</div>';
    }
    // Print page content
    $hideannotations = get_user_preferences(OUWIKI_PREF_HIDEANNOTATIONS, 0);
    $data = $ouwikioutput->ouwiki_print_page($subwiki, $cm, $pageversion, true, 'view',
            $ouwiki->enablewordcount, (bool)$hideannotations);
    echo $data[0];
    if ($subwiki->canedit && $pageversion->locked != '1') {
        print ouwiki_display_create_page_form($subwiki, $cm, $pageversion);
    }
    if (has_capability('mod/ouwiki:lock', $context)) {
        print ouwiki_display_lock_page_form($pageversion, $id);
    }
} else {
    // Page does not exist
    print '<p>'.get_string($pagename ? 'pagedoesnotexist' : 'startpagedoesnotexist', 'ouwiki').'</p>';
    if ($subwiki->canedit) {
        print '<p>'.get_string('wouldyouliketocreate', 'ouwiki').'</p>';
        print "<form method='get' action='edit.php'>";
        print ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_FORM);
        print "<input type='submit' value='".get_string('createpage', 'ouwiki')."' /></form>";
    }
}

if ($timelocked = ouwiki_timelocked($subwiki, $ouwiki, $context)) {
    print '<div class="ouw_timelocked">'.$timelocked.'</div>';
}

// init JS module
if (ajaxenabled()) {
    $PAGE->requires->yui2_lib('event');
    $PAGE->requires->yui2_lib('connection');
    $PAGE->requires->yui2_lib('dom');
    $PAGE->requires->yui2_lib('annimation');

    $stringlist[] = array('typeinsectionname', 'ouwiki');
    $stringlist[] = array('typeinpagename', 'ouwiki');
    $stringlist[] = array('collapseannotation', 'ouwiki');
    $stringlist[] = array('expandannotation', 'ouwiki');
    $module = array(
            'name'      => 'mod_ouwiki',
            'fullpath'  => '/mod/ouwiki/view.js',
            'requires'  => array('base'),
            'strings'   => $stringlist
    );
    $PAGE->requires->js_init_call('M.mod_ouwiki.init', array(), false, $module);
}

// Footer
ouwiki_print_footer($course, $cm, $subwiki, $pagename);
