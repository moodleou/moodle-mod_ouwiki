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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

class mod_ouwiki_annotate_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE;

        $mform =& $this->_form;
        $annotations = $this->_customdata[0];
        $pageid = $this->_customdata[1]->pageid;
        $pagename = $this->_customdata[2];
        $currentuserid = $this->_customdata[3];
        $canlock = $this->_customdata[4];
        $orphaned = false;

        if ($pagename !== '') {
            $mform->addElement('hidden', 'page', $pagename);
            $mform->setType('page', PARAM_TEXT);
        }
        $mform->addElement('hidden', 'user', $currentuserid);
        $mform->setType('user', PARAM_INT);

        if (count($annotations != 0)) {
            usort($annotations, array('mod_ouwiki_annotate_form', 'ouwiki_internal_position_sort'));
            $editnumber = 1;
            foreach ($annotations as $annotation) {
                if (!$annotation->orphaned) {
                    $mform->addElement('textarea', 'edit'.$annotation->id, '(' . $editnumber . ')',
                            array('cols'=>'40', 'rows'=>'3'));
                    $mform->setDefault('edit'.$annotation->id, $annotation->content);
                    $editnumber++;
                } else {
                    $orphaned = true;
                }
            }
        }

        // Special field used in JavaScript
        $mform->addElement('static', 'endannotations', '', '<span id="end"></span>');

        // only display this checkbox if there are orphaned annotations
        if ($orphaned) {
            $mform->addElement('checkbox', 'deleteorphaned', get_string('deleteorphanedannotations', 'ouwiki'));
        }

        if ($canlock) {
            $mform->addElement('checkbox', 'lockediting', get_string('lockediting', 'ouwiki'));
            if (ouwiki_is_page_editing_locked($pageid)) {
                $mform->setDefault('lockediting', true);
            } else {
                $mform->setDefault('lockediting', false);
            }
        }
        $this->add_action_buttons();
    }

    private function ouwiki_internal_position_sort($a, $b) {
        return intval($a->position) - intval($b->position);
    }
}
