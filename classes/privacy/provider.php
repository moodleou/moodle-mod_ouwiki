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
 *
 * @package    mod_ouwiki
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ouwiki\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use context;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

/**
 * Data provider class.
 *
 * @package    mod_ouwiki
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\user_preference_provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table('ouwiki_annotations', [
                'userid' => 'privacy:metadata:ouwiki_annotations:userid',
                'content' => 'privacy:metadata:ouwiki_annotations:content',
                'timemodified' => 'privacy:metadata:ouwiki_annotations:timemodified'
        ], 'privacy:metadata:ouwiki_annotations');

        $collection->add_database_table('ouwiki_subwikis', [
                'userid' => 'privacy:metadata:ouwiki_subwikis:userid',
                'groupid' => 'privacy:metadata:ouwiki_subwikis:groupid'
        ], 'privacy:metadata:ouwiki_subwikis');

        $collection->add_database_table('ouwiki_versions', [
                'userid' => 'privacy:metadata:ouwiki_versions:userid',
                'xhtml' => 'privacy:metadata:ouwiki_versions:xhtml',
                'timecreated' => 'privacy:metadata:ouwiki_versions:timecreated',
                'changestart' => 'privacy:metadata:ouwiki_versions:changestart',
                'changesize' => 'privacy:metadata:ouwiki_versions:changesize',
                'changeprevsize' => 'privacy:metadata:ouwiki_versions:changeprevsize',
                'deletedat' => 'privacy:metadata:ouwiki_versions:deletedat',
                'wordcount' => 'privacy:metadata:ouwiki_versions:wordcount',
                'previousversionid' => 'privacy:metadata:ouwiki_versions:previousversionid',
                'importversionid' => 'privacy:metadata:ouwiki_versions:importversionid'
        ], 'privacy:metadata:ouwiki_versions');

        $collection->add_database_table('ouwiki_locks', [
                'userid' => 'privacy:metadata:ouwiki_locks:userid',
                'sectionstart' => 'privacy:metadata:ouwiki_locks:sectionstart',
                'sectionsize' => 'privacy:metadata:ouwiki_locks:sectionsize',
                'lockedat' => 'privacy:metadata:ouwiki_locks:lockedat',
                'expiresat' => 'privacy:metadata:ouwiki_locks:expiresat',
                'seenat' => 'privacy:metadata:ouwiki_locks:seenat'
        ], 'privacy:metadata:ouwiki_locks');

        $collection->add_user_preference('ouwiki_hide_annotations',
                'privacy:metadata:preferences:ouwiki_hide_annotations');
        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');
        return $collection;
    }

    /**
     * Store all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @throws \coding_exception
     */
    public static function export_user_preferences(int $userid) {
        $prefvalue = get_user_preferences('ouwiki_hide_annotations', 0, $userid);
        $ouwikihideannotations = transform::yesno($prefvalue);
        if (0 !== $prefvalue) {
            writer::export_user_preference(
                    'mod_ouwiki',
                    'ouwiki_hide_annotations',
                    $ouwikihideannotations,
                    get_string('privacy:metadata:preferences:ouwiki_hide_annotations', 'mod_ouwiki')
            );
        }
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = 'SELECT DISTINCT ctx.id
                  FROM {modules} m
                  JOIN {course_modules} cm ON cm.module = m.id AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                  JOIN {ouwiki_subwikis} s ON cm.instance = s.wikiid
             LEFT JOIN {ouwiki_pages} p ON p.subwikiid = s.id
             LEFT JOIN {ouwiki_annotations} oa ON oa.pageid = p.id AND oa.userid = :userid3
             LEFT JOIN {ouwiki_versions} v ON v.pageid = p.id AND v.userid = :userid4
             LEFT JOIN {ouwiki_locks} l ON l.pageid = p.id AND l.userid = :userid5
                 WHERE s.userid = :userid1 OR oa.id IS NOT NULL OR v.id IS NOT NULL OR l.id IS NOT NULL';
        $contextlist->add_from_sql($sql, ['modname' => 'ouwiki', 'contextlevel' => CONTEXT_MODULE,
                'userid1' => $userid, 'userid2' => $userid,
                'userid3' => $userid, 'userid4' => $userid, 'userid5' => $userid]);
        return $contextlist;
    }

    /**
     * Add one subwiki to the export
     * Each page is added as related data because all pages in one subwiki share the same filearea
     *
     * @param context $context
     * @param array $subwiki
     */
    protected static function export_subwiki(context $context, $subwiki) {
        if (empty($subwiki)) {
            return;
        }
        $subwikiid = key($subwiki);
        $pages = $subwiki[$subwikiid]['pages'];
        $subwikititle = $subwikiid . ' ' . get_string('subwikis', 'mod_ouwiki');
        unset($subwiki[$subwikiid]['pages']);
        writer::with_context($context)->export_related_data([$subwikititle], $subwikititle, $subwiki[$subwikiid]);
        foreach ($pages as $page => $entry) {
            writer::with_context($context)->export_related_data([$subwikititle, $page], $page, $entry);
            if (!empty($entry['revisions'])) {
                foreach ($entry['revisions'] as $revision) {
                    writer::with_context($context)->export_area_files([$subwikititle, $page], 'mod_ouwiki',
                            'attachment', $revision['versionid']);
                    writer::with_context($context)->export_area_files([$subwikititle, $page], 'mod_ouwiki',
                            'content', $revision['versionid']);
                }
            }
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $sql = 'SELECT s.id AS subwikiid,
                           s.groupid AS subwikigroupid, s.userid AS subwikiuserid,
                           p.id AS pageid, p.title,
                           v.id AS versionid, v.xhtml, v.timecreated AS versiontimecreated, v.changestart,
                           v.changesize, v.changeprevsize, v.deletedat, v.previousversionid, v.importversionid, v.wordcount,
                           l.id AS lockid, l.sectionstart, l.lockedat, l.seenat, l.sectionstart, l.sectionsize, l.expiresat,
                           oa.id AS annotationid, oa.content AS annotationcontent, oa.timemodified AS annotationtimemodified
                      FROM {course_modules} cm
                      JOIN {ouwiki} w ON w.id = cm.instance
                      JOIN {ouwiki_subwikis} s ON cm.instance = s.wikiid
                 LEFT JOIN {ouwiki_pages} p ON p.subwikiid = s.id
                 LEFT JOIN {ouwiki_annotations} oa ON oa.pageid = p.id AND oa.userid = :user4
                 LEFT JOIN {ouwiki_versions} v ON v.pageid = p.id AND v.userid = :user5
                 LEFT JOIN {ouwiki_locks} l ON l.pageid = p.id AND l.userid = :user6
                     WHERE cm.id = :cmid AND (s.userid = :user1 OR oa.userid = :user2 OR v.userid = :user3 OR l.userid = :user7)
                  ORDER BY s.id, p.id, v.id';
            $rs = $DB->get_recordset_sql($sql, ['cmid' => $context->instanceid,
                    'user1' => $user->id, 'user2' => $user->id, 'user3' => $user->id, 'user4' => $user->id,
                    'user5' => $user->id, 'user6' => $user->id, 'user7' => $user->id,
                    'ctxid' => $context->id]);

            if (!$rs->current()) {
                $rs->close();
                continue;
            }
            $subwiki = [];
            foreach ($rs as $record) {
                if (!isset($subwiki[$record->subwikiid])) {
                    self::export_subwiki($context, $subwiki);
                    $subwiki = [$record->subwikiid => [
                            'groupid' => $record->subwikigroupid,
                            'userid' => self::you_or_somebody_else($record->subwikiuserid, $user),
                            'pages' => []
                    ]];
                }
                if (!$record->pageid) {
                    continue;
                }

                $pagetitle = format_string($record->title, true, ['context' => $context]);
                // Start page has empty title.
                if (empty($pagetitle)) {
                    $pagetitle = get_string('startpage', 'mod_ouwiki');
                }
                $page = $record->pageid . ' ' . $pagetitle;

                if (!isset($subwiki[$record->subwikiid]['pages'][$page])) {
                    // Export basic details about the page.
                    $subwiki[$record->subwikiid]['pages'][$page] = ['page' => [
                            'id' => $record->pageid,
                            'title' => $pagetitle
                    ]];
                }
                // Process revisions.
                if ($record->versionid) {
                    $record->xhtml = writer::with_context($context)->rewrite_pluginfile_urls([],
                            'mod_ouwiki', 'content', $record->versionid, $record->xhtml);
                    // Export basic details about revisions of the page.
                    $subwiki[$record->subwikiid]['pages'][$page]['revisions'][$record->versionid] = [
                            'content' => $record->xhtml,
                            'versionid' => $record->versionid,
                            'timecreated' => transform::datetime($record->versiontimecreated),
                            'changestart' => $record->changestart,
                            'changesize' => $record->changesize,
                            'changeprevsize' => $record->changeprevsize,
                            'deletedat' => $record->deletedat ? transform::datetime($record->deletedat) : $record->deletedat,
                            'wordcount' => $record->wordcount,
                            'previousversionid' => $record->previousversionid,
                            'importversionid' => $record->importversionid
                    ];
                }
                // Process lock.
                if ($record->lockid) {
                    $subwiki[$record->subwikiid]['pages'][$page]['locks'][$record->lockid] = [
                            'lockedate' => transform::datetime($record->lockedat),
                            'seenat' => $record->seenat ? transform::datetime($record->seenat) : $record->seenat,
                            'expiresat' => $record->expiresat ? transform::datetime($record->expiresat) : $record->expiresat,
                            'sectionstart' => $record->sectionstart,
                            'sectionsize' => $record->sectionsize
                    ];
                }
                // Process annotation.
                if ($record->annotationid) {
                    $subwiki[$record->subwikiid]['pages'][$page]['annotations'][$record->annotationid] = [
                            'content' => $record->annotationcontent,
                            'timemodified' => transform::datetime($record->annotationtimemodified)
                    ];
                }
            }
            if (count($subwiki)) {
                self::export_subwiki($context, $subwiki);
                // Export wiki itself.
                $contextdata = helper::get_context_data($context, $user);
                helper::export_context_files($context, $user);
                writer::with_context($context)->export_data([], $contextdata);
            }
            $rs->close();
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $sql = 'SELECT s.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.name = :modname AND cm.module = m.id
                  JOIN {ouwiki_subwikis} s ON s.wikiid = cm.instance
                 WHERE cm.id = :cmid';
        $subwikis = $DB->get_fieldset_sql($sql, ['cmid' => $context->instanceid, 'modname' => 'ouwiki']);
        if (!$subwikis) {
            return;
        }
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_ouwiki', 'attachment');
        $fs->delete_area_files($context->id, 'mod_ouwiki', 'content');

        list($sql, $params) = $DB->get_in_or_equal($subwikis);
        $DB->delete_records_select('ouwiki_locks', 'pageid IN (SELECT id FROM {ouwiki_pages} WHERE subwikiid ' . $sql . ')',
                $params);
        $DB->delete_records_select('ouwiki_versions', 'pageid IN (SELECT id FROM {ouwiki_pages} WHERE subwikiid ' . $sql . ')',
                $params);
        $DB->delete_records_select('ouwiki_annotations', 'pageid IN (SELECT id FROM {ouwiki_pages} WHERE subwikiid ' . $sql . ')',
                $params);
        $DB->delete_records_select('ouwiki_sections', 'pageid IN (SELECT id FROM {ouwiki_pages} WHERE subwikiid ' . $sql . ')',
                $params);
        $DB->delete_records_select('ouwiki_links', 'topageid IN (SELECT id FROM {ouwiki_pages} WHERE subwikiid ' . $sql . ')',
                $params);
        $DB->delete_records_select('ouwiki_pages', 'subwikiid ' . $sql, $params);
        $DB->delete_records_select('ouwiki_subwikis', 'id ' . $sql, $params);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $contextids = $contextlist->get_contextids();
        if (!$contextids) {
            return;
        }

        self::delete_individual_user_data($contextlist);
        self::process_population_user_data($contextlist);
    }

    /**
     * Delete ouwiki individual data.
     *
     * @param approved_contextlist $contextlist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_individual_user_data(approved_contextlist $contextlist) {
        global $DB;

        $contextids = $contextlist->get_contextids();
        list($ctxsql, $ctxparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);

        $sql = 'SELECT v.id AS versionid, p.id AS pageid,  ctx.id AS ctxid, s.id AS subwikid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.name = :modname AND cm.module = m.id
                  JOIN {ouwiki} w ON w.id = cm.instance AND w.subwikis = :subwikis
                  JOIN {ouwiki_subwikis} s ON s.wikiid = cm.instance
             LEFT JOIN {ouwiki_pages} p ON p.subwikiid = s.id
             LEFT JOIN {ouwiki_versions} v ON v.pageid = p.id AND v.userid = :userid1
                 WHERE s.userid = :userid2 OR v.id IS NOT NULL AND ctx.id ' . $ctxsql;

        $userid = $contextlist->get_user()->id;
        $ouwikis = $DB->get_records_sql($sql, [
                        'userid1' => $userid,
                        'userid2' => $userid,
                        'modname' => 'ouwiki',
                        'contextlevel' => CONTEXT_MODULE,
                        'subwikis' => OUWIKI_SUBWIKIS_INDIVIDUAL] + $ctxparams);

        if ($ouwikis) {
            $fs = get_file_storage();
            foreach ($ouwikis as $ouwiki) {
                $fs->delete_area_files($ouwiki->ctxid, 'mod_ouwiki', 'attachment', $ouwiki->versionid);
                $fs->delete_area_files($ouwiki->ctxid, 'mod_ouwiki', 'content', $ouwiki->versionid);
            }

            list($pagesql, $paramspageid) = $DB->get_in_or_equal(array_column($ouwikis, 'pageid'), SQL_PARAMS_NAMED);
            // Delete all related data.
            $DB->delete_records_select('ouwiki_locks', 'pageid ' . $pagesql, $paramspageid);
            $DB->delete_records_select('ouwiki_versions', 'pageid ' . $pagesql, $paramspageid);
            $DB->delete_records_select('ouwiki_annotations', 'pageid ' . $pagesql, $paramspageid);
            $DB->delete_records_select('ouwiki_sections', 'pageid ' . $pagesql, $paramspageid);
            $DB->delete_records_select('ouwiki_links', 'topageid ' . $pagesql, $paramspageid);
            // Delete individual subwikis from this context..
            list($subwikisql, $paramssubwiki) = $DB->get_in_or_equal(array_column($ouwikis, 'subwikid'), SQL_PARAMS_NAMED);
            $paramssubwiki['userid'] = $userid;
            $DB->delete_records_select('ouwiki_pages',
                    'subwikiid IN (SELECT id FROM {ouwiki_subwikis} WHERE userid = :userid  AND id ' . $subwikisql . ')',
                    $paramssubwiki);
            $DB->delete_records_select('ouwiki_subwikis', 'userid = :userid AND id ' . $subwikisql, $paramssubwiki);
        }
    }

    /**
     * Process ouwiki population data.
     *
     * @param approved_contextlist $contextlist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function process_population_user_data(approved_contextlist $contextlist) {
        global $DB;

        $contextids = $contextlist->get_contextids();
        list($ctxsql, $ctxparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        list($subwikissql, $subwikisparams) = $DB->get_in_or_equal([OUWIKI_SUBWIKIS_SINGLE,
                OUWIKI_SUBWIKIS_GROUPS], SQL_PARAMS_NAMED);

        $sql = 'SELECT v.id AS versionid, v.xhtml, p.id AS pageid,  ctx.id AS ctxid, s.id AS subwikid, w.course as courseid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.name = :modname AND cm.module = m.id
                  JOIN {ouwiki} w ON w.id = cm.instance
                  JOIN {ouwiki_subwikis} s ON s.wikiid = cm.instance
             LEFT JOIN {ouwiki_pages} p ON p.subwikiid = s.id
             LEFT JOIN {ouwiki_versions} v ON v.pageid = p.id AND v.userid = :userid1
                 WHERE s.userid = :userid2 OR v.id IS NOT NULL AND ctx.id ' . $ctxsql . ' AND w.subwikis ' . $subwikissql;

        $userid = $contextlist->get_user()->id;
        $ouwikis = $DB->get_records_sql($sql, [
                        'userid1' => $userid,
                        'userid2' => $userid,
                        'modname' => 'ouwiki',
                        'contextlevel' => CONTEXT_MODULE,
                        'subwikis' => OUWIKI_SUBWIKIS_INDIVIDUAL] + $ctxparams + $subwikisparams);
        if ($ouwikis) {
            $user = $contextlist->get_user();
            // Update page content that related to user request delete.
            self::update_xhmtl_content_user_request_delete($user);
            list($pagesql, $paramspageid) = $DB->get_in_or_equal(array_column($ouwikis, 'pageid'), SQL_PARAMS_NAMED);
            // Delete unnecessary data.
            $DB->delete_records_select('ouwiki_locks', 'pageid ' . $pagesql, $paramspageid);
            // Set user to admin to other data.
            $adminid = get_admin()->id;
            // Process files.
            $select = 'userid = :userid AND component = :component AND filearea IN (:attachment, :content)';
            $params = [
                    'userid' => $userid,
                    'component' => 'mod_ouwiki',
                    'attachment' => 'attachment',
                    'content' => 'content'];
            $DB->set_field_select('files', 'userid', $adminid, $select, $params);
            // Process tables.
            $DB->set_field_select('ouwiki_subwikis', 'userid', $adminid, 'userid = :userid', [
                    'userid' => $userid]);

            $sql = 'UPDATE {ouwiki_versions} SET userid = :adminid WHERE userid = :userid AND pageid ' . $pagesql;
            $DB->execute($sql, [
                            'adminid' => $adminid,
                            'userid' => $userid] + $paramspageid);

            $sql = 'UPDATE {ouwiki_annotations} SET userid = :adminid, content = :content WHERE userid = :userid AND pageid ' .
                    $pagesql;
            $DB->execute($sql, [
                            'adminid' => $adminid,
                            'content' => get_string('privacy:annotationdeleted', 'mod_ouwiki'),
                            'userid' => $userid] + $paramspageid);
        }
    }

    /**
     * Removes personally-identifiable data from a user id for export.
     *
     * @param int $userid User id of a person
     * @param \stdClass $user Object representing current user being considered
     * @return string 'You' if the two users match, 'Somebody else' otherwise
     * @throws \coding_exception
     */
    protected static function you_or_somebody_else($userid, $user) {
        if ($userid == $user->id) {
            return get_string('privacy_you', 'mod_ouwiki');
        } else {
            return get_string('privacy_somebodyelse', 'mod_ouwiki');
        }
    }

    /**
     * Update content version xhtml for user related to request delete.
     *
     * @param $user
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function update_xhmtl_content_user_request_delete($user) {
        global $DB, $CFG;
        $url = $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&';
        $sql = 'SELECT id, xhtml
                  FROM {ouwiki_versions}
                 WHERE xhtml LIKE :url';
        $versions = $DB->get_records_sql($sql, ['url' => "%{$url}%"]);
        if (! $versions) {
            return;
        }
        $patern = '/<a href="' . str_replace(['/', '?', '.'], ['\/', '\?', '\.'], $url) . '(.*)>(.*)<\/a>/U';
        foreach ($versions as $version) {
            $version->xhtml = preg_replace($patern, get_string('privacy:xhtmlcontentdeleted', 'mod_ouwiki'), $version->xhtml);
            $DB->update_record('ouwiki_versions', $version);
        }
    }
}
