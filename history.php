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
 * History page. Shows list of all previous versions of a page.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$id = required_param('id', PARAM_INT); // Course Module ID
$compare = optional_param('compare', 0, PARAM_INT);
$pagename = optional_param('page', '', PARAM_TEXT);

$url = new moodle_url('/mod/ouwiki/history.php', array('id' => $id));
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

// Check if this is a compare request
if ($compare) {
    // OK, figure out the version numbers and redirect to diff.php (this
    // is done here just so diff.php doesn't have to worry about the manky
    // format)
    $versions = array();
    foreach ($_GET as $name => $value) {
        if (preg_match('/^v[0-9]+$/', $name)) {
            $versions[] = substr($name, 1);
        }
    }
    if (count($versions) != 2) {
        print_error(get_string('mustspecify2', 'ouwiki'));
    }
    sort($versions, SORT_NUMERIC);
    $wikiurlparams = html_entity_decode(ouwiki_display_wiki_parameters($pagename, $subwiki, $cm), ENT_QUOTES);
    redirect("diff.php?$wikiurlparams&v1={$versions[0]}&v2={$versions[1]}");
    exit;
}

// Get information about page
$pageversion = ouwiki_get_current_page($subwiki, $pagename, OUWIKI_GETPAGE_CREATE);
$wikiparams = ouwiki_display_wiki_parameters($pagename, $subwiki, $cm);
$wikiinputs = ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_FORM);

// Do header
$atomurl = $CFG->wwwroot.'/mod/ouwiki/feed-history.php?'.$wikiparams.
    '&amp;magic='.$subwiki->magic;
$rssurl = $CFG->wwwroot.'/mod/ouwiki/feed-history.php?'.$wikiparams.
    '&amp;magic='.$subwiki->magic.'&amp;format=rss';
$meta = '<link rel="alternate" type="application/atom+xml" title="Atom feed" '.
    'href="'.$atomurl.'" />';

$wikiname = format_string(htmlspecialchars($ouwiki->name));
$title = get_string('historyfor', 'ouwiki');
if ($pagename) {
    $title = $wikiname.' - '.$title.' : '.$pagename;
    $name = $pagename;
} else {
    $title = $wikiname.' - '.$title.' : '. get_string('startpage', 'ouwiki');
}

echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context, null, false, false, $meta, $title);

// Get history
$changes = ouwiki_get_page_history($pageversion->pageid, $candelete);
ouwiki_print_tabs('history', $pagename, $subwiki, $cm, $context, true, $pageversion->locked);

print_string('advice_history', 'ouwiki', "view.php?$wikiparams");

// Print message about deleted things being invisible to students so admins
// don't get confused
if ($candelete) {
    $found = false;
    foreach ($changes as $change) {
        if (!empty($change->deletedat)) {
            $found = true;
            break;
        }
    }
    if ($found) {
        print '<p class="ouw_deletedpageinfo">'.get_string('pagedeletedinfo', 'ouwiki').'</p>';
    }
}

// Check to see whether any change has been overwritten by being imported.
$overwritten = false;
foreach ($changes as $change) {
    if (!empty($change->importversionid)) {
        $overwritten = true;
        break;
    }
}

print "
<form name='ouw_history' class='ouw_history' method='get' action='history.php'>
<input type='hidden' name='compare' value='1'/>
$wikiinputs
<table class='generaltable'>
<thead>
<tr><th scope='col'>".get_string('date')."</th><th scope='col'>".get_string('time')."</th><th><span class='accesshide'>".get_string('actionheading', 'ouwiki')."</span>
</th>";
if ($ouwiki->enablewordcount) {
    print "<th scope='col'>".get_string('words', 'ouwiki')."</th>";
}
if ($overwritten) {
    print '<th scope="col">'.get_string('importedfrom', 'ouwiki').'</th>';
}
print "<th scope='col'>".get_string('changedby', 'ouwiki')."</th><th scope='col'><span class='accesshide'>".get_string('compare', 'ouwiki')."</span></th>";
print '</thead></tr><tbody>';

