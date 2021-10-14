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
 * Import page content from other ouwiki activities into this wiki.
 * @package mod
 * @subpackage ouwiki
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot . '/mod/ouwiki/basicpage.php');
require($CFG->dirroot . '/mod/ouwiki/import_form.php');

$pageparams = ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_ARRAY);

/* Define/work out the step of the import process we are on.
 Step 1  - Select wiki (+sub wiki)
Step 2 - Select pages
Step 3 - Confirm
Step 4 - Process import
*/
$curstep = optional_param('step', 1, PARAM_INT);
if (optional_param('cancel', '', PARAM_TEXT) == get_string('cancel')) {
    // Cancelled last step, go back one.
    $curstep -= 2;
}
$pageparams['step'] = $curstep;

$url = new moodle_url('/mod/ouwiki/import.php', array('id' => $id, 'page' => $pagename));
$PAGE->set_url($url);

$PAGE->set_cm($cm);
$PAGE->set_pagelayout('incourse');

$ouwikioutput = $PAGE->get_renderer('mod_ouwiki');

echo $ouwikioutput->ouwiki_print_start($ouwiki, $cm, $course, $subwiki, get_string('import', 'ouwiki'), $context);

echo $OUTPUT->heading(get_string('import', 'ouwiki'));

if ((!$subwiki->canedit) || (!$ouwiki->allowimport)) {
    print_error('You are not able to add content to this wiki.');
}

// Get course id of wiki that is being imported from. Only used in steps 2,3 and 4.
$importedfromcourse = $course;
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid) {
    $importedfromcourse = get_course($courseid);
}

echo '<div class="ouwiki_import ouwiki_import_step' . $curstep . '" id="ouwiki_belowtabs">';

