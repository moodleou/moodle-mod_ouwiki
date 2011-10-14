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

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('ouwiki', '/activity/ouwiki');
        $paths[] = new restore_path_element('ouwiki_subwiki', '/activity/ouwiki/subs/subwiki');
        $paths[] = new restore_path_element('ouwiki_page', '/activity/ouwiki/subs/subwiki/pages/page');
        $paths[] = new restore_path_element('ouwiki_version', '/activity/ouwiki/subs/subwiki/pages/page/versions/version');
        $paths[] = new restore_path_element('ouwiki_annotation', '/activity/ouwiki/subs/subwiki/pages/page/annotations/annotation');
        $paths[] = new restore_path_element('ouwiki_link', '/activity/ouwiki/subs/subwiki/pages/page/versions/version/links/link');

        // Return the paths wrapped into standard activity structure
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

        // insert the ouwiki record
        $newitemid = $DB->insert_record('ouwiki', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
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
    }

    protected function process_ouwiki_version($data) {
        global $DB, $CFG;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pageid = $this->get_new_parentid('ouwiki_page');

        if (!isset($data->wordcount)) {
            // calculate the wordcount if it doesn't exist
            require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');
            $wordcount = ouwiki_count_words($data->xhtml);
            $data->wordcount = $wordcount;
        }

        $newitemid = $DB->insert_record('ouwiki_versions', $data);
        $this->set_mapping('ouwiki_version', $oldid, $newitemid, true);

        // see if this version was the "currentversion" in the old database
        $page = $DB->get_record('ouwiki_pages', array('id' => $data->pageid), 'id, currentversionid');

        if ($oldid == $page->currentversionid) {
            $page->currentversionid = $newitemid;
            $DB->update_record('ouwiki_pages', $page);
        }
    }

    protected function process_ouwiki_link($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->fromversionid = $this->get_new_parentid('ouwiki_version');
        $data->topageid = $this->get_mappingid('ouwiki_page', $data->topageid);

        $newitemid = $DB->insert_record('ouwiki_links', $data);
    }

    protected function process_ouwiki_annotation($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pageid = $this->get_new_parentid('ouwiki_page');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('ouwiki_annotations', $data);

    }

    protected function after_execute() {
        global $DB;

        // Add ouwiki related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_ouwiki', 'intro', null);

        // Add post related files
        $this->add_related_files('mod_ouwiki', 'attachment', 'ouwiki_version');
        $this->add_related_files('mod_ouwiki', 'content', 'ouwiki_version');

        // update firstversionid
        $sql = 'SELECT v.pageid,
                    (SELECT MIN(id)
                        FROM {ouwiki_versions} v3
                        WHERE v3.pageid = p.id AND v3.deletedat IS NULL)
                    AS firstversionid
                        FROM {ouwiki_pages} p
                        JOIN {ouwiki_versions} v ON v.pageid = p.id
                    GROUP BY v.pageid, p.id ORDER BY v.pageid';
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $entry) {
                if (isset($entry->firstversionid)) {
                    $DB->set_field('ouwiki_pages', 'firstversionid', $entry->firstversionid,
                        array('id' => $entry->pageid));
                }
            }
        }
        $rs->close();

        // update previousversionid
        $sql = 'SELECT v.id AS versionid,
                    (SELECT MAX(v2.id)
                        FROM {ouwiki_versions} v2
                        WHERE v2.pageid = p.id AND v2.id < v.id)
                    AS previousversionid
                        FROM {ouwiki_pages} p
                        JOIN {ouwiki_versions} v ON v.pageid = p.id';
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $entry) {
                if (isset($entry->previousversionid)) {
                    $DB->set_field('ouwiki_versions', 'previousversionid',
                        $entry->previousversionid, array('id' => $entry->versionid));
                }
            }
        }
        $rs->close();
    }
}
