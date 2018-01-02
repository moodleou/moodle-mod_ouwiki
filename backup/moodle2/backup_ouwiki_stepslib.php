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
 * Define all the backup steps that will be used by the backup_ouwiki_activity_task
 */

/**
 * Define the complete ouwiki structure for backup, with file and id annotations
 */
class backup_ouwiki_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        $els = array('name', 'subwikis', 'intro', 'editbegin', 'editend', 'annotation',
                'introformat', 'completionedits', 'completionpages', 'enablewordcount', 'allowimport', 'timemodified');
        if (!$userinfo) {
            $els[] = 'template';
        }
        // Define each element separated
        $ouwiki = new backup_nested_element('ouwiki', array('id'), $els);

        $subwikis = new backup_nested_element('subs');

        $subwiki = new backup_nested_element('subwiki', array('id'), array('groupid', 'userid', 'magic'));

        $pages = new backup_nested_element('pages');

        $page = new backup_nested_element('page', array('id'), array('title', 'currentversionid', 'locked'));

        $versions = new backup_nested_element('versions');

        $version = new backup_nested_element('version', array('id'), array('xhtml', 'changestart',
                'changesize', 'changeprevsize', 'deletedat', 'timecreated', 'userid', 'wordcount'));

        $annotations = new backup_nested_element('annotations');

        $annotation = new backup_nested_element('annotation', array('id'), array('userid', 'timemodified', 'content'));

        $links = new backup_nested_element('links');

        $link = new backup_nested_element('link', array('id'), array('topageid', 'tomissingpage', 'tourl'));

        // Build the tree
        $ouwiki->add_child($subwikis);
        $subwikis->add_child($subwiki);

        $subwiki->add_child($pages);
        $pages->add_child($page);

        $page->add_child($versions);
        $versions->add_child($version);

        $version->add_child($links);
        $links->add_child($link);

        $page->add_child($annotations);
        $annotations->add_child($annotation);

        // Define sources
        $ouwiki->set_source_table('ouwiki', array('id' => backup::VAR_ACTIVITYID));

        // All these source definitions only happen if we are including user info
        if ($userinfo) {
            $subwiki->set_source_table('ouwiki_subwikis', array('wikiid' => backup::VAR_PARENTID));

            $page->set_source_table('ouwiki_pages', array('subwikiid' => backup::VAR_PARENTID));

            $version->set_source_table('ouwiki_versions', array('pageid' => backup::VAR_PARENTID));

            $link->set_source_table('ouwiki_links', array('fromversionid' => backup::VAR_PARENTID));

            $annotation->set_source_table('ouwiki_annotations', array('pageid' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $subwiki->annotate_ids('group', 'groupid');

        $subwiki->annotate_ids('user', 'userid');

        $version->annotate_ids('user', 'userid');

        $annotation->annotate_ids('user', 'userid');

        // Define file annotations
        $ouwiki->annotate_files('mod_ouwiki', 'intro', null); // This file area hasn't itemid
        if (!$userinfo) {
            $ouwiki->annotate_files('mod_ouwiki', 'template', 'id');
        }
        $version->annotate_files('mod_ouwiki', 'attachment', 'id');
        $version->annotate_files('mod_ouwiki', 'content', 'id');

        // Return the root element (wiki), wrapped into standard activity structure
        return $this->prepare_activity_structure($ouwiki);

    }
}