$lastdate = '';
$changeindex = 0;
$changeids = array_keys($changes);
foreach ($changes as $change) {
    $date = userdate($change->timecreated, get_string('strftimedate'));
    if ($date == $lastdate) {
        $date = '';
    } else {
        $lastdate = $date;
    }
    $time = ouwiki_recent_span($change->timecreated).userdate($change->timecreated, get_string('strftimetime')).'</span>';

    $createdtime = userdate($change->timecreated, get_string('strftimetime'));
    $nextchange = false;
    if ($changeindex + 1 < count($changes)) {
        $nextchange = $changes[$changeids[$changeindex + 1]];
    }

    if ($nextchange) {
        $changelink = " <small>(<a href='diff.php?$wikiparams&amp;v2={$change->versionid}&amp;v1={$nextchange->versionid}'>".
                get_string('changes', 'ouwiki')."<span class=\"accesshide\"> $lastdate $createdtime</span></a>)</small>";
    } else {
        $changelink = '';
    }
    $revertlink = '';
    if ($change->versionid == $pageversion->versionid) {
        $viewlink = "view.php?$wikiparams";
    } else {
        $viewlink = "viewold.php?$wikiparams&amp;version={$change->versionid}";
        if ($subwiki->canedit && !$pageversion->locked) {
            $revertlink = " <a href=revert.php?$wikiparams&amp;version={$change->versionid}>".get_string('revert')."</a>";
        }
    }

    // set delete link as appropriate
    $deletedclass = '';
    $deletedstr = '';
    $deletelink = '';
    if ($candelete) {
        $strdelete = get_string('delete');
        $strdeleted = get_string('deleted');
        if (!empty($change->deletedat)) {
            $revertlink = '';
            $deletedclass = " class='ouw_deletedrow'";
            $strdelete = get_string('undelete', 'ouwiki');
            $deletedstr = "<span class='ouw_deleted'>$strdeleted</span>";
        }
        $deletelink = " <a href=delete.php?$wikiparams&amp;version={$change->versionid}>$strdelete</a>";
    }

    if ($change->id) {
        $userlink = ouwiki_display_user($change, $course->id);
    } else {
        $userlink = '';
    }

    $a = new StdClass;
    $a->lastdate = $lastdate;
    $a->createdtime = $createdtime;

    $selectaccessibility = get_string('historycompareaccessibility', 'ouwiki', $a);

    print "
    <tr$deletedclass>
      <td class='ouw_leftcol'>$date</td><td>$time $deletedstr</td>
      <td class='actions'><a href='$viewlink'>".get_string('view')."</a>$deletelink$revertlink$changelink</td>";
    if ($ouwiki->enablewordcount) {
        if ($change->previouswordcount) {
            $wordcountchanges = ouwiki_wordcount_difference($change->wordcount, $change->previouswordcount, true);
        } else {
            $wordcountchanges = ouwiki_wordcount_difference($change->wordcount, 0, false);
        }
        print "<td>$wordcountchanges</td>";
    }

    if ($overwritten) {
        if (!empty($change->importversionid)) {
            $selectedouwiki = ouwiki_get_wiki_details($change->importversionid);
            print '<td>';
            if ($selectedouwiki->courseshortname) {
                print $selectedouwiki->courseshortname. '<br/>';
            }
            print $selectedouwiki->name;
            if ($selectedouwiki->group) {
                print '<br/>';
                print '[[' .$selectedouwiki->group. ']]';
            } else if ($selectedouwiki->user) {
                print '<br/>';
                print '[[' .$selectedouwiki->user. ']]';
            }
            print '</td>';
        } else {
            print '<td></td>';
        }
    }
    print "
      <td>$userlink</td>
      <td class='check ouw_rightcol'><label for='v{$change->versionid}' class=\"accesshide\"> $selectaccessibility </label>
      <input type='checkbox' name='v{$change->versionid}' id='v{$change->versionid}' onclick='ouw_check()' /></td>";
    print '</tr>';
    $changeindex++;
}

print "</tbody></table>";
$input = '<input id="ouw_comparebutton" type="submit" value="' .
        get_string('compareselected', 'ouwiki') . '" class="osep-smallbutton" />';
echo html_writer::div($input, 'ouw-comparebutton-wrapper');
print "</form>";

// The page works without JS. If you do have it, though, this script ensures
// you can't click compare without having two versions selected.
print '
<script type="text/javascript">
var comparebutton=document.getElementById("ouw_comparebutton");
comparebutton.disabled=true;

function ouw_check() {
    var elements=document.forms["ouw_history"].elements;
    var checked=0;
    for(var i=0;i<elements.length;i++) {
        if(/^v[0-9]+/.test(elements[i].name) && elements[i].checked) {
            checked++;
        }
    }
    comparebutton.disabled=checked!=2;
}

</script>
';

echo $ouwikioutput->ouwiki_get_feeds($atomurl, $rssurl);

$pageversion = ouwiki_get_current_page($subwiki, $pagename);
echo $ouwikioutput->get_bottom_buttons($subwiki, $cm, $context, $pageversion, false);

// Footer
ouwiki_print_footer($course, $cm, $subwiki, $pagename);
