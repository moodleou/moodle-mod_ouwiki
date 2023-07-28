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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one ouwiki activity
 */
class restore_ouwiki_activity_structure_step extends restore_activity_structure_step {
    private $versions = array();
    private $processingversion = null;

    protected $elementsids; // Array to store last oldid and newid as a key/value pair used for each annotation.

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('ouwiki', '/activity/ouwiki');
        $paths[] = new restore_path_element('ouwiki_subwiki', '/activity/ouwiki/subs/subwiki');
        $paths[] = new restore_path_element('ouwiki_page', '/activity/ouwiki/subs/subwiki/pages/page');
        $paths[] = new restore_path_element('ouwiki_version', '/activity/ouwiki/subs/subwiki/pages/page/versions/version');
        $paths[] = new restore_path_element('ouwiki_annotation', '/activity/ouwiki/subs/subwiki/pages/page/annotations/annotation');
        $paths[] = new restore_path_element('ouwiki_link', '/activity/ouwiki/subs/subwiki/pages/page/versions/version/links/link');

        // Set annotation elements id array.
        $this->elementsids = array();
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_ouwiki($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->editbegin = $this->apply_date_offset($data->editbegin);
        $data->editend = $this->apply_date_offset($data->editend);

        if (!isset($data->enablewordcount)) {
            $data->enablewordcount = 1;
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        // insert the ouwiki record
        $newitemid = $DB->insert_record('ouwiki', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
        // Get OU Wiki id for later use.
        $this->ouwikiid = $newitemid;
        $this->set_mapping('ouwiki', $oldid, $newitemid);
    }

    protected function process_ouwiki_subwiki($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->wikiid = $this->get_new_parentid('ouwiki');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        // extra cleanup required - if values are 0 then set them to null
        if ($data->groupid == 0) {
            $data->groupid = null;
        }
        if ($data->userid == 0) {
            $data->userid = null;
        }

        $newitemid = $DB->insert_record('ouwiki_subwikis', $data);
        $this->set_mapping('ouwiki_subwiki', $oldid, $newitemid);
    }

    protected function process_ouwiki_page($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->subwikiid = $this->get_new_parentid('ouwiki_subwiki');

        $newitemid = $DB->insert_record('ouwiki_pages', $data);

        $this->set_mapping('ouwiki_page', $oldid, $newitemid);

        // Flush out any unsaved versions.
        $this->flush_versions();
    }

    protected function process_ouwiki_version($data) {
        global $CFG;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pageid = $this->get_new_parentid('ouwiki_page');

        if (!isset($data->wordcount)) {
            // calculate the wordcount if it doesn't exist
            require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');
            $wordcount = ouwiki_count_words($data->xhtml);
            $data->wordcount = $wordcount;
        }

        // Store the version in memory. We cannot write it out now because
        // they need to be in id order, but the stupid backup can be in random
        // order (Moodle backup doesn't let you sort it) and there is no way
        // to fix this before getting here...
        $this->versions[$oldid] = $data;

        // Store the current version in memory so we can store links inside
        // it from the process_ouwiki_link function.
        $data->links = array();
        $this->processingversion = $data;
    }

    /**
     * Saves out all versions currently in memory (if any), in id order. This
     * function should be called before each new page, so saving may be delayed,
     * but it shouldn't need to hold more than one page's worth of versions in
     * memory at once.
     */
    private function flush_versions() {
        global $DB;

        // Sort versions into id order.
        ksort($this->versions);

        // Loop through, saving each one.
        $transaction = $DB->start_delegated_transaction();
        foreach ($this->versions as $data) {
            // Insert version.
            $oldid = $data->id;
            $newversionid = $DB->insert_record('ouwiki_versions', $data);
            $this->set_mapping('ouwiki_version', $oldid, $newversionid, true);

            // Insert any links.
            foreach ($data->links as $link) {
                $link->fromversionid = $newversionid;
                // Note: The 'topageid' is still pointing to old id - we cannot
                // use mapping yet because not all pages have been retrieved,
                // so this needs to be update after_execute.
                $DB->insert_record('ouwiki_links', $link);
            }

            // If this version was the "currentversion" in the old database, update it.
            $page = $DB->get_record('ouwiki_pages', array('id' => $data->pageid),
                    'id, currentversionid');
            if ($oldid == $page->currentversionid) {
                $page->currentversionid = $newversionid;
                $DB->update_record('ouwiki_pages', $page);
            }
        }
        $transaction->allow_commit();

        // Clear array.
        $this->versions = array();
    }

    protected function process_ouwiki_link($data) {
        $data = (object)$data;

        // The new page id and parent version id are both not yet known, so
        // 'topageid' points to the old page id, and 'fromversionid' is not set
        // at all. Add to list, we will fill this data in later.
        $this->processingversion->links[] = $data;
    }

    protected function process_ouwiki_annotation($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pageid = $this->get_new_parentid('ouwiki_page');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('ouwiki_annotations', $data);

        // Add old and new annotation id to element ids array.
        $this->elementsids['annotation'.$oldid] = 'annotation'.$newitemid;
    }

    protected function after_execute() {
        global $DB, $CFG;
        $transaction = $DB->start_delegated_transaction();
        $ouwikiid = $this->get_task()->get_activityid();

        // Flush out any unsaved versions.
        $this->flush_versions();

        // Add ouwiki related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_ouwiki', 'intro', null);
        $this->add_related_files('mod_ouwiki', 'template', 'ouwiki');

        // Add post related files.
        $this->add_related_files('mod_ouwiki', 'attachment', 'ouwiki_version');
        $this->add_related_files('mod_ouwiki', 'content', 'ouwiki_version');

        // Update firstversionid.
        $sql = 'SELECT v.pageid,
                    (SELECT MIN(id)
                    FROM {ouwiki_versions} v3
                    WHERE v3.pageid = p.id AND v3.deletedat IS NULL)
                    AS firstversionid
                FROM
                    {ouwiki} o
                    JOIN {ouwiki_subwikis} s ON s.wikiid = o.id
                    JOIN {ouwiki_pages} p ON p.subwikiid = s.id
                    JOIN {ouwiki_versions} v ON v.pageid = p.id
                WHERE
                    o.id = ?
                GROUP BY v.pageid, p.id
                ORDER BY v.pageid';
        $rs = $DB->get_recordset_sql($sql, array($ouwikiid));
        foreach ($rs as $entry) {
            if ($entry->firstversionid) {
                $DB->set_field('ouwiki_pages', 'firstversionid', $entry->firstversionid,
                    array('id' => $entry->pageid));
            }
        }
        $rs->close();

        // Update previousversionid.
        $sql = 'SELECT v.id AS versionid,
                    (SELECT MAX(v2.id)
                    FROM {ouwiki_versions} v2
                    WHERE v2.pageid = p.id AND v2.id < v.id)
                    AS previousversionid
                FROM
                    {ouwiki} o
                    JOIN {ouwiki_subwikis} s ON s.wikiid = o.id
                    JOIN {ouwiki_pages} p ON p.subwikiid = s.id
                    JOIN {ouwiki_versions} v ON v.pageid = p.id
                WHERE
                    o.id = ?';
        $rs = $DB->get_recordset_sql($sql, array($ouwikiid));
        foreach ($rs as $entry) {
            if ($entry->previousversionid) {
                $DB->set_field('ouwiki_versions', 'previousversionid',
                    $entry->previousversionid, array('id' => $entry->versionid));
            }
        }
        $rs->close();

        // Update all the page ids for links.
        $sql = 'SELECT l.id AS linkid, l.topageid
                FROM
                    {ouwiki} o
                    JOIN {ouwiki_subwikis} s ON s.wikiid = o.id
                    JOIN {ouwiki_pages} p ON p.subwikiid = s.id
                    JOIN {ouwiki_versions} v ON v.pageid = p.id
                    JOIN {ouwiki_links} l ON l.fromversionid = v.id
                WHERE
                    o.id = ? AND l.topageid IS NOT NULL';
        $rs = $DB->get_recordset_sql($sql, array($ouwikiid));
        $errors = array();
        foreach ($rs as $entry) {
            $newpageid = $this->get_mappingid('ouwiki_page', $entry->topageid, null);
            if (!$newpageid && empty($errors[$entry->topageid])) {
                $errors[$entry->topageid] = true;
                $this->get_logger()->process('OU wiki: link to missing pageid ' .
                        $entry->topageid . ' not restored properly.', backup::LOG_WARNING);
            }
            $DB->set_field('ouwiki_links', 'topageid', $newpageid, array('id' => $entry->linkid));
        }
        $rs->close();

        // Update xhtml field with correct annotations.
        // Get all table entries in ouwiki_versions table for this wiki.
        $sql = "SELECT v.id, v.xhtml
                    FROM {ouwiki_subwikis} s
                    JOIN {ouwiki_pages} p ON p.subwikiid = s.id
                    JOIN {ouwiki_versions} v ON v.pageid = p.id
                    WHERE s.wikiid = ?
                    ORDER BY v.id";
        $rs = $DB->get_recordset_sql($sql, array($ouwikiid));

        // Go through annotation elements ids replacing old annotation ids with new annotation ids in xhtml field of result set.
        foreach ($rs as $entry) {
            $matches = array();
            // Check to see whether this contains any annotations to be replaced.
            $found = preg_match_all('~(span id=")(annotation[0-9]+)(")~', $entry->xhtml, $matches, PREG_SET_ORDER);
            if ($found) {
                foreach ($matches as $arr) {
                    // Check to see whether an old array key exist.
                    if (array_key_exists($arr[2], $this->elementsids)) {
                        // Do the replace.
                        $replacestr = 'span id="' .$this->elementsids[$arr[2]]. '"';
                        $entry->xhtml = str_replace($arr[0], $replacestr, $entry->xhtml);
                    }
                }
                // Set the xhtml field if any annotation ids replaced.
                $DB->set_field('ouwiki_versions', 'xhtml', $entry->xhtml, array('id' => $entry->id));
            }
        }
        // Close the result set.
        $rs->close();

        $transaction->allow_commit();

        require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');
        // Create search index if user data restored.
        if ($this->get_setting_value('userinfo') && ouwiki_search_installed()) {
            ouwiki_ousearch_update_all(false, $this->get_courseid());
        }
    }
}
