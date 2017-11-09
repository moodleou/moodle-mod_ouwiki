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
 * Search area class for document page versions
 *
 * @package mod_ouwiki
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ouwiki\search;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

/**
 * Search area class for document page versions
 *
 * @package mod_ouwiki
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_version extends \core_search\base_mod {

    /**
     * File areas related to document
     *
     * List all file areas in files table
     */
    const FILEAREA = [
        'ATTACHMENT' => 'attachment',
        'CONTENT' => 'content',
    ];

    /** @var array Relevant context levels (module context) */
    protected static $levels = [CONTEXT_MODULE];

    /**
     * Returns recordset containing required data for indexing ouwiki page versions.
     *
     * @param int $modifiedfrom
     * @return \moodle_recordset
     */
    public function get_recordset_by_timestamp($modifiedfrom = 0) {
        global $DB;
        // Note: Ideally in this query we would also join with the ouwiki_subwikis and ouwiki tables
        // to get the additional information from those tables (wiki name, course id, etc). However,
        // doing this made the query infeasibly slow when run on OU acct data. I've moved this part
        // out (along with the XHTML since that might be large) into an extra db query in
        // get_document.
        $querystring = '
            SELECT owv.timecreated as versionmodified, owv.id as ouwikiversionid, owv.userid,
                   owp.title, owp.subwikiid
              FROM {ouwiki_versions} owv
              JOIN {ouwiki_pages} owp ON owv.pageid = owp.id AND owp.currentversionid = owv.id
             WHERE owv.timecreated >= ?
          ORDER BY owv.timecreated';

        return $DB->get_recordset_sql($querystring, array($modifiedfrom));
    }

    /**
     * Returns the document associated with this page version id.
     *
     * @param \stdClass $record
     * @param array $options
     * @return bool|\core_search\document
     */
    public function get_document($record, $options = array()) {
        global $DB;

        try {
            // Get additional data that used to be obtained in the main query (for performance).
            $moredata = $DB->get_record_sql("
                    SELECT ow.course, ow.id, ow.name as ouwikiname, owv.xhtml
                      FROM {ouwiki_subwikis} owsw
                      JOIN {ouwiki} ow ON ow.id = owsw.wikiid
                      JOIN {ouwiki_versions} owv ON owv.id = ?
                     WHERE owsw.id = ?", [$record->ouwikiversionid, $record->subwikiid], MUST_EXIST);

            $cm = get_coursemodule_from_instance($this->get_module_name(), $moredata->id, $moredata->course);
            $context = \context_module::instance($cm->id);
        } catch (\dml_exception $ex) {
            // Don't throw an exception, apparently it might upset the search process.
            debugging('Error retrieving ' . $this->areaid . ' ' . $record->ouwikiversionid .
                    ' document: ' . $ex->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        // Construct the document instance to return.
        $doc = \core_search\document_factory::instance(
                $record->ouwikiversionid, $this->componentname, $this->areaname);

        // Set document title.
        // By default, document title will be page title.
        $title = $record->title;
        if (empty($title)) {
            $title = $moredata->ouwikiname;
        }
        $doc->set('title', content_to_text($title, false));

        // Document content.
        $content = $moredata->xhtml;
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, $this->componentname,
            self::FILEAREA['CONTENT'], $record->ouwikiversionid);
        $doc->set('content', content_to_text($content, FORMAT_HTML));

        // Set other search metadata.
        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', $moredata->course);
        $doc->set('modified', $record->versionmodified);
        $doc->set('itemid', $record->ouwikiversionid);
        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);

        // Sometimes userid is not set for pages that were system-generated.
        if ($record->userid) {
            $doc->set('userid', $record->userid);
        }

        // Set optional 'new' flag.
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $record->versionmodified)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }

        return $doc;
    }

    /**
     * Whether the user can access the document or not.
     *
     * @param int $id Post ID
     * @return int
     */
    public function check_access($id) {
        global $USER;

        // Get wiki page instance.
        $version = $this->get_version($id);

        if (!empty($version)) {
            $cm = get_coursemodule_from_instance($this->get_module_name(), $version->ouwikiinstanceid,
                $version->course);
            $context = \context_module::instance($cm->id);
        } else {
            // This activity instance was deleted.
            return \core_search\manager::ACCESS_DELETED;
        }

        // Does not allow to search old versions.
        if ($version->currentversionid != $version->ouwikiversionid) {
            return \core_search\manager::ACCESS_DELETED;
        }

        // Check whether user has view capability.
        if (!has_capability('mod/ouwiki:view', $context)) {
            return \core_search\manager::ACCESS_DENIED;
        }

        // Check sub-wiki accessibility.
        switch($version->subwikis) {
            case OUWIKI_SUBWIKIS_SINGLE:
                break;

            case OUWIKI_SUBWIKIS_GROUPS:
                $groupmode = groups_get_activity_groupmode($cm);
                if ($groupmode == SEPARATEGROUPS) {
                    if (!groups_is_member($version->groupid) &&
                        !has_capability('moodle/site:accessallgroups', $context)) {
                        return \core_search\manager::ACCESS_DENIED;
                    }
                }
                break;

            case OUWIKI_SUBWIKIS_INDIVIDUAL:
                if ($version->userid != $USER->id) {
                    // Is user allowed to view everybody?
                    if (!has_capability('mod/ouwiki:viewallindividuals', $context)) {
                        // Nope. Are they allowed to view people in same group?
                        if (!has_capability('mod/ouwiki:viewgroupindividuals', $context)) {
                            return \core_search\manager::ACCESS_DENIED;
                        }
                        // Check user is in same group. Note this isn't now restricted to the module grouping.
                        $ourgroups = groups_get_all_groups($cm->course, $USER->id);
                        $theirgroups = groups_get_all_groups($cm->course, $version->userid);
                        $found = false;
                        foreach ($ourgroups as $ourgroup) {
                            foreach ($theirgroups as $theirgroup) {
                                if ($ourgroup->id == $theirgroup->id) {
                                    $found = true;
                                    break;
                                }
                            }
                            if ($found) {
                                break;
                            }
                        }
                        if (!$found) {
                            return \core_search\manager::ACCESS_DENIED;
                        }
                    }
                }
                break;

            default:
                return \core_search\manager::ACCESS_DENIED;
        }

        return \core_search\manager::ACCESS_GRANTED;
    }

    /**
     * Link to the ouwiki page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_doc_url(\core_search\document $doc) {
        $versionid = preg_replace('~^.*?([0-9]+)$~', '$1', $doc->get('itemid'));
        $version = $this->get_version($versionid);

        $cm = get_coursemodule_from_instance($this->get_module_name(), $version->ouwikiinstanceid,
            $version->course);

        if (empty($version->title)) {
            return new \moodle_url('/mod/ouwiki/view.php', array('id' => $cm->id));
        } else {
            return new \moodle_url('/mod/ouwiki/view.php', array('id' => $cm->id,
                'page' => $version->title));
        }
    }

    /**
     * Link to the ouwiki page
     *
     * @param \core_search\document $doc Document instance returned by get_document function
     * @return \moodle_url
     */
    public function get_context_url(\core_search\document $doc) {
        return $this->get_doc_url($doc);
    }

    /**
     * Returns the module name.
     *
     * @return string
     */
    protected function get_module_name() {
        return substr($this->componentname, 4);
    }

    /**
     * Return version and some wiki information.
     *
     * @param int $versionid
     * @return \stdClass
     */
    private function get_version($versionid) {
        global $DB;

        $querystring = '
            SELECT owp.title, owp.id as ouwikipageid, owp.currentversionid,
                   owv.id as ouwikiversionid, owv.userid,
                   ow.course, ow.id as ouwikiinstanceid, ow.subwikis,
                   owsw.groupid
              FROM {ouwiki_versions} owv
              JOIN {ouwiki_pages} owp ON owv.pageid = owp.id
              JOIN {ouwiki_subwikis} owsw ON owsw.id = owp.subwikiid
              JOIN {ouwiki} ow ON ow.id = owsw.wikiid
             WHERE owv.id = ?';

        return $DB->get_record_sql($querystring, array($versionid));
    }

    /**
     * Returns true if this area uses file indexing.
     *
     * @return bool
     */
    public function uses_file_indexing() {
        return true;
    }

    /**
     * Add the attached description files.
     *
     * @param \core_search\document $document The current document
     * @return null
     */
    public function attach_files($document) {
        $fs = get_file_storage();
        $files = array();

        foreach (self::FILEAREA as $area) {
            $files = array_merge($files, $fs->get_area_files($document->get('contextid'), $this->componentname, $area,
                    $document->get('itemid'), 'sortorder DESC, id ASC', false));
        }

        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }
}
