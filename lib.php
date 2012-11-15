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
 * Standard API to Moodle core.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

defined('MOODLE_INTERNAL') || die();

/* Do not include any libraries here! */

function ouwiki_add_instance($data, $mform) {
    global $DB;

    $cmid = $data->coursemodule;
    $context = get_context_instance(CONTEXT_MODULE, $cmid);

    if ($formdata = $data) {
        // Set up null values
        $nullvalues = array('editbegin', 'editend', 'timeout');
        foreach ($nullvalues as $nullvalue) {
            if (empty($formdata->{$nullvalue})) {
                unset($formdata->{$nullvalue});
            }
        }

        if (strlen(preg_replace('/(<.*?>)|(&.*?;)|\s/', '', $formdata->intro)) == 0) {
            unset($formdata->intro);
        }

        // Create record
        $ouwikiid = $DB->insert_record('ouwiki', $formdata);
        $formdata->id = $ouwikiid;

        ouwiki_grade_item_update($formdata);

        // template file save
        $fs = get_file_storage();
        if ($filename = $mform->get_new_filename('template_file')) {
            $file = $mform->save_stored_file('template_file', $context->id, 'mod_ouwiki', 'template', $ouwikiid, '/', $filename);
            $DB->set_field('ouwiki', 'template', '/'.$file->get_filename(), array('id' => $formdata->id));
        }

        return $ouwikiid;
    }
    // Note: existing template files will be stored based on the old data structure
    // however, because they are only used in code once (on ouwiki_init_pages)
    // it wasn't necessary to rejig these
}

function ouwiki_update_instance($data, $mform) {
    global $CFG, $DB;

    $data->id = $data->instance;

    // Update main record
    unset($data->template);
    $DB->update_record('ouwiki', $data);

    // Set up null values
    $nullvalues = array('editbegin', 'editend', 'timeout');
    foreach ($nullvalues as $nullvalue) {
        if (empty($data->{$nullvalue})) {
            unset($data->{$nullvalue});
            $DB->set_field('ouwiki', $nullvalue, null, array('id' => $data->id));
        }
    }
    if (strlen(preg_replace('/(<.*?>)|(&.*?;)|\s/', '', $data->intro)) == 0) {
        unset($data->intro);
        $DB->set_field('ouwiki', 'intro', null, array('id' => $data->id));
    }

    ouwiki_grade_item_update($data);

    return true;
}

