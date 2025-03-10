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
    $context = context_module::instance($cmid);

    if ($formdata = $data) {

        $formdata->timemodified = time();

        // Set up null values
        $nullvalues = array('editbegin', 'editend', 'timeout');
        foreach ($nullvalues as $nullvalue) {
            if (empty($formdata->{$nullvalue})) {
                unset($formdata->{$nullvalue});
            }
        }

        if (strlen(preg_replace('/(<.*?>)|(&.*?;)|\s/', '', $formdata->intro)) == 0) {
            $formdata->intro = null;
        }

        // Create record
        $ouwikiid = $DB->insert_record('ouwiki', $formdata);
        $formdata->id = $ouwikiid;

        ouwiki_grade_item_update($formdata);

        // template file save
        $fs = get_file_storage();
        if (isset($mform) && $filename = $mform->get_new_filename('template_file')) {
            $file = $mform->save_stored_file('template_file', $context->id, 'mod_ouwiki', 'template', $ouwikiid, '/', $filename);
            $DB->set_field('ouwiki', 'template', '/'.$file->get_filename(), array('id' => $formdata->id));
        }

        // Update completion event in calendar.
        $completionexpected = (!empty($formdata->completionexpected)) ? $formdata->completionexpected : null;
        \core_completion\api::update_completion_date_event($cmid, 'ouwiki', $ouwikiid, $completionexpected);

        return $ouwikiid;
    }
    // Note: template files will be stored based on the old data structure.
}

function ouwiki_update_instance($data, $mform) {
    global $CFG, $DB;

    $data->id = $data->instance;
    $data->timemodified = time();


    if ($data->lockstartpages) {
        // Find current value of 'lock start pages' setting
        $lockstartpages = $DB->get_field('ouwiki', 'lockstartpages', array('id' => $data->id));
    }

    // Update main record.
    $DB->update_record('ouwiki', $data);

    // Lock all start pages if the 'lock start pages' setting has been activated
    if ($data->lockstartpages && isset($lockstartpages) && !$lockstartpages) {
        $sql = "UPDATE {ouwiki_pages}
            SET locked = 1
            WHERE locked = 0
            AND title = ''
            AND subwikiid IN (SELECT id FROM {ouwiki_subwikis} WHERE wikiid = ?)";

        $DB->execute($sql, array($data->id));
    }

    // Set up null values
    $nullvalues = array('editbegin', 'editend', 'timeout');
    foreach ($nullvalues as $nullvalue) {
        if (empty($data->{$nullvalue})) {
            unset($data->{$nullvalue});
            $DB->set_field('ouwiki', $nullvalue, null, array('id' => $data->id));
        }
    }
    if (strlen(preg_replace('/(<.*?>)|(&.*?;)|\s/', '', $data->intro)) == 0) {
        $data->intro = null;
        $DB->set_field('ouwiki', 'intro', null, array('id' => $data->id));
    }

    ouwiki_grade_item_update($data);

    if (!$cm = get_coursemodule_from_id('ouwiki', $data->coursemodule)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    // Checking course instance.
    $course = $DB->get_record('course', array('id' => $data->course), '*', MUST_EXIST);

    if ($filename = $mform->get_new_filename('template_file')) {
        // Delete any previous template files.
        $cmid = $data->coursemodule;
        $context = context_module::instance($cmid);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_ouwiki', 'template', $data->id);
        // Add template file.
        $file = $mform->save_stored_file('template_file', $context->id, 'mod_ouwiki', 'template', $data->id, '/', $filename);
        $DB->set_field('ouwiki', 'template', '/'.$file->get_filename(), array('id' => $data->id));
        // Check for empty wikis (i.e. wikis without a start page already created).
        $subwikis = ouwiki_get_subwikis($data->id);
        $ouwiki = $DB->get_record_select('ouwiki', 'id = ?', array($data->id));
        foreach ($subwikis as $subwiki) {
            if (!ouwiki_subwiki_content_exists($subwiki->id)) {
                // Amend any empty wikis from template.
                ouwiki_init_pages($course, $cm, $ouwiki, $subwiki);
            }
        }
    }

    // Update completion event in calendar.
    $completionexpected = (!empty($data->completionexpected)) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'ouwiki', $data->id, $completionexpected);

    return true;
}

function ouwiki_delete_instance($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

    $cm = get_coursemodule_from_instance('ouwiki', $id, 0, false, MUST_EXIST);

    // Delete associated template data.
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_ouwiki', 'template', $id);

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

    // Delete event in calendar when deleting activity.
    \core_completion\api::update_completion_date_event($cm->id, 'ouwiki', $id, null);

    return true;
}

