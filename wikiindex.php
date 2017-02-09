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
 * 'Wiki index' page. Displays an index of all pages in the wiki, in
 * various formats.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

raise_memory_limit(MEMORY_EXTRA);

$treemode = optional_param('type', '', PARAM_ALPHA) == 'tree';
$id = required_param('id', PARAM_INT); // Course Module ID

$url = new moodle_url('/mod/ouwiki/wikiindex.php', array('id'=>$id));
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

// Get basic wiki parameters
$wikiparams = ouwiki_display_wiki_parameters('', $subwiki, $cm);

// Do header
$ouwikioutput->set_export_button('subwiki', $subwiki->id, $course->id, !empty($treemode) ? 1 : 0);
echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, get_string('index', 'ouwiki'), $context, null, false);

// Print tabs for selecting index type
$tabrow = array();
$tabrow[] = new tabobject('alpha', 'wikiindex.php?'.$wikiparams,
    get_string('tab_index_alpha', 'ouwiki'));
$tabrow[] = new tabobject('tree', 'wikiindex.php?'.$wikiparams.'&amp;type=tree',
    get_string('tab_index_tree', 'ouwiki'));
$tabs = array();
$tabs[] = $tabrow;
print_tabs($tabs, $treemode ? 'tree' : 'alpha');
print '<div id="ouwiki_belowtabs">';

global $orphans;
// Get actual index
$index = ouwiki_get_subwiki_index($subwiki->id);

$orphans = false;
$func = 'ouwiki_display_wikiindex_page_in_index';
if (count($index) == 0) {
    print '<p>'.get_string('startpagedoesnotexist', 'ouwiki').'</p>';
} else if ($treemode) {
    ouwiki_build_tree($index);
    // Print out in hierarchical form...
    print '<ul class="ouw_indextree">';
    print ouwiki_tree_index($func, reset($index)->pageid, $index, $subwiki, $cm);
    print '</ul>';
    foreach ($index as $indexitem) {
        if (count($indexitem->linksfrom) == 0 && $indexitem->title !== '') {
            $orphans = true;
            break;
        }
    }
} else {
    // ...or standard alphabetical
    print '<ul class="ouw_index">';
    foreach ($index as $indexitem) {
        if (count($indexitem->linksfrom)!= 0 || $indexitem->title === '') {
            print '<li>' . ouwiki_display_wikiindex_page_in_index($indexitem, $subwiki, $cm) . '</li>';
        } else {
            $orphans = true;
        }
    }
    print '</ul>';
}

if ($orphans) {
    print '<h2 class="ouw_orphans">'.get_string('orphanpages', 'ouwiki').'</h2>';
    print '<ul class="ouw_index">';
    foreach ($index as $indexitem) {
        if (count($indexitem->linksfrom) == 0 && $indexitem->title !== '') {
            if ($treemode) {
                $orphanindex = ouwiki_get_sub_tree_from_index($indexitem->pageid, $index);
                ouwiki_build_tree($orphanindex);
                print ouwiki_tree_index($func, $indexitem->pageid, $orphanindex, $subwiki, $cm);
            } else {
                print '<li>' . ouwiki_display_wikiindex_page_in_index($indexitem, $subwiki, $cm) . '</li>';
            }
        }
    }
    print '</ul>';
}

$missing = ouwiki_get_subwiki_missingpages($subwiki->id);
if (count($missing) > 0) {
    print '<div class="ouw_missingpages"><h2>'.get_string('missingpages', 'ouwiki').'</h2>';
    print '<p>'.get_string(count($missing) > 1 ? 'advice_missingpages' : 'advice_missingpage', 'ouwiki').'</p>';
    print '<ul>';
    $first = true;
    foreach ($missing as $title => $from) {
        print '<li>';
        if ($first) {
            $first = false;
        } else {
            print ' &#8226; ';
        }
        print '<a href="view.php?'.ouwiki_display_wiki_parameters($title, $subwiki, $cm).'">'.
            htmlspecialchars($title).'</a> <span class="ouw_missingfrom">('.
            get_string(count($from) > 1 ? 'frompages' : 'frompage', 'ouwiki',
                '<a href="view.php?'.ouwiki_display_wiki_parameters($from[0], $subwiki, $cm).'">'.
                ($from[0] ? htmlspecialchars($from[0]) : get_string('startpage', 'ouwiki')).'</a>)</span>');
        print '</li>';
    }
    print '</ul>';
    print '</div>';
}

$tree = 0;
if (!empty($treemode)) {
    $wikiparams.= '&amp;type=tree';
    $tree = 1;
}

if (count($index) != 0) {
    print '<div class="ouw_entirewiki"><h2>'.get_string('entirewiki', 'ouwiki').'</h2>';
    print '<p>'.get_string('onepageview', 'ouwiki').'</p><ul>';
    print '<li id="ouwiki_down_html"><a href="entirewiki.php?'.$wikiparams.'&amp;format=html">'.
        get_string('format_html', 'ouwiki').'</a></li>';

    // Are there any files in this wiki?
    $context = context_module::instance($cm->id);
    $result = $DB->get_records_sql("
SELECT
    f.id
FROM
    {ouwiki_subwikis} sw
    JOIN {ouwiki_pages} p ON p.subwikiid = sw.id
    JOIN {ouwiki_versions} v ON v.pageid = p.id
    JOIN {files} f ON f.itemid = v.id
WHERE
    sw.id = ? AND f.contextid = ? AND f.component = 'mod_ouwiki' AND f.filename NOT LIKE '.'
    AND f.filearea = 'attachment' AND v.id IN (SELECT MAX(v.id) from {ouwiki_versions} v WHERE v.pageid = p.id)
    ", array($subwiki->id, $context->id), 0, 1);
    $anyfiles = count($result) > 0;
    $wikiparamsarray = array('subwikiid' => $subwiki->id, 'tree' => $tree);
    print $ouwikioutput->render_export_all_li($subwiki, $anyfiles, $wikiparamsarray);

    if (has_capability('moodle/course:manageactivities', $context)) {
        $str = get_string('format_template', 'ouwiki');
        $filesexist = false;
        if ($anyfiles) {
            // Images or attachment files found.
            $filesexist = true;
        }

        print '<li id="ouwiki_down_template"><a href="entirewiki.php?' . $wikiparams . '&amp;format=template&amp;filesexist='
            .$filesexist.'">' . $str . '</a></li>';
    }
    print '</ul></div>';
}

$pageversion = ouwiki_get_current_page($subwiki, $pagename);
echo $ouwikioutput->get_link_back_to_wiki($cm);
echo $ouwikioutput->get_bottom_buttons($subwiki, $cm, $context, $pageversion, false);

// Footer
ouwiki_print_footer($course, $cm, $subwiki, $pagename);
