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
 * Save template feature. Saves entire subwiki contents as an XML template.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$id = required_param('id', PARAM_INT); // Course Module ID
$pagename = optional_param('page', '', PARAM_TEXT);
$filesexist = optional_param('filesexist', 0, PARAM_INT);

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
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$PAGE->set_pagelayout('incourse');
require_course_login($course, true, $cm);

$ouwikioutput = $PAGE->get_renderer('mod_ouwiki');

$format = required_param('format', PARAM_ALPHA);
if ($format !== OUWIKI_FORMAT_HTML && $format !== OUWIKI_FORMAT_RTF && $format !== OUWIKI_FORMAT_TEMPLATE) {
    print_error('Unexpected format');
}

// Get basic wiki details for filename
$filename = $course->shortname.'.'.$ouwiki->name;
$filename = preg_replace('/[^A-Za-z0-9.-]/','_', $filename);

switch ($format) {
    case OUWIKI_FORMAT_TEMPLATE:
        header('Content-Type: text/xml; encoding=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'.template.xml"');
        print '<wiki>';
        break;
    case OUWIKI_FORMAT_RTF:
        require_once($CFG->dirroot.'/local/rtf.php');
        $html = '<root><p>'.get_string('savedat', 'ouwiki', userdate(time())).'</p><hr />';
        break;
    case OUWIKI_FORMAT_HTML:
        // Do header
        echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, get_string('entirewiki', 'ouwiki'), $context, null, false, true);
        print '<div class="ouwiki_content">';
        break;
}

// Get list of all pages
$first = true;
$index = ouwiki_get_subwiki_index($subwiki->id);

// Set up remove any links to files variables in xhtml.
$pattern = '#<img(.*?)src="'. $CFG->wwwroot .'/pluginfile.php(.*?)/>#';
$brokenimagestr = get_string('brokenimage', 'ouwiki');

foreach ($index as $pageinfo) {
    // Get page details
    $pageversion = ouwiki_get_current_page($subwiki, $pageinfo->title);
    // If the page hasn't really been created yet, skip it
    if (is_null($pageversion->xhtml)) {
        continue;
    }
    $visibletitle = $pageversion->title === '' ? get_string('startpage', 'ouwiki') : $pageversion->title;

    $pageversion->xhtml = file_rewrite_pluginfile_urls($pageversion->xhtml, 'pluginfile.php',
            $context->id, 'mod_ouwiki', 'content', $pageversion->versionid);

    switch ($format) {
        case OUWIKI_FORMAT_TEMPLATE:
            // Remove any links to files in the xhtml.
            if ($filesexist) {
                $pageversion->xhtml = preg_replace($pattern, $brokenimagestr, $pageversion->xhtml);
            }
            // Print template wiki page.
            print '<page>';
            if ($pageversion->title !== '') {
                print '<title>'.htmlspecialchars($pageversion->title).'</title>';
            }
            print '<xhtml>'.htmlspecialchars($pageversion->xhtml).'</xhtml>';
            print '</page>';
            break;
        case OUWIKI_FORMAT_RTF:
            $html .= '<h1>'.htmlspecialchars($visibletitle).'</h1>';
            $html .= trim($pageversion->xhtml);
            $html .= '<br /><br /><hr />';
            break;
        case OUWIKI_FORMAT_HTML:
            print '<div class="ouw_entry"><a name="'.$pageversion->pageid.'"></a><h1 class="ouw_entry_heading"><a href="view.php?'.
                ouwiki_display_wiki_parameters($pageversion->title, $subwiki, $cm).
                '">'.htmlspecialchars($visibletitle).'</a></h1>';
            print ouwiki_convert_content($pageversion->xhtml, $subwiki, $cm, $index, $pageversion->xhtmlformat);
            print '</div>';
            break;
    }

    if ($first) {
        $first = false;
    }
}

switch ($format) {
    case OUWIKI_FORMAT_TEMPLATE:
        print '</wiki>';
        break;

    case OUWIKI_FORMAT_RTF:
        $html .= '</root>';
        rtf_from_html($filename.'.rtf', $html);
        break;

    case OUWIKI_FORMAT_HTML:
        print '</div>';
        ouwiki_print_footer($course, $cm, $subwiki);
        break;
}