/**
 * @return array List of all system capabilitiess used in module
 */
function ouwiki_get_extra_capabilities() {
    // Note: I made this list by searching for moodle/ within the module
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames',
            'moodle/course:manageactivities', 'report/restrictuser:view',
            'report/restrictuser:restrict', 'report/restrictuser:removerestrict');
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
        case FEATURE_SHOW_DESCRIPTION: return true;
        case FEATURE_MOD_PURPOSE: return MOD_PURPOSE_COLLABORATION;
        default: return null;
    }
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
    $sql = substr($sql, 0, -3); // take off the last OR

    $sql .= ") AND l.module = 'ouwiki' AND action = 'edit' "
        ." AND userid != ? GROUP BY cmid, l.course, instance";
    $params[] = $USER->id;

    try {
        $new = $DB->get_records_sql($sql, $params);
    } catch (Exception $e) {
        ouwiki_dberror($e);
    }

    $strwikis = get_string('modulename', 'ouwiki');
    $strnumrespsince1 = get_string('overviewnumentrysince1', 'ouwiki');
    $strnumrespsince = get_string('overviewnumentrysince', 'ouwiki');

    // Go through the list of all wikis build previously, and check whether
    // they have had any activity.
    foreach ($wikis as $wiki) {

        if (array_key_exists($wiki->id, $new) && !empty($new[$wiki->id])) {
            $count = $new[$wiki->id]->count;

            if ($count > 0) {
                if ($count == 1) {
                    $strresp = $strnumrespsince1;
                } else {
                    $strresp = $strnumrespsince;
                }

                $viewurl = new moodle_url('/mod/ouwiki/view.php', array('id' => $wiki->coursemodule));
                $str = '<div class="overview wiki"><div class="name">'.
                    $strwikis.': <a title="'.$strwikis.'" href="'.$viewurl.'">'.
                    $wiki->name.'</a></div>';
                $str .= '<div class="info">';
                $str .= $count.' '.$strresp;
                $str .= '</div></div>';

                if (!array_key_exists($wiki->course, $htmlarray)) {
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
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $wiki
 * @return stdClass A standard object with 2 variables: info (number of edits for this user) and time (last modified)
 */
function ouwiki_user_outline($course, $user, $mod, $wiki) {
    global $DB, $CFG;

    // Get user grades.
    require_once("$CFG->libdir/gradelib.php");
    $grades = grade_get_grades($course->id, 'mod', 'ouwiki', $wiki->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
        if ($grade->str_grade == '-') {
            $grade = false;
        }
    }

    // Get user edits.
    $params = array(
        'userid' => $user->id,
        'ouwikiid' => $wiki->id
    );

    $vsql = "SELECT v.id AS versionid, v.timecreated
               FROM {ouwiki_pages} p
               JOIN {ouwiki_subwikis} s ON s.id = p.subwikiid
               JOIN {ouwiki_versions} v ON v.pageid = p.id
              WHERE v.userid = :userid
                AND s.wikiid = :ouwikiid
                AND v.deletedat IS NULL
           ORDER BY v.timecreated ASC";
    $versions = $DB->get_records_sql($vsql, $params);

    $result = null;

    if (!empty($versions)) {
        $result = new stdClass();
        $result->info = get_string('numedits', 'ouwiki', count($versions));

        if ($grade) {
            $result->info .= ', ' . get_string('gradenoun') . ': ' . $grade->str_long_grade;
        }

        $timecreated = end($versions)->timecreated;

        $result->time = $timecreated;
    } else if ($grade) {
        $result = new stdClass();
        $result->info = get_string('gradenoun') . ': ' . $grade->str_long_grade;
        // If grade was last modified by the user themselves use date graded. Otherwise use date submitted.
        if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
            $result->time = $grade->dategraded;
        } else {
            $result->time = $grade->datesubmitted;
        }
    }

    return $result;
}

/**
 * Prints detailed summary information about what a user has done,
 * for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $wiki
 */
function ouwiki_user_complete($course, $user, $mod, $wiki) {
    global $DB, $CFG, $OUTPUT, $USER, $PAGE;

    require_once("$CFG->libdir/gradelib.php");
    require_once("$CFG->dirroot/mod/ouwiki/locallib.php");

    $grades = grade_get_grades($course->id, 'mod', 'ouwiki', $wiki->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        if ($grade != '-') {
            echo $OUTPUT->container(get_string('gradenoun') . ': ' . $grade->str_long_grade);
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback') . ': ' . $grade->str_feedback);
            }
        }
    }

    $usergroups = array();
    if ($wiki->subwikis == 1) {
        $wikigroups = groups_get_activity_allowed_groups($mod);
        foreach ($wikigroups as $mygroup) {
            if (groups_is_member($mygroup->id, $user->id)) {
                $usergroups[] = $mygroup;
            }
        }
    }

    $context = context_module::instance($mod->id);
    $fullname = fullname($user, has_capability('moodle/site:viewfullnames', $context));

    $ouwikioutput = $PAGE->get_renderer('mod_ouwiki');
    echo '<div class="ouwiki_user_complete_report">';
    if (empty($usergroups)) {
        $subwiki = ouwiki_get_subwiki($course, $wiki, $mod, $context, 0, $user->id, true);
        $canview = ouwiki_can_view_participation($course, $wiki, $subwiki, $mod, $USER->id);
        list($newuser, $changes) = ouwiki_get_user_participation($user->id, $subwiki);
        echo $ouwikioutput->ouwiki_render_user_participation($user, $changes, $mod, $course, $wiki,
            $subwiki, '', 0, '', $canview, $context, $fullname,
            false, '');
    } else {
        foreach ($usergroups as $group) {
            $subwiki = ouwiki_get_subwiki($course, $wiki, $mod, $context, $group->id, $user->id, true);
            $canview = ouwiki_can_view_participation($course, $wiki, $subwiki, $mod, $USER->id);
            list($newuser, $changes) = ouwiki_get_user_participation($user->id, $subwiki);
            echo $OUTPUT->heading(get_string('group') . ': ' . $group->name, 5);
            echo $ouwikioutput->ouwiki_render_user_participation($user, $changes, $mod, $course, $wiki,
                $subwiki, '', $group->id, '', $canview, $context, $fullname,
                false, $group->name);
        }
    }
    echo '</div>';
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
            context_module::instance($cm->id))) {
        $cm->set_user_visible(false);
        $cm->set_available(false);
    }
}

