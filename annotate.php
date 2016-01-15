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
 * Annotate page. Allows user to add and edit wiki annotations.
 *
 * @copyright &copy; 2009 The Open University
 * @author b.j.waddington@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/lib/ajax/ajaxlib.php');
require_once($CFG->dirroot.'/mod/ouwiki/annotate_form.php');
require_once($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$save = optional_param('submitbutton', '', PARAM_TEXT);
$cancel = optional_param('cancel', '', PARAM_TEXT);
$deleteorphaned = optional_param('deleteorphaned', 0, PARAM_BOOL);
$lockunlock = optional_param('lockediting', false, PARAM_BOOL);

if (!empty($_POST) && !confirm_sesskey()) {
    print_error('invalidrequest');
}

$url = new moodle_url('/mod/ouwiki/annotate.php', array('id' => $id));
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

// Check permission
require_capability('mod/ouwiki:annotate', $context);
if (!$subwiki->annotation) {
    $redirect = 'view.php?'.ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL);
    print_error('You do not have permission to annotate this wiki page', 'error', $redirect);
}

// Get the current page version, creating page if needed
$pageversion = ouwiki_get_current_page($subwiki, $pagename, OUWIKI_GETPAGE_ACCEPTNOVERSION);
$wikiformfields = ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_FORM);

// For everything except cancel we need to obtain a lock.
if (!$cancel) {
    if (!$pageversion) {
        print_error(get_string('startpagedoesnotexist', 'ouwiki'));
    }
    // Get lock
    list($lockok, $lock) = ouwiki_obtain_lock($ouwiki, $pageversion->pageid);
}

// Handle save
if ($save) {
    if (!$lockok) {
        ouwiki_release_lock($pageversion->pageid);
        print_error('cannotlockpage', 'ouwiki', 'view.php?'.ouwiki_display_wiki_parameters($pagename,
                $subwiki, $cm, OUWIKI_PARAMS_URL));
    }

    // Format XHTML so it matches that sent to annotation marker creation code.
    /*$pageversion->xhtml = ouwiki_convert_content($pageversion->xhtml, $subwiki, $cm, null,
            $pageversion->xhtmlformat);*/

    $userid = !$userid ? $USER->id : $userid;
    $neednewversion = false;

    // get the form data
    $new_annotations = array();
    $edited_annotations = array();
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'edit') === 0) {
            $edited_annotations[substr($key, 4)] = optional_param($key, null, PARAM_TEXT);
        } else if (strpos($key, 'new') === 0) {
            $new_annotations[substr($key, 3)] = optional_param($key, null, PARAM_TEXT);
        }
    }

    $transaction = $DB->start_delegated_transaction();

    // get the existing annotations to check for changes
    $stored = ouwiki_get_annotations($pageversion);
    $updated = $stored;

    // do we need to delete orphaned annotations
    $deleted_annotations = array();
    if ($deleteorphaned) {
        foreach ($stored as $annotation) {
            if ($annotation->orphaned) {
                $DB->delete_records('ouwiki_annotations', array('id' => $annotation->id));
                $deleted_annotations[$annotation->id] = '';
            }
        }
    }

    foreach ($edited_annotations as $key => $value) {
        if ($value == '') {
            $DB->delete_records('ouwiki_annotations', array('id' => $key));
            $deleted_annotations[$key] = '';
        } else if ($value != $stored[$key]->content) {
            $dataobject = new stdClass();
            $dataobject->id = $key;
            $dataobject->pageid = $pageversion->pageid;
            $dataobject->userid = $USER->id;
            $dataobject->timemodified = time();
            $dataobject->content = $value;
            $DB->update_record('ouwiki_annotations', $dataobject);
        }
    }

    $updated = array_diff_key($updated, $deleted_annotations);

    // we need to work backwords through this lot in order to maintain charactor position
    krsort($new_annotations, SORT_NUMERIC);
    $prevkey = '';
    $spanlength = 0;
    foreach ($new_annotations as $key => $value) {
        if ($value != '') {
            $dataobject = new stdClass();
            $dataobject->pageid = $pageversion->pageid;
            $dataobject->userid = $USER->id;
            $dataobject->timemodified = time();
            $dataobject->content = $value;
            $newannoid = $DB->insert_record('ouwiki_annotations', $dataobject);
            $updated[$newannoid] = '';

            // we're still going so insert the new annotation into the xhtml
            $replace = '<span id="annotation'.$newannoid.'"></span>';
            $position = $key;
            if ($key == $prevkey) {
                $position = $key + $spanlength;
            } else {
                $position = $key;
            }

            $pageversion->xhtml = substr_replace($pageversion->xhtml, $replace, $position, 0);
            $neednewversion = true;
            $spanlength = strlen($replace);
            $prevkey = $key;
        }
    }

    // if we have got this far then commit the transaction, remove any unwanted spans
    // and save a new wiki version if required
    $neednewversion = (ouwiki_cleanup_annotation_tags($updated, $pageversion->xhtml)) ? true : $neednewversion;

    // Note: Because we didn't get data values from the form, they have not been
    // sanity-checked so we don't know if the field actually existed or not.
    // That means we need to do another lock capability check here in addition
    // to the one done when displaying the form.
    if (has_capability('mod/ouwiki:lock', $context)) {
        ouwiki_lock_editing($pageversion->pageid, $lockunlock);
    }

    if ($neednewversion) {
        if (strpos($pageversion->xhtml, '"view.php') !== false) {
            // Tidy up and revert converted content (links) back to original format.
            $pattern = '(<a\b[^>]*?href="view\.php[^>]*?>(.*?)<\/a>)';
            $pageversion->xhtml = preg_replace($pattern, "[[$1]]", $pageversion->xhtml);
        }
        if ($contenttag = strpos($pageversion->xhtml, '<div class="ouwiki_content">') !== false) {
            // Strip out content tag.
            $pageversion->xhtml = substr_replace($pageversion->xhtml, '', $contenttag, 28);
            $endtag = strrpos($pageversion->xhtml, '</div>');
            if ($endtag !== false) {
                $pageversion->xhtml = substr_replace($pageversion->xhtml, '', $endtag, 6);
            }
        }
        ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pagename, $pageversion->xhtml);
    }

    $transaction->allow_commit();
}