function ouwiki_delete_instance($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

    $cm = get_coursemodule_from_instance('ouwiki', $id, 0, false, MUST_EXIST);

    // Delete search data
    if (ouwiki_search_installed()) {
        local_ousearch_document::delete_module_instance_data($cm);
    }

    // Delete grade
    $ouwiki = $DB->get_record('ouwiki', array('id' => $cm->instance));
    ouwiki_grade_item_delete($ouwiki);

    // Subqueries that find all versions and pages associated with this wiki
    // and delete them all bottom up
    $versions = $DB->get_records_sql("SELECT DISTINCT v.id
                        FROM {ouwiki_subwikis} s
                        INNER JOIN {ouwiki_pages} p ON p.subwikiid = s.id
                        INNER JOIN {ouwiki_versions} v ON v.pageid = p.id
                        WHERE s.wikiid = ?", array($id));
    if (!empty($versions)) {
        list($vsql, $vparams) = $DB->get_in_or_equal(array_keys($versions));
        $DB->delete_records_select('ouwiki_links', "fromversionid $vsql", $vparams);
    }

    $pages = $DB->get_records_sql("SELECT p.id
                    FROM {ouwiki_subwikis} s
                    INNER JOIN {ouwiki_pages} p ON p.subwikiid = s.id
                    WHERE s.wikiid = ?", array($id));
    if (!empty($pages)) {
        list($psql, $pparams) = $DB->get_in_or_equal(array_keys($pages));
        $DB->delete_records_select('ouwiki_versions', "pageid $psql", $pparams);
        $DB->delete_records_select('ouwiki_locks', "pageid $psql", $pparams);
        $DB->delete_records_select('ouwiki_sections', "pageid $psql", $pparams);
    }

    $subwikis = $DB->get_records_sql("SELECT s.id
                        FROM {ouwiki_subwikis} s
                        WHERE s.wikiid = ?", array($id));
    if (!empty($subwikis)) {
        list($swsql, $swparams) = $DB->get_in_or_equal(array_keys($subwikis));
        $DB->delete_records_select('ouwiki_pages', "subwikiid $swsql", $swparams);
    }

    $DB->delete_records_select('ouwiki_subwikis', 'wikiid = ?', array($id));
    $DB->delete_records('ouwiki', array('id' => $id));
    return true;
}

/**
 * @return array List of all system capabilitiess used in module
 */
function ouwiki_get_extra_capabilities() {
    // Note: I made this list by searching for moodle/ within the module
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames',
            'moodle/course:manageactivities');
}

/**
 * Update all wiki documents for ousearch.
 *
 * @param bool $feedback If true, prints feedback as HTML list items
 * @param int $courseid If specified, restricts to particular courseid
 */
function ouwiki_ousearch_update_all($feedback = null, $courseid = 0) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');
    // Get list of all wikis. We need the coursemodule data plus
    // the type of subwikis
    $coursecriteria = $courseid === 0 ? '' : 'AND cm.course = '.$courseid;
    $sql = "SELECT cm.id, cm.course, cm.instance, w.subwikis
                                            FROM {modules} m
                                            INNER JOIN {course_modules} cm ON cm.module = m.id
                                            INNER JOIN {ouwiki} w ON cm.instance = w.id
                                        WHERE m.name = 'ouwiki' {$coursecriteria}";
    $coursemodules = $DB->get_records_sql($sql, array());

    if (!$coursemodules) {
        return;
    }

    if ($feedback) {
        print '<li><strong>'.count($coursemodules).'</strong> wikis to process.</li>';
        $dotcount = 0;
    }

    $count = 0;
    foreach ($coursemodules as $coursemodule) {

        // This condition is needed because if somebody creates some stuff
        // then changes the wiki type, it actually keeps the old bits
        // in the database. Maybe it shouldn't, not sure.
        switch($coursemodule->subwikis) {
            case OUWIKI_SUBWIKIS_SINGLE:
                $where = "sw.userid IS NULL AND sw.groupid IS NULL";
                break;

            case OUWIKI_SUBWIKIS_GROUPS:
                $where = "sw.userid IS NULL AND sw.groupid IS NOT NULL";
                break;

            case OUWIKI_SUBWIKIS_INDIVIDUAL:
                $where = "sw.userid IS NOT NULL AND sw.groupid IS NULL";
                break;
        }

        // Get all pages in that wiki
        $sql = "SELECT p.id, p.title, v.xhtml, v.timecreated, sw.groupid, sw.userid
            FROM {ouwiki_subwikis} sw
            INNER JOIN {ouwiki_pages} p ON p.subwikiid = sw.id
            INNER JOIN {ouwiki_versions} v ON v.id = p.currentversionid
            WHERE sw.wikiid = ? AND $where";
        $rs = $DB->get_recordset_sql($sql, array($coursemodule->instance));

        foreach ($rs as $result) {

            // Update the page for search
            $doc = new local_ousearch_document();
            $doc->init_module_instance('ouwiki', $coursemodule);
            if ($result->groupid) {
                $doc->set_group_id($result->groupid);
            }
            if ($result->title) {
                $doc->set_string_ref($result->title);
            }
            if ($result->userid) {
                $doc->set_user_id($result->userid);
            }
            $title = $result->title ? $result->title : '';
            $doc->update($title, $result->xhtml, $result->timecreated);
        }
        $rs->close();

        $count++;
        if ($feedback) {
            if ($dotcount == 0) {
                print '<li>';
            }
            print '.';
            $dotcount++;
            if ($dotcount == 20 || $count == count($coursemodules)) {
                print 'done '.$count.'</li>';
                $dotcount = 0;
            }
            flush();
        }
    }
}

/**
 * Obtains a search document given the ousearch parameters.
 * @param object $document Object containing fields from the ousearch documents table
 * @return mixed False if object can't be found, otherwise object containing the following
 *   fields: ->content, ->title, ->url, ->activityname, ->activityurl
 */
function ouwiki_ousearch_get_document($document) {
    global $CFG, $DB;

    $params = array($document->coursemoduleid);

    $titlecondition = 'AND p.title =  \'\'';
    if (!empty($document->stringref)) {
        $titlecondition = ' AND p.title = ?';
        $params[] = $document->stringref;
    }

    $groupconditions = '';
    if (is_null($document->groupid)) {
        $groupconditions .= ' AND sw.groupid IS NULL';
    } else {
        $groupconditions .= ' AND sw.groupid = ?';
        $params[] = $document->groupid;
    }
    if (is_null($document->userid)) {
        $groupconditions .= ' AND sw.userid IS NULL';
    } else {
        $groupconditions .= ' AND sw.userid = ?';
        $params[] = $document->userid;
    }

    $sql = "SELECT w.name AS activityname, p.title AS title, v.xhtml AS content
        FROM {course_modules} cm
        INNER JOIN {ouwiki} w ON cm.instance = w.id
        INNER JOIN {ouwiki_subwikis} sw ON sw.wikiid = w.id
        INNER JOIN {ouwiki_pages} p ON p.subwikiid = sw.id
        INNER JOIN {ouwiki_versions} v ON v.id = p.currentversionid
            WHERE cm.id = ?
            $titlecondition
            $groupconditions";

    $result = $DB->get_record_sql($sql, $params);

    if (!$result) {
        return false;
    }

    if ($result->title == '') {
        $result->title = get_string('startpage', 'ouwiki');
    }
    $result->activityurl = new moodle_url('/mod/ouwiki/view.php', array('id' => $document->coursemoduleid));
    $result->url = $result->activityurl;
    if ($document->stringref !== '') {
        $result->url .= '&page='.urlencode($document->stringref);
    }
    if ($document->groupid) {
        $result->url .= '&group='.$document->groupid;
    }
    if ($document->userid) {
        $result->url .= '&user='.$document->userid;
    }
    return $result;
}

/**
 * Indicates API features that the ouwiki supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function ouwiki_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_GROUPINGS: return true;
        case FEATURE_GROUPS: return true;
        case FEATURE_GROUPMEMBERSONLY: return true;
        case FEATURE_SHOW_DESCRIPTION: return true;
        default: return null;
    }
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in module settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function ouwiki_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get forum details
    $ouwiki = $DB->get_record('ouwiki', array('id' => $cm->instance));

    $countsql = "SELECT COUNT(1)
            FROM {ouwiki_versions} v
                INNER JOIN {ouwiki_pages} p ON p.id = v.pageid
                INNER JOIN {ouwiki_subwikis} s ON s.id = p.subwikiid
            WHERE v.userid = ? AND v.deletedat IS NULL AND s.wikiid = ?";

    $result = $type; // Default return value

    if ($ouwiki->completionedits) {
        $value = $ouwiki->completionedits <= $DB->get_field_sql($countsql, array($userid, $ouwiki->id));
          if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }
    if ($ouwiki->completionpages) {
        $value = $ouwiki->completionpages <=
            $DB->get_field_sql($countsql.
            ' AND (SELECT MIN(id)
                FROM {ouwiki_versions}
                WHERE pageid = p.id AND deletedat IS NULL) = v.id',
                array($userid, $ouwiki->id));
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    return $result;
}

/**
 * This function prints the recent activity (since current user's last login)
 * for specified courses.
 * @param array $courses Array of courses to print activity for.
 * @param string by reference $htmlarray Array of html snippets for display some
 *        -where, which this function adds its new html to.
 */
function ouwiki_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$wikis = get_all_instances_in_courses('ouwiki', $courses)) {
        return;
    }

    // get all ouwiki logs in ONE query (much better!)
    $params = array();
    $sql = 'SELECT instance, cmid, l.course, COUNT(l.id) as count
                FROM {log} l
                JOIN {course_modules} cm ON cm.id = cmid
            WHERE (';

    foreach ($courses as $course) {
        $params[] = $course->id;
        $params[] = $course->lastaccess;
        $sql .= '(l.course = ? AND l.time > ?) OR ';
    }
    $sql = substr($sql,0,-3); // take off the last OR

    $sql .= ") AND l.module = 'ouwiki' AND action = 'edit' "
        ." AND userid != ? GROUP BY cmid, l.course, instance";
    $params[] = $USER->id;

    try {
        $new = $DB->get_records_sql($sql, $params);
    } catch (Exception $e) {
        ouwiki_dberror($e);
    }

    $strwikis = get_string('modulename','ouwiki');
    $strnumrespsince1 = get_string('overviewnumentrysince1','ouwiki');
    $strnumrespsince = get_string('overviewnumentrysince','ouwiki');

    //Go through the list of all wikis build previously, and check whether
    //they have had any activity.
    foreach ($wikis as $wiki) {

        if (array_key_exists($wiki->id, $new) && !empty($new[$wiki->id])) {
            $count = $new[$wiki->id]->count;

            if( $count > 0 ){
                if( $count == 1 ){
                    $strresp = $strnumrespsince1;
                }else{
                    $strresp = $strnumrespsince;
                }

                $viewurl = new moodle_url('/mod/ouwiki/view.php', array('id' => $wiki->coursemodule));
                $str = '<div class="overview wiki"><div class="name">'.
                    $strwikis.': <a title="'.$strwikis.'" href="'.$viewurl.'">'.
                    $wiki->name.'</a></div>';
                $str .= '<div class="info">';
                $str .= $count.' '.$strresp;
                $str .= '</div></div>';

                if (!array_key_exists($wiki->course,$htmlarray)) {
                    $htmlarray[$wiki->course] = array();
                }
                if (!array_key_exists('wiki', $htmlarray[$wiki->course])) {
                    $htmlarray[$wiki->course]['wiki'] = ''; // initialize, avoid warnings
                }
                $htmlarray[$wiki->course]['wiki'] .= $str;
            }
        }
    }
}

