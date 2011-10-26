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
 * Provide federated-search access to wiki. This facility searches all
 * wikis that a user has access to.
 *
 * Note: I don't think anyone has tested this in years and I bet it doesn't
 * work any more.
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/ousearch/searchlib.php');
require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

global $ouwiki_nologin;
$ouwiki_nologin = true;

// Security
ousearch_require_remote_access();

// Get basic details
$username = required_param('username', PARAM_RAW);
$query = required_param('query', PARAM_RAW);
$first = optional_param('first', 1, PARAM_INT);

// Locate user
$userid = $DB->get_field('user', 'id', array('username' => $username));
if (!$userid) {
    print_error('No such user '.$username);
}

/**
 * Obtain list of all the modules of a given type to which the specified user
 * has access to a particular capability. (Usually the 'view' capability but
 * could be others.)
 * @param string $modulename Name of module e.g. 'ouwiki'
 * @param string $capability Name of capability e.g. 'mod/ouwiki:view'
 * @param int $userid ID of user
 * @param string $extracheck Additional SQL check e.g. 'cm.groupmode<>0'
 * @return array Array of course-module ID=>object containing ->id and ->course
 */
function get_all_accessible_modules_of_type($modulename, $capability, $userid = 0, $extracheck = null) {
    global $CFG, $USER, $DB;

    // Prepare values for use in query
    if (!$userid) {
        $userid = $USER->id;
    }
    $capability = $capability;
    $modulename = $modulename;
    $userid = (int)$userid;
    $now = time();
    if ($extracheck) {
        $extracheck = 'AND cm.groupmode <> 0';
    }

    // This query obtains each permission for the given capability
    // that applies, keeping track of the contextlevel (note that override
    // permissions apply at the contextlevel of the role, not at the contextlevel
    // of the override, and luckily it's easier to get that number).

    // Must use get_recordset not get_records as cm.id is not unique in this query.
    $sql = "SELECT
                cm.id, cm.course, rc.permission, c2.contextlevel
            FROM
                {modules} m
                INNER JOIN {course_modules} cm ON m.id = cm.module
                INNER JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = 70
                INNER JOIN {context} c2 ON substring(c.path FOR char_length(c2.path)+1) = c2.path || '/'
                INNER JOIN {role_assignments} ra ON ra.contextid = c2.id
                INNER JOIN {role_capabilities} rc ON rc.roleid = ra.roleid
            WHERE
                m.name = ?

                AND ra.userid = ?
                AND ra.hidden = 0
                AND ra.timestart <= ?
                AND (ra.timeend = 0 OR ra.timeend > ?)

                AND rc.capability = ?
                AND (rc.contextid = 1 OR rc.contextid IN
                    (SELECT xc.id FROM {context} xc WHERE substring(xc.path FOR char_length(c2.path)+1) = c2.path || '/'))
                $extracheck
            ORDER BY
                cm.id");

    $rs = $DB->get_recordset_sql($sql, array($modulename, $userid, $now, $now, $capability));

    // Combine the permissions to build this into a yes/no list of coursemodules.
    // Note that permissions are combined in the following manner:
    // * CAP_PROHIBIT anywhere results in no permission
    // * Only permissions at the most-specific (highest) contextlevel apply
    // * Permissions at this highest level are added together. A result more than
    //   0 means permitted.
    $coursemodules = array();
    $current = null;
    foreach ($rs as $result) {
        if (!$current) {
            $current = $result;
        } else if ($result->id === $current->id) {
            // Combine permissions.

            // If it's already on probibit, forget it, it's gone
            if ($current->permission === CAP_PROHIBIT) {
                // Forget it, you can't un-prohibit
                continue;
            }
            if ($result->permission === CAP_PROHIBIT || $result->contextlevel > $current->contextlevel) {
                // If this row's permission is prohibit, or this row is more specific than before,
                // then set it to this
                $current->permission = $result->permission;
                $current->contextlevel = $result->contextlevel;
            } else if( $result->contextlevel === $current->contextlevel) {
                // If at the same level, permissions are added up
                $current->permission += $result->permission;
            }
            // Less specific contextlevels that weren't prohibit are ignored.
        } else {
            if ($current && $current->permission > 0) {
                $coursemodules[$current->id] = $current;
            }
            $current = $result;
        }
    }
    // Don't forget the last one
    if ($current && $current->permission > 0) {
        $coursemodules[$current->id] = $current;
    }
    $rs->close();

    return $coursemodules;
}

// Set up basic search with specified query
$search = new ousearch_search($query);

// Set up list of accessible groups and user ID
$groupids = array();
$rs = $DB->get_recordset_sql('SELECT gm.groupid FROM {groups_members} gm WHERE gm.userid = ?', array($userid));
foreach ($rs as $result) {
    $groupids[] = $result->id;
}
$rs->close();

$search->set_group_ids($groupids);
$search->set_user_id($userid);

// Get array of course-module info
$accessible = get_all_accessible_modules_of_type('ouwiki', 'mod/ouwiki:view', $userid);
$search->set_coursemodule_array($accessible);

// Get exceptions where user can access all groups
$allgroups = get_all_accessible_modules_of_type('ouwiki','moodle/site:accessallgroups', $userid, true);
$search->set_group_exceptions($allgroups);

$results = $search->query(0, OUWIKI_MAXRESULTS);
ousearch_display_remote_results($results, $first, OUWIKI_RESULTSPERPAGE);