if ($curstep == 1) {
    $courses = enrol_get_users_courses($USER->id, true);
    $pageparams['step']++;
    $wikisfound = false;
    foreach ($courses as $listcourse) {
        // Select wiki from course step, first get wikis.
        $modinfo = get_fast_modinfo($listcourse);
        $allwikis = $modinfo->get_instances_of('ouwiki');
        unset($allwikis[$ouwiki->id]);// Don't include current activity.
        $availablewikis = array();
        foreach ($allwikis as $wikiact) {
            $wikicontext = context_module::instance($wikiact->id);
            // Check wiki is available.
            if (!$wikiact->uservisible ||
                    !has_capability('mod/ouwiki:view', $wikicontext)) {
                continue;
            }
            // Create object for this wiki that we will use if it is shown.
            $wikiob = new stdClass();
            $wikiob->cm = $wikiact;
            $wikiob->selector = array();
            $wikiob->selectordefault = 0;
            $wikiob->nocontent = false;

            // For each wiki type do further access checks and get more info.
            $wikiinst = $DB->get_record('ouwiki', array('id' => $wikiact->instance));
            if (!$wikiinst) {
                continue;
            }
            if ($wikiinst->subwikis == OUWIKI_SUBWIKIS_SINGLE) {
                // Course wiki, check subwiki and start page exists.
                if (!$wikisubwiki = $DB->get_record_select('ouwiki_subwikis', 'wikiid = ? AND
                        groupid IS NULL AND userid IS NULL', array($wikiinst->id), 'id')) {
                    $wikiob->nocontent = true;
                } else if (!ouwiki_subwiki_content_exists($wikisubwiki->id)) {
                    $wikiob->nocontent = true;
                } else {
                    $wikiob->selectordefault = $wikisubwiki->id;
                }
            } else if ($wikiinst->subwikis == OUWIKI_SUBWIKIS_GROUPS) {
                // Group wiki. Get all groups user can see (checking they have content to import).
                if (!$groups = groups_get_activity_allowed_groups($wikiact)) {
                    continue;
                }
                $default = groups_get_activity_group($wikiact);
                foreach ($groups as $group) {
                    // Check group subwiki has content before adding it.
                    if ($wikisubwiki = $DB->get_record_select('ouwiki_subwikis', 'wikiid = ?
                            AND groupid = ? AND userid IS NULL', array($wikiinst->id, $group->id), 'id')) {
                        if (ouwiki_subwiki_content_exists($wikisubwiki->id)) {
                            $wikiob->selector[$wikisubwiki->id] = format_string($group->name);
                            if ($group->id == $default) {
                                $wikiob->selectordefault = $wikisubwiki->id;
                            }
                        }
                    }
                }
                // If no groups have content disable wiki selector.
                if (empty($wikiob->selector)) {
                    $wikiob->nocontent = true;
                }
            } else if ($wikiinst->subwikis == OUWIKI_SUBWIKIS_INDIVIDUAL) {
                // Individual wiki. Get all users user can view (checking subwiki for content).
                $userfields = user_picture::fields('u', null, 'uid');
                $sql = "SELECT sw.id, $userfields
                        FROM {ouwiki_subwikis} sw
                        INNER JOIN {user} u ON sw.userid = u.id
                        INNER JOIN (SELECT subwikiid
                            FROM {ouwiki_pages}
                            WHERE currentversionid IS NOT NULL
                            GROUP BY subwikiid) as wp on wp.subwikiid = sw.id
                        WHERE sw.wikiid = ?";
                $params = array($wikiinst->id);
                if (!has_capability('mod/ouwiki:viewallindividuals', $wikicontext)) {
                    if (!has_capability('mod/ouwiki:viewgroupindividuals', $wikicontext)) {
                        // Can only see own wiki (if exists).
                        $sql .= ' AND sw.userid = ?';
                        $params[] = $USER->id;
                    } else {
                        // Can see any users that are in the same group(s).
                        if ($theirgroups = groups_get_all_groups($wikiact->course, $USER->id,
                                $wikiact->groupingid, 'g.id')) {
                            $groupmembers = array();
                            foreach ($theirgroups as $group) {
                                if ($members = groups_get_members($group->id, 'u.id')) {
                                    $groupmembers = array_merge($groupmembers, array_keys($members));
                                }
                            }
                            if (!empty($groupmembers)) {
                                list($insql, $inparams) = $DB->get_in_or_equal($groupmembers);
                                $sql .= 'AND sw.userid ' . $insql;
                                $params = array_merge($params, $inparams);
                            }
                        }
                    }
                }
                $sql .= ' ORDER BY u.lastname, u.firstname';

                if (!$choices = $DB->get_records_sql($sql, $params)) {
                    $wikiob->nocontent = true;
                }

                foreach ($choices as $wikisubwiki) {
                    $wikiob->selector[$wikisubwiki->id] = fullname($wikisubwiki);
                    if ($wikisubwiki->uid == $USER->id) {
                        $wikiob->selectordefault = $wikisubwiki->id;
                    }
                }
            }
            // Add wiki info to list of available wiki activities.
            $availablewikis[] = $wikiob;
        }
        $courserenderer = $PAGE->get_renderer('course');
        // Create selection forms for available wikis.
        $pageparams['courseid'] = $listcourse->id;
        $i = 0;
        foreach ($availablewikis as $showwiki) {
            if ($i == 0) {
                $coursename = $listcourse->shortname . ' ' . $listcourse->fullname;
                echo html_writer::div($coursename);
            }
            $i++;

            echo html_writer::start_div('ouwiki_import_act');
            $customdata = array('wikiinfo' => $showwiki, 'params' => $pageparams,
                    'actlink' => $courserenderer->course_section_cm_name($showwiki->cm));
            $form = new mod_ouwiki_import_wikiselect_form(null, $customdata);
            $form->display();
            echo html_writer::end_div();
            $wikisfound = true;
        }
    }

    if (!$wikisfound) {
        // If courses are empty print a warning message.
        echo $OUTPUT->notification(get_string('unabletoimport', 'ouwiki'));
        unset($pageparams['step']);
        echo $OUTPUT->continue_button(new moodle_url('/mod/ouwiki/view.php', $pageparams));
    }
} else if ($curstep == 2) {
    // Select pages, first ensure step 1 data correct.
    require_sesskey();
    if ($pagelist = optional_param('pages', null, PARAM_SEQUENCE)) {
        // Full page list available e.g. from cancel.
        $pagelist = explode(',', $pagelist);
    }

    $selectedact = required_param('importid', PARAM_INT);
    $selectedsubwiki = required_param('subwikiid' . $selectedact, PARAM_INT);
    $selectedouwiki = '';

    ouwiki_get_wikiinfo($selectedact, $selectedsubwiki, $selectedouwiki, $importedfromcourse);

    echo html_writer::tag('p', get_string('import_selectwiki', 'ouwiki', $selectedact->get_formatted_name()));

    // Build page selector.
    $pages = '';
    $index = ouwiki_get_subwiki_index($selectedsubwiki->id);
    ouwiki_build_tree($index);
    // Print out in hierarchical form...
    $pages .= html_writer::start_tag('ul', array('class' => 'ouw_indextree'));
    $pages .= ouwiki_tree_index('ouwiki_display_wikiindex_page_in_index', reset($index)->pageid,
            $index, $selectedsubwiki, $selectedact, null, true);
    $pages .= html_writer::end_tag('ul');
    $orphans = '';
    foreach ($index as $indexitem) {
        if (count($indexitem->linksfrom) == 0 && $indexitem->title !== '') {
            $orphanindex = ouwiki_get_sub_tree_from_index($indexitem->pageid, $index);
            ouwiki_build_tree($orphanindex);
            $orphans .= ouwiki_tree_index('ouwiki_display_wikiindex_page_in_index',
                    $indexitem->pageid, $orphanindex, $selectedsubwiki, $selectedact, null, true);
        }
    }
    if (!empty($orphans)) {
        $pages .= $OUTPUT->heading(get_string('orphanpages', 'ouwiki'), 3);
        $pages .= html_writer::start_tag('ul', array('class' => 'ouw_indextree'));
        $pages .= $orphans;
        $pages .= html_writer::end_tag('ul');
    }
    $PAGE->requires->yui_module('moodle-mod_ouwiki-pageselector', 'M.mod_ouwiki.pageselector.init', array($pagelist));
    // Prepare form parameters.
    $wikiinfo = array('importid' => $selectedact->id , 'subwikiid' => $selectedsubwiki->id);
    $pageparams['step']++;
    $pageparams['courseid'] = $importedfromcourse->id;
    $form = new mod_ouwiki_import_pageselect_form(null, array('params' => array_merge($pageparams,
            $wikiinfo), 'pages' => $pages));
    $form->display();
} else if ($curstep == 3) {
    // Confirmation - check OK to proceed and look for merge conflicts, locks etc.
    require_sesskey();
    $selectedact = required_param('importid', PARAM_INT);
    $selectedsubwiki = required_param('subwikiid', PARAM_INT);
    $selectedouwiki = '';

    ouwiki_get_wikiinfo($selectedact, $selectedsubwiki, $selectedouwiki, $importedfromcourse);

    // Build up index, get selected pages - making sure sub pages are included.
    $index = ouwiki_get_subwiki_index($selectedsubwiki->id);
    ouwiki_build_tree($index);
    $pagelist = array();// Our list of page ids to import.
    if ($pages = optional_param('pages', null, PARAM_SEQUENCE)) {
        // Full page list available e.g. from cancel.
        $pagelist = explode(',', $pages);
    } else {
        // Find pages sent from step 1, get any linked pages also.
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'page') === 0) {
                // Add in page and recursive add of links.
                ouwiki_add_linkedpages($pagelist, $index, $value);
            }
        }
        $pagelist = array_unique($pagelist);
    }
    echo $OUTPUT->heading(get_string('import_confirm', 'ouwiki'), 3);
    // Setup confirm form.
    $confirmdata = array(
            'importfrom' => $selectedact->get_formatted_name(),
            'pages' => array(), // Array of page names.
            'conflicts' => array(), // Array of conflicting pages and if locked.
            'lockedpage' => false, // If any pages were locked (stops import).
            'startselected' => false, // Was start page included?
            'startlocked' => false, // Check start page not locked.
            'wikipages' => array() // Current wiki pages and locked status.
            );
    foreach ($pagelist as $page) {
        $ipagename = $index[$page]->title;
        if ($ipagename == '') {
            $confirmdata['startselected'] = true;
            array_unshift($confirmdata['pages'], get_string('startpage', 'ouwiki'));
        } else {
            $confirmdata['pages'][] = $ipagename;
        }
        // Check if this page already exists, and is locked.
        if ($pageexists = ouwiki_get_current_page($subwiki, $ipagename, OUWIKI_GETPAGE_ACCEPTNOVERSION)) {
            $locked = false;
            if ($pageexists->locked || (ouwiki_is_page_locked($pageexists->pageid) &&
                    !has_capability('mod/ouwiki:overridelock', $context))) {
                $locked = true;
                if ($ipagename != '') {
                    $confirmdata['lockedpage'] = true;
                }
            }
            if ($ipagename == '') {
                $confirmdata['startlocked'] = $locked;
            } else {
                $confirmdata['conflicts'][$ipagename] = $locked;
            }
        }
    }
    if (!$confirmdata['startselected'] && !empty($pagelist)) {
        // We didn't select start page, so need to choose a page to use as base for links.
        $confirmdata['wikipages']['-1'] = get_string('import_confirm_linkfrom_newpage', 'ouwiki');
        $thisindex = ouwiki_get_subwiki_allpages_index($subwiki);
        foreach ($thisindex as $thispage) {
            if ($thispage->title == '') {
                $thispage->title = get_string('startpage', 'ouwiki');
            }
            if ($thispage->locked) {
                // Prevent selection by adding a note that page is locked.
                $confirmdata['wikipages'][$thispage->pageid] = $thispage->title .
                '[' . get_string('import_lockedpage', 'ouwiki') . ']';
            } else {
                $confirmdata['wikipages'][$thispage->pageid] = $thispage->title;
            }
        }
        if (empty($thisindex)) {
            // No pages at all - add start page manually with id of 0.
            $confirmdata['wikipages'][0] = get_string('startpage', 'ouwiki');
        }
    }
    $wikiinfo = array('importid' => $selectedact->id , 'subwikiid' => $selectedsubwiki->id,
            'subwikiid' . $selectedact->id => $selectedsubwiki->id, 'pages' => implode(',', $pagelist));
    $pageparams['step']++;
    $pageparams['courseid'] = $importedfromcourse->id;
    $form = new mod_ouwiki_import_confirm_form(null, array('params' => array_merge($pageparams, $wikiinfo),
            'confirmdata' => $confirmdata));
    $form->display();
} else if ($curstep == 4) {
    // Processing step.
    require_sesskey();
    $selectedact = required_param('importid', PARAM_INT);
    $selectedsubwiki = required_param('subwikiid', PARAM_INT);
    $selectedouwiki = '';

    ouwiki_get_wikiinfo($selectedact, $selectedsubwiki, $selectedouwiki, $importedfromcourse);

    $pagelist = explode(',', required_param('pages', PARAM_SEQUENCE));// Page ids to import.
    $conflictmerge = optional_param('conflictmerge', 0, PARAM_INT);// Page conflict setting.
    $startpagemerge = optional_param('startpagemerge', 0, PARAM_INT);// SP conflict setting.
    $linkfrom = optional_param('linkfrom', null, PARAM_INT);// Add links to (if SP not included).

    // Build up index of orig wiki, get selected pages - making sure sub pages are included.
    $index = ouwiki_get_subwiki_allpages_index($selectedsubwiki);
    $conflicts = array();// All conflicting pages in $pagelist [id] => title.
    $warned = false;// Set to true if user warned of problems.
    $startpagelocked = false; // Start page locked.
    $pagelocked = false;      // Page you are trying to import into is locked.
    $startpageid = null;

    echo $OUTPUT->heading(get_string('import_process', 'ouwiki'), 3);
    try {
        // Throw moodle_exception when any problems to display continue/cancel form.

        $checkpagelist = array();
        if ($linkfrom > 0) {
            // Selected page to link to, so needs conflict checking.
            $checkpagelist[] = $linkfrom;
        } else if ($pageexists = ouwiki_get_current_page($subwiki, '', OUWIKI_GETPAGE_CREATE)) {
            // Check to see whether start page exists and is locked.
            $startpagelocked = $pageexists->locked;
            $startpageid = $pageexists->pageid;
            $checkpagelist[] = $startpageid;
        }

        if ($startpagemerge == 1) {
            if ($currentpageexists = ouwiki_get_current_page($subwiki, get_string('importedstartpage', 'ouwiki'),
                    OUWIKI_GETPAGE_ACCEPTNOVERSION)) {
                $checkpagelist[] = $currentpageexists->pageid;
            }
        }
        if ($linkfrom == -1) {
            if ($currentpageexists = ouwiki_get_current_page($subwiki, get_string('importedpages', 'ouwiki'),
                        OUWIKI_GETPAGE_ACCEPTNOVERSION)) {
                $checkpagelist[] = $currentpageexists->pageid;
            }
        }

        // Obtain lock for any conflicting pages (inc start page).
        $checkpagelist = array_merge($pagelist, $checkpagelist);
        foreach ($checkpagelist as $page) {
            if (!isset($index[$page])) {
                $ipagename = $DB->get_field('ouwiki_pages', 'title', array('id' => $page), MUST_EXIST);
            } else {
                $ipagename = $index[$page]->title;
            }
            if ($ipagename == '' && $startpagemerge == 1) {
                // If start page exists then set imported start page in conflicts array.
                if (ouwiki_get_current_page($subwiki, $ipagename, OUWIKI_GETPAGE_ACCEPTNOVERSION)) {
                    $conflicts[$page] = $ipagename;
                }
                // Doesn't matter if start page is locked - when adding links check and skip if is.
                continue;
            }

            // Check if this page already exists, and is locked.
            if ($currentpageexists = ouwiki_get_current_page($subwiki, $ipagename, OUWIKI_GETPAGE_ACCEPTNOVERSION)) {
                $conflicts[$page] = $ipagename;
                if (has_capability('mod/ouwiki:overridelock', $context)) {
                    ouwiki_override_lock($currentpageexists->pageid);
                }
                list ($lock, $by) = ouwiki_obtain_lock($ouwiki, $currentpageexists->pageid);
                if ($currentpageexists->locked) {
                    $lock = false;// No one can edit.
                }
                if (!$lock) {
                    throw new moodle_exception('import_process_locked', 'ouwiki');
                }
            }
        }
        $newpagelist = array();// Use to store names of new pages imported.
        $updatepagelist = array();
        $importedindex = array();
        $updatedindex = array();

        $pbar = new progress_bar('mod_ouwiki_import', 500, true);
        for ($len = count($pagelist), $a = 0; $a < $len; $a++) {
            $page = $pagelist[$a];
            if (!isset($index[$page])) {
                // For some reason our page is not in the list in index - stop.
                throw new moodle_exception('Error. Page to import missing.');
            }
            $pageinfo = $index[$page];
            if (in_array($page, array_keys($conflicts))) {
                // This page has been identified as one that already exists.
                if ($startpagelocked) {
                    $warned = true;
                }

                if ($pageinfo->title == '' && $startpagemerge == 1) {
                    // Create a new 'Imported start page' with imported start page content.
                    $importedstartpage = get_string('importedstartpage', 'ouwiki');
                    $newpage = true;
                    // See whether an 'Imported start page' already exists in the target wiki.
                    if ($importedstartpageexists = ouwiki_get_current_page($subwiki, $importedstartpage, OUWIKI_GETPAGE_CREATE)) {
                         // Check if 'Imported start page' (lang string needed) exists or is locked (skip and warn).
                        if ($importedstartpageexists->locked) {
                            $warned = true;
                            $pagelocked = true;
                        }
                        // Get imported page content.
                        $importedcontent = $pageinfo->xhtml;
                        // Get existing page content.
                        $currentpagecontent = $importedstartpageexists->xhtml;
                        // Add imported page content to existing page content.
                        $importedstartpageexists->xhtml = $currentpagecontent . $importedcontent;

                        // Save new version of existing page.
                        $versionid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $importedstartpageexists->title,
                                $importedstartpageexists->xhtml, -1, -1,  -1, null, null, null,
                                $pageinfo->versionid);

                        // Add page names to $newpagelist|$updatepagelist array as appropriate.
                        if (empty($currentpagecontent)) {
                            // Link imported start page to start page.
                            $newpagelist[] = $importedstartpage;
                            $importedstartpageexists->linkfrom = $startpageid;
                            $importedindex[$importedstartpageexists->pageid] = $importedstartpageexists;
                            $linkfrom = $startpageid;
                        } else {
                            $updatepagelist[] = $importedstartpage;
                        }
                        $updatepagelist[] = get_string('startpage', 'ouwiki');
                    }
                } else {
                    // Either merge or replace existing page content.
                    if ($conflictmerge == 0 || ($pageinfo->title == '' && $startpagemerge == 0)) {
                        // Append import page content to existing content.
                        if (!$currentpageexists = ouwiki_get_current_page($subwiki, $pageinfo->title,
                                OUWIKI_GETPAGE_REQUIREVERSION)) {
                            // We got the page above, but not now?
                            throw new moodle_exception('Error. Page to import into missing.');
                        }
                        // Append existing content with imported content.
                        // Get imported page content.
                        $importedcontent = $pageinfo->xhtml;
                        // Get existing page content.
                        $currentpagecontent = $currentpageexists->xhtml;
                        // Merge/Add imported page content to existing page content.
                        $currentpagecontent = $currentpagecontent . $importedcontent;
                        // Save new version of existing page.
                        $versionid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $currentpageexists->title,
                                $currentpagecontent, -1, -1,  -1, null, null, null,  $pageinfo->versionid);
                        $updatedindex[$pageinfo->pageid] = $pageinfo;
                    } else {
                        // Override existing content with imported content.
                        // Get imported page content.
                        $importedcontent = $pageinfo->xhtml;
                        // Replace existing page content with imported page content.
                        // Save new version of existing page.
                        $versionid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pageinfo->title, $importedcontent,
                                -1, -1,  -1, null, null, null,  $pageinfo->versionid);
                        $updatedindex[$pageinfo->pageid] = $pageinfo;
                    }
                    $updatepagelist[] = $pageinfo->title;
                }
            } else {
                // New page.
                // Add new page using $pageinfo->xhtml as content.
                $newpagelist[] = $pageinfo->title;
                $pageversion = ouwiki_get_current_page($subwiki, $pageinfo->title, OUWIKI_GETPAGE_CREATE);
                $versionid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $pageinfo->title, $pageinfo->xhtml,
                        -1, -1,  -1, null, null, null,  $pageinfo->versionid);
                $importedindex[$pageinfo->pageid] = $pageinfo;
            }

            // Add any attachments and image files to the page.
            // Information needed for files and attachments.
            $fs = get_file_storage();
            $modcontext = context_module::instance($selectedact->id);
            $prevversion = $pageinfo->versionid;
            // Add any files.
            if ($oldfiles = $fs->get_area_files($modcontext->id, 'mod_ouwiki', 'content', $prevversion)) {
                foreach ($oldfiles as $oldfile) {
                    // Copy this file to the version record.
                    try {
                        $fs->create_file_from_storedfile(array(
                                'contextid' => $context->id,
                                'filearea' => 'content',
                                'itemid' => $versionid), $oldfile);
                    } catch (stored_file_creation_exception $e) {
                        continue;
                    }
                }
            }
            // Add any attachments.
            if ($oldfiles = $fs->get_area_files($modcontext->id, 'mod_ouwiki', 'attachment', $prevversion)) {
                foreach ($oldfiles as $oldfile) {
                    try {
                        // Copy this file to the version record.
                        $fs->create_file_from_storedfile(array(
                                'contextid' => $context->id,
                                'filearea' => 'attachment',
                                'itemid' => $versionid), $oldfile);
                    } catch (stored_file_creation_exception $e) {
                        continue;
                    }
                }
            }

            $pbar->update($a + 1, count($pagelist), get_string('import_process', 'ouwiki'));

            // We have a problem with new page - need to check linkfrom value.
            if ($linkfrom == -1) {
                $importedindex[$pageinfo->pageid] = $pageinfo;
            }
        }

        if (!is_null($linkfrom)) {
            $mergexhtml = '';
            // Merge imported index and updated index.
            $pageindexes = $importedindex + $updatedindex;
            // Work out top level of the updated/imported pages ready to add links to these.
            foreach ($pageindexes as $page) {
                if ( (!isset($page->linksfrom)) || empty($page->linksfrom)) {
                    $page->linksfrom = array();
                    $page->linksfrom[0] = 0;     // Nullifies for test below.
                }
                // Create the links xhtml.
                if (!in_array($page->linksfrom[0], $pagelist)) {
                    // Assume that we have a top level page.
                    $page->linksfrom = array();
                    $mergexhtml .= '<p>[[' .$page->title. ']]</p>';
                }
            }

            if ($linkfrom == 0) {
                // Add links to non-existing start page.
                // Create start page with links.
                $updatepagelist[] = get_string('startpage', 'ouwiki');
                $pageversion = ouwiki_get_current_page($subwiki, null, OUWIKI_GETPAGE_CREATE);
                //  Add links into selected page content.
                $versionid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, '', $mergexhtml);
            } else if ($linkfrom == -1) {
                // Check start page not locked (skip and warn) and add link to 'Imported pages'.
                // Check to see whether start page is locked.
                if ($startpagelocked) {
                    $warned = true;
                }
                // Create a new 'Imported pages' page with the links on.
                $importedpages = get_string('importedpages', 'ouwiki');
                // Create/update(merge) 'Imported pages'.
                $linkfrompage = ouwiki_get_current_page($subwiki, $importedpages, OUWIKI_GETPAGE_CREATE);
                $linkfrompageid = $linkfrompage->pageid;
                $newpage = false;
                if (empty($linkfrompage->xhtml)) {
                    // New page.
                    $newpage = true;
                } else if ($linkfrompage->locked) {
                    // Check for 'Imported pages' not locked(skip and warn).
                    $warned = true;
                    $pagelocked = true;
                }
                // Merge links xhtml into import into page xhtml content.
                $linkfrompage->xhtml = $linkfrompage->xhtml . $mergexhtml;
                $linkfrompage->linkfrom = $pageinfo->pageid;
                // Place in new page creation.
                $versionid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $linkfrompage->title, $linkfrompage->xhtml);
                // Add page names to $newpagelist|$updatepagelist array as appropriate.
                if ($newpage) {
                    $newpagelist[] = $importedpages;
                    // Update 'Start page'.
                    $title = '';
                    $xhtmlcontent = '';
                    if ($pageexists) {
                        $xhtmlcontent = $pageexists->xhtml . '<p>[[' .$linkfrompage->title. ']]</p>';
                        $title = $pageexists->title;
                    } else {
                        $xhtmlcontent = $xhtmlcontent . '<p>[[' .$linkfrompage->title. ']]</p>';
                    }
                    ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $title, $xhtmlcontent);
                } else {
                    $updatepagelist[] = $importedpages;
                }
                // Set linkfrom to 'Imported pages'.
                $linkfrom = $importedpages;
                $updatepagelist[] = get_string('startpage', 'ouwiki');
            } else {
                // Add links into selected page.
                if (!$pageinfo = $DB->get_record('ouwiki_pages', array('id' => $linkfrom))) {
                    throw new moodle_exception('Error. Page to import into missing.');
                }
                // Store page name so we can go there.
                $linkfrom = $pageinfo->title;
                // Add links into selected page content.
                $linkfrompage = ouwiki_get_current_page($subwiki, $pageinfo->title, OUWIKI_GETPAGE_CREATE);
                $linkfrompageid = $linkfrompage->pageid;
                // Merge links xhtml into import into page xhtml content.
                $linkfrompage->xhtml = $linkfrompage->xhtml . $mergexhtml;
                $versionid = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, $linkfrompage->title, $linkfrompage->xhtml);
                if ($pageinfo->title == '') {
                    // Start page.
                    $updatepagelist[] = get_string('startpage', 'ouwiki');
                } else {
                    $updatepagelist[] = $pageinfo->title;
                }
            }
        }
        // Provide summary output.
        echo $OUTPUT->heading(get_string('import_process_summary', 'ouwiki'), 3);
        echo html_writer::tag('p', $warned ? get_string('import_process_summary_warn', 'ouwiki') :
                get_string('import_process_summary_success', 'ouwiki'));
        if ($startpagelocked) {
            // Start page locked.
            echo html_writer::div(get_string('import_process_startpage_locked', 'ouwiki'));
        }
        if ($pagelocked) {
            // Page imported into locked.
            echo html_writer::div(get_string('import_process_locked', 'ouwiki'));
        }

        // Show pages imported.
        echo html_writer::start_div();
        echo get_string('import_process_summary_imported', 'ouwiki');
        echo html_writer::alist(array_unique($newpagelist));
        echo html_writer::end_div();
        if (!empty($updatepagelist)) {
            // Show pages updated.
            echo html_writer::start_div();
            echo get_string('import_process_summary_updated', 'ouwiki');
            echo html_writer::alist(array_unique($updatepagelist));
            echo html_writer::end_div();
        }
        // Continue button to page where links were added.
        if (is_null($linkfrom) || (is_numeric($linkfrom) && ($linkfrom < 1)) || $linkfrom == '') {
            unset($pageparams['page']);// Set to start page.
        } else {
            $pageparams['page'] = $linkfrom;
        }
        unset($pageparams['step']);
        echo $OUTPUT->continue_button(new moodle_url('/mod/ouwiki/view.php', $pageparams));
    } catch (moodle_exception $e) {
        // Display continue/cancel form.
        $wikiinfo = array('importid' => $selectedact->id , 'subwikiid' => $selectedsubwiki->id,
                'pages' => implode(',', $pagelist), 'conflictmerge' => $conflictmerge,
                'startpagemerge' => $startpagemerge, 'linkfrom' => $linkfrom,
                'sesskey' => sesskey());
        $continue = new moodle_url('/mod/ouwiki/import.php', array_merge($pageparams, $wikiinfo));
        $pageparams['step']++;
        $pageparams['cancel'] = get_string('cancel');
        $cancel = new moodle_url('/mod/ouwiki/import.php', array_merge($pageparams, $wikiinfo));
        echo $OUTPUT->confirm($e->getMessage(), $cancel, new moodle_url('/mod/ouwiki/view.php', $pageparams));
        ouwiki_print_footer($course, $cm, $subwiki, $pagename);
        exit;
    }
}

// Footer.
ouwiki_print_footer($course, $cm, $subwiki, $pagename);

/**
 * Recursive function to put all pages from page id value into pagelist
 * @param array $pagelist
 * @param array $index subwiki index tree
 * @param int $value page id
 */
function ouwiki_add_linkedpages(&$pagelist, &$index, $value) {
    if (!in_array($value, $pagelist) && in_array($value, array_keys($index))) {
        $pagelist[] = $value;
        $newpages = array_unique($index[$value]->linksto);
        foreach ($newpages as $newvalue) {
            ouwiki_add_linkedpages($pagelist, $index, $newvalue);
        }
    }
}