/**
 * Returns summary information about what a user has done,
 * for user activity reports.
 * @param $course
 * @param $user
 * @param $mod
 * @param $wiki
 * @return object
 */
function ouwiki_user_outline($course, $user, $mod, $wiki) {
    global $DB;

    $result = NULL;
    $logsview = $DB->get_records_select('log', "userid = ? AND module = 'ouwiki'
        AND action = 'view' AND cmid = ?", array($user->id, $mod->id), "time ASC");
    $logsedit = $DB->get_records_select('log', "userid = ? AND module = 'ouwiki'
        AND action = 'edit' AND cmid = ?", array($user->id, $mod->id), "time ASC");
    if ($logsview) {
        $numviews = count($logsview);
        $lastlog = array_pop($logsview);
        $result = new object();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;
    }
    if ($logsedit) {
        if ($logsview) {
            $numviews = count($logsedit);
            $lastlog = array_pop($logsedit);
            $result->info .= ', and '.get_string('numedits', 'ouwiki', $numviews);
            $result->time = $lastlog->time > $result->time ? $lastlog->time : $result->time;
        } else {
            $numviews = count($logsedit);
            $lastlog = array_pop($logsedit);
            $result = new object();
            $result->info = get_string('numedits', 'ouwiki', $numviews);
            $result->time = $lastlog->time;
        }
    }
    return $result;
}

/**
 * Serves the ouwiki attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function ouwiki_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $fileareas = array('attachment', 'content');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $versionid = (int)array_shift($args);

    if (!$version = $DB->get_record('ouwiki_versions', array('id' => $versionid))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_ouwiki/$filearea/$versionid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    require_capability('mod/ouwiki:view', $context);

    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}

/**
 * File browsing support for ouwiki module.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance Representing an actual file or folder (null if not found
 * or cannot access)
 */
function ouwiki_get_file_info($browser, $areas, $course, $cm, $context, $filearea,
        $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    $fileareas = array('attachment', 'content');
    if (!in_array($filearea, $fileareas)) {
        return null;
    }
    if (!has_capability('mod/ouwiki:view', $context)) {
        return null;
    }
    if (!$pageid = $DB->get_field('ouwiki_versions', 'pageid',
            array('id' => $itemid), IGNORE_MISSING)) {
        return null;
    }
    if (!$subwikiid = $DB->get_field('ouwiki_pages', 'subwikiid',
            array('id' => $pageid), IGNORE_MISSING)) {
        return null;
    }
    $groupid = $DB->get_field('ouwiki_subwikis', 'groupid',
            array('id' => $subwikiid), IGNORE_MISSING);
    // Make sure groups allow this user to see this file
    if ($groupid) {
        if (groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS) {
            // Groups are being used
            if (!groups_group_exists($groupid)) {
                // Can't find group
                return null;
            }
            if (!has_capability('moodle/site:accessallgroups', $context) &&
                    !groups_is_member($groupid)) {
                return null;
            }
        }
    }
    $userid = $DB->get_field('ouwiki_subwikis', 'userid',
            array('id' => $subwikiid), IGNORE_MISSING);
    if ($userid) {
        if ($userid != $USER->id && !has_capability('mod/ouwiki:viewallindividuals', $context)) {
            if (has_capability('mod/ouwiki:viewgroupindividuals', $context)) {
                $params = array($course->id, $userid, $USER->id);
                $query = "
                FROM
                    {groups} gp
                    INNER JOIN {groups_members} gm ON gp.id = gm.groupid
                    INNER JOIN {groups_members} gms ON gp.id = gms.groupid
                WHERE
                    gp.courseid = ? AND gm.userid = ? AND gms.userid = ?";

                $count = $DB->count_records_sql("SELECT COUNT(1) $query", $params);
                if ($count == 0) {
                    return null;
                }
            } else {
                return null;
            }
        }
    }

    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;
    if (!($storedfile = $fs->get_file($context->id, 'mod_ouwiki', $filearea, $itemid,
            $filepath, $filename))) {
        return null;
    }

    $urlbase = $CFG->wwwroot . '/pluginfile.php';
    return new file_info_stored($browser, $context, $storedfile, $urlbase, $filearea,
            $itemid, true, true, false);
}

/**
 * Create grade item for given ouwiki
 *
 * @param object $ouwiki object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function ouwiki_grade_item_update($ouwiki, $grades = null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname' => $ouwiki->name);

    if ($ouwiki->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $ouwiki->grade;
        $params['grademin']  = 0;

    } else if ($ouwiki->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$ouwiki->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/ouwiki', $ouwiki->course, 'mod',
        'ouwiki', $ouwiki->id, 0, $grades, $params);
}

/**
 * Deletes grade item for given ouwiki.
 *
 * @param object $ouwiki object
 * @return int GRADE_UPDATE_xx constant
 */
function ouwiki_grade_item_delete($ouwiki) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/ouwiki', $ouwiki->course, 'mod',
        'ouwiki', $ouwiki->id, 0, null, array('deleted' => 1));
}

/**
 * Sets the module uservisible to false if the user has not got the view capability
 * @param cm_info $cm
 */
function ouwiki_cm_info_dynamic(cm_info $cm) {
    if (!has_capability('mod/ouwiki:view',
            get_context_instance(CONTEXT_MODULE,$cm->id))) {
        $cm->uservisible = false;
        $cm->set_available(false);
    }
}

function ouwiki_cron() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/ouwiki/mod_ouwiki_cron.php');

    try {
        mod_ouwiki_cron::cron();
    } catch (moodle_exception $e) {
        mtrace("An ouwiki exception occurred and ouwiki cron was aborted: " .
                $e->getMessage() . "\n\n" .
                $e->debuginfo . "\n\n" .
                $e->getTraceAsString()."\n\n");
    }
}
