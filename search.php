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
 * View page. Displays wiki pages.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/local/ousearch/searchlib.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$querytext = required_param('query', PARAM_RAW);

$url = new moodle_url('/mod/ouwiki/search.php', array('id'=>$id, 'user'=>$userid, 'query'=>$querytext));
$PAGE->set_url($url);
$ouwikioutput = $PAGE->get_renderer('mod_ouwiki');
echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, get_string('searchresults'), $context, null, null, null, '', '', $querytext);
echo html_writer::start_div();
$query = new local_ousearch_search($querytext);
$query->set_coursemodule($cm);
if ($subwiki->groupid) {
    $query->set_group_id($subwiki->groupid);
}
if ($subwiki->userid) {
    $query->set_user_id($subwiki->userid);
}

$foundsomething = $query->display_results('search.php?'.ouwiki_display_wiki_parameters('', $subwiki, $cm));

echo $foundsomething;

// Add link to search the rest of this website if service available.
if (!empty($CFG->block_resources_search_baseurl)) {
    $params = array('course' => $course->id, 'query' => $querytext);
    $restofwebsiteurl = new moodle_url('/blocks/resources_search/search.php', $params);
    $strrestofwebsite = get_string('restofwebsite', 'local_ousearch');
    $altlink = html_writer::start_tag('div', array('class' => 'advanced-search-link'));
    $altlink .= html_writer::link($restofwebsiteurl, $strrestofwebsite);
    $altlink .= html_writer::end_tag('div');
    print $altlink;
}

// Footer
ouwiki_print_footer($course, $cm, $subwiki, null, 'search.php?query='.urlencode($querytext), $foundsomething ? null : 'searchfailure', $querytext);