// Redirect for save or cancel
if ($save || $cancel) {
    ouwiki_release_lock($pageversion->pageid);
    redirect('view.php?'.ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL), '', 0);
}
// OK, not redirecting...

// Handle case where page is locked by someone else
if (!$lockok) {
    // Print header etc
    echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context);

    $lockholder = $DB->get_record('user', array('id' => $lock->userid));
    $pagelockedtitle = get_string('pagelockedtitle', 'ouwiki');
    $pagelockedtimeout = '';

    $details = new StdClass;
    $details->name = fullname($lockholder);
    $details->lockedat = ouwiki_nice_date($lock->lockedat);
    $details->seenat = ouwiki_nice_date($lock->seenat);

    if ($lock->seenat > time()) {
        // When the 'seen at' value is greater than current time, that means
        // their lock has been automatically confirmed in advance because they
        // don't have JavaScript support.
        $details->nojs = ouwiki_nice_date($lock->seenat + OUWIKI_LOCK_PERSISTENCE);
        $pagelockeddetails = get_string('pagelockeddetailsnojs', 'ouwiki', $details);
    } else {
        $pagelockeddetails = get_string('pagelockeddetails', 'ouwiki', $details);
        if ($lock->expiresat) {
            $pagelockedtimeout = get_string('pagelockedtimeout', 'ouwiki', userdate($lock->expiresat));
        }
    }
    $canoverride = has_capability('mod/ouwiki:overridelock', $context);
    $pagelockedoverride = $canoverride ? '<p>'.get_string('pagelockedoverride', 'ouwiki').'</p>' : '';
    $overridelock = get_string('overridelock', 'ouwiki');
    $overridebutton = $canoverride ? "
<form class='ouwiki_overridelock' action='override.php' method='post'>
  <input type='hidden' name='redirpage' value='annotate' />
  $wikiformfields
  <input type='submit' value='$overridelock' />
</form>
" : '';
    $cancel = get_string('cancel');
    $tryagain = get_string('tryagain', 'ouwiki');
    print "
<div id='ouwiki_lockinfo'>
  <h2>$pagelockedtitle</h2>
  <p>$pagelockeddetails $pagelockedtimeout</p>
  $pagelockedoverride
  <div class='ouwiki_lockinfobuttons'>
    <form action='edit.php' method='get'>
      $wikiformfields
      <input type='submit' value='$tryagain' />
    </form>
    <form action='view.php' method='get'>
      $wikiformfields
      <input type='submit' value='$cancel' />
    </form>
    $overridebutton
  </div>
  </div><div>";

    ouwiki_print_footer($course, $cm, $subwiki, $pagename);
    exit;
}
// The page is now locked to us! Go ahead and print edit form