/**
 * Show last updated date + time (version created).
 *
 * @param cm_info $cm
 */
function ouwiki_cm_info_view(cm_info $cm) {
    global $CFG;
    if (!$cm->uservisible) {
        return;
    }
    require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

    $lastpostdate = ouwiki_get_last_modified($cm, $cm->get_course());
    if (!empty($lastpostdate)) {
        $cm->set_after_link(html_writer::span(get_string('lastmodified', 'ouwiki',
                userdate($lastpostdate, get_string('strftimerecent', 'ouwiki'))), 'lastmodtext ouwikilmt'));
    }
}

/**
 * Return wikis on course that have last modified date for current user
 *
 * @param stdClass $course
 * @return array
 */
function ouwiki_get_ourecent_activity($course) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

    $modinfo = get_fast_modinfo($course);

    $return = array();

    foreach ($modinfo->get_instances_of('ouwiki') as $wiki) {
        if ($wiki->uservisible) {
            $lastpostdate = ouwiki_get_last_modified($wiki, $wiki->get_course());
            if (!empty($lastpostdate)) {
                $data = new stdClass();
                $data->cm = $wiki;
                $data->text = get_string('lastmodified', 'ouwiki',
                        userdate($lastpostdate, get_string('strftimerecent', 'ouwiki')));
                $data->date = $lastpostdate;
                $return[$data->cm->id] = $data;
            }
        }
    }
    return $return;
}


/**
 * List of view style log actions
 * @return array
 */
function ouwiki_get_view_actions() {
    return ['view', 'view all', 'viewold', 'wikihistory', 'wikiindex', 'history',
            'entirewiki'];
}

/**
 * List of update style log actions
 * @return array
 */
function ouwiki_get_post_actions() {
    return array('update', 'add', 'annotate', 'edit');
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 */
function ouwiki_get_coursemodule_info($coursemodule) {
    global $DB;
    $ouwiki = $DB->get_record('ouwiki',
            ['id' => $coursemodule->instance], 'id, name, completionpages, completionedits, intro, introformat');
    if (!$ouwiki) {
        return null;
    }

    $info = new cached_cm_info();
    $info->customdata = (object)[];

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata->customcompletionrules['completionpages'] = $ouwiki->completionpages;
        $info->customdata->customcompletionrules['completionedits'] = $ouwiki->completionedits;
    }

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('ouwiki', $ouwiki, $coursemodule->id, false);
    }

    return $info;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_ouwiki_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory, int $userid = 0) {
    global $USER;
    if (!$userid) {
        $userid = $USER->id;
    }
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['ouwiki'][$event->instance];
    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }
    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);
    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }
    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/ouwiki/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
