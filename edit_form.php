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
 * This file contains all necessary code to define and process an edit form
 */

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

class mod_ouwiki_edit_page_form extends moodleform {

    protected function definition() {
        global $CFG;

        $mform =& $this->_form;

        // Editor.
        $mform->addElement('editor', 'content', get_string('content'), null, array('maxfiles' => EDITOR_UNLIMITED_FILES));
        $mform->addHelpButton('content', 'formathtml', 'wiki');

        // attachments
        if ($this->_customdata->attachments) {
            $mform->addElement('filemanager', 'attachments', get_string('attachments', 'ouwiki'), null, array('subdirs' => 0));
        }

        // hiddens
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'startversionid', null);
        $mform->setType('startversionid', PARAM_INT);
        $mform->addElement('hidden', 'user', null);
        $mform->setType('user', PARAM_INT);
        if (!$this->_customdata->startpage) {
            $mform->addElement('hidden', 'page', '');
            $mform->setType('page', PARAM_TEXT);
        }
        if ($this->_customdata->addpage) {
            $mform->addElement('hidden', 'frompage', '');
            $mform->setType('frompage', PARAM_TEXT);
        }
        if ($this->_customdata->addsection) {
            $mform->addElement('hidden', 'newsection', '');
            $mform->setType('newsection', PARAM_TEXT);
        }
        $mform->addElement('hidden', 'section', '');
        $mform->setType('section', PARAM_RAW);

        $buttongroup = array();
        $buttongroup[] =& $mform->createElement('submit', 'editoption', get_string('savechanges'), array('id' => 'save'));
        $buttongroup[] =& $mform->createElement('submit', 'editoption', get_string('preview'), array('id' => 'preview'));
        $buttongroup[] =& $mform->createElement('submit', 'editoption', get_string('cancel'), array('id' => 'cancel'));

        $mform->addGroup($buttongroup, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}