// get title of the page
$title = get_string('annotatingpage', 'ouwiki');
$wikiname = format_string(htmlspecialchars($ouwiki->name));
$name = $pagename;
if ($pagename) {
    $title = $wikiname.' - '.$title.' : '.$pagename;
} else {
    $title = $wikiname.' - '.$title.' : '.get_string('startpage', 'ouwiki');
}

// Print header
echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context,
    array(array('name' => get_string('annotatingpage', 'ouwiki'), 'link' => null)),
    false, false, '', $title);

// Tabs
ouwiki_print_tabs('annotate', $pagename, $subwiki, $cm, $context, $pageversion->versionid ? true : false, $pageversion->locked);

// prints the div that contains a message when js is disabled in the browser so cannot annotate.
print '<div id="ouwiki_belowtabs_annotate_nojs"><p>'.get_string('jsnotenabled', 'ouwiki').'</p>'.
        '<div class="ouwiki_jsrequired"><p>'.get_string('jsajaxrequired', 'ouwiki').'</p></div></div>';

// opens the annotate specific div for when js is enabled in the browser, user can annotate.
print '<div id="ouwiki_belowtabs_annotate">';

ouwiki_print_editlock($lock, $ouwiki);

if ($ouwiki->timeout) {
    $countdowntext = get_string('countdowntext', 'ouwiki', $ouwiki->timeout/60);
    print "<script type='text/javascript'>
document.write('<p><div id=\"ouw_countdown\"></div>$countdowntext<span id=\"ouw_countdownurgent\"></span></p>');
</script>";
}

print get_string('advice_annotate', 'ouwiki');
$data = $ouwikioutput->ouwiki_print_page($subwiki, $cm, $pageversion, false, 'annotate', $ouwiki->enablewordcount);
echo $data[0];
$annotations = $data[1];

$customdata[0] = $annotations;
$customdata[1] = $pageversion;
$customdata[2] = $pagename;
$customdata[3] = $userid;
$customdata[4] = has_capability('mod/ouwiki:lock', $context);
echo html_writer::start_div('ouw-annotation-list');
echo html_writer::tag('h2', get_string('annotations', 'ouwiki'));
echo html_writer::end_div();

$annotateform = new mod_ouwiki_annotate_form('annotate.php?id='.$id, $customdata);
$annotateform->display();
echo $ouwikioutput->get_bottom_buttons($subwiki, $cm, $context, $pageversion, true);

$usedannotations = array();
foreach ($annotations as $annotation) {
    if (!$annotation->orphaned) {
        $usedannotations[$annotation->id] = $annotation;
    }
}
echo '<div id="annotationcount" style="display:none;">'.count($usedannotations).'</div>';

echo '<div class="yui-skin-sam">';
echo '    <div id="annotationdialog" class="yui-pe-content">';
echo '        <div class="hd">'.get_string('addannotation', 'ouwiki').'</div>';
echo '        <div class="bd">';
echo '            <form method="POST" action="post.php">';
echo '                <label for="annotationtext">'.get_string('addannotation', 'ouwiki').':</label>';
echo '                <textarea name="annotationtext" id="annotationtext" rows="4" cols="30"></textarea>';
echo '            </form>';
echo '        </div>';
echo '    </div>';
echo '</div>';

// init JS module
$stringlist[] = array('add', 'ouwiki');
$stringlist[] = array('cancel', 'ouwiki');
$jsmodule = array('name'     => 'mod_ouwiki_annotate',
                  'fullpath' => '/mod/ouwiki/module.js',
                  'requires' => array('base', 'event', 'io', 'node', 'anim', 'panel',
                                      'yui2-container', 'yui2-dragdrop'),
                  'strings'  => $stringlist
                 );
$PAGE->requires->js_init_call('M.mod_ouwiki_annotate.init', array(), true, $jsmodule);

// close <div id="#ouwiki_belowtabs_annotate">
print '</div>';
// Footer
ouwiki_print_footer($course, $cm, $subwiki, $pagename);
