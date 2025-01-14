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

/** Make sure this isn't being directly accessed */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

class mod_ouwiki_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $COURSE;

        $mform =& $this->_form;
        $data = $this->_customdata['data'] ?? null;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name and intro
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('wikiintro', 'ouwiki'));

        $mform->addElement('header', 'wikifieldset', get_string('wikisettings', 'ouwiki'));

        // Subwikis
        $subwikisoptions = array();
        $subwikisoptions[OUWIKI_SUBWIKIS_SINGLE] = get_string('subwikis_single', 'ouwiki');
        $subwikisoptions[OUWIKI_SUBWIKIS_GROUPS] = get_string('subwikis_groups', 'ouwiki');
        $subwikisoptions[OUWIKI_SUBWIKIS_INDIVIDUAL] = get_string('subwikis_individual', 'ouwiki');
        $mform->addElement('select', 'subwikis', get_string("subwikis", "ouwiki"), $subwikisoptions);
        $mform->addHelpButton('subwikis', 'subwikis', 'ouwiki');

        // Annotation
        $annotationoptions = array('0' => get_string('no'), '1' => get_string('yes'));
        $mform->addElement('select', 'annotation', get_string('annotationsystem', 'ouwiki'), $annotationoptions);
        $mform->addHelpButton('annotation', 'annotationsystem', 'ouwiki');

        // Editing timeout
        $timeoutoptions = array();
        $timeoutoptions[0] = get_string('timeout_none', 'ouwiki');
        $timeoutoptions[15*60] = get_string('numminutes', '', 15);
        $timeoutoptions[30*60] = get_string('numminutes', '', 30);
        $timeoutoptions[60*60] = get_string('numminutes', '', 60);
        $timeoutoptions[120*60] = get_string('numhours', '', 2);
        $timeoutoptions[240*60] = get_string('numhours', '', 4);
        if (debugging('', DEBUG_DEVELOPER)) {
            // This is not a language string because it's only for developer
            // debugging, lots of which requires English...
            $timeoutoptions[3*60] = '3 minutes (for testing)';
        }
        $mform->addElement('select', 'timeout', get_string("timeout", "ouwiki"), $timeoutoptions);
        $mform->addHelpButton('timeout', 'timeout', 'ouwiki');

        // Read-only controls.
        $mform->addElement('date_selector', 'editbegin', get_string('editbegin', 'ouwiki'), array('optional' => true));
        $mform->addHelpButton('editbegin', 'editbegin', 'ouwiki');
        $mform->addElement('date_selector', 'editend', get_string('editend', 'ouwiki'), array('optional' => true));
        $mform->addHelpButton('editend', 'editend', 'ouwiki');

        // Display any template usage warning messages.
        if ((!empty($this->current->id)) && (ouwiki_has_subwikis($this->current->id))) {
            $mform->addElement('static', 'name1', get_string('note', 'ouwiki'), get_string('subwikiexist', 'ouwiki'));
        }
        if (isset($this->current->template)) {
            $mform->addElement('static', 'name2', get_string('note', 'ouwiki'), get_string('templatefileexists', 'ouwiki',
                    $this->current->template));
        }
        // Template - previously on creation, but allow to add now add anytime.
        $filepickeroptions = array();
        $filepickeroptions['accepted_types'] = array('.xml', '.zip');
        $filepickeroptions['maxbytes'] = $COURSE->maxbytes;
        $mform->addElement('filepicker', 'template_file', get_string('template', 'ouwiki'), null, $filepickeroptions);
        $mform->addHelpButton('template_file', 'template', 'ouwiki');

        $lockstartpagesoptions = array('0' => get_string('no'), '1' => get_string('yes'));
        $mform->addElement('select', 'lockstartpages', get_string('lockstartpages', 'ouwiki'), $lockstartpagesoptions);
        $mform->addHelpButton('lockstartpages', 'lockstartpages', 'ouwiki');

        // Wordcount
        $wordcountoptions = array('0' => get_string('no'), '1' => get_string('yes'));
        $mform->addElement('select', 'enablewordcount', get_string('showwordcounts', 'ouwiki'), $wordcountoptions);
        $mform->addHelpButton('enablewordcount', 'showwordcounts', 'ouwiki');
        $mform->setDefault('enablewordcount', 1);

        // Enable the allow import course wiki pages into this wiki.
        $mform->addElement('checkbox', 'allowimport', get_string('allowimport', 'ouwiki', 0));
        $mform->addHelpButton('allowimport', 'allowimport', 'ouwiki');

        $this->standard_grading_coursemodule_elements();

        // Standard stuff
        $this->standard_coursemodule_elements();

        // Disable the 'completion with grade' if grading is turned off
        if ($mform->elementExists('completionusegrade')) {
            $mform->disabledIf('completionusegrade', 'grade', 'eq', 0);
        }

        $this->add_action_buttons();

        $this->set_data($data);
    }

    public function add_completion_rules() {
        $mform =& $this->_form;

        $group = [];
        $completionpagesenabledel = $this->get_suffixed_name('completionpagesenabled');
        $group[] =& $mform->createElement(
                'checkbox',
                $completionpagesenabledel,
                ' ',
                get_string('completionpages', 'ouwiki'));
        $completionpagesel = $this->get_suffixed_name('completionpages');
        $group[] =& $mform->createElement('text', $completionpagesel, ' ', ['size' => 3]);
        $mform->setType($completionpagesel, PARAM_INT);
        $completionpagesgroupel = $this->get_suffixed_name('completionpagesgroup');
        $mform->addGroup($group, 'completionpagesgroup', get_string('completionpagesgroup', 'ouwiki'), [' '], false);
        $mform->disabledIf($completionpagesel, $completionpagesenabledel, 'notchecked');

        $group = [];
        $completioneditsenabledel = $this->get_suffixed_name('completioneditsenabled');
        $group[] =& $mform->createElement(
                'checkbox',
                $completioneditsenabledel,
                ' ',
                get_string('completionedits', 'ouwiki'));
        $completioneditsel = $this->get_suffixed_name('completionedits');
        $group[] =& $mform->createElement('text', $completioneditsel, ' ', ['size' => 3]);
        $mform->setType($completioneditsel, PARAM_INT);
        $completioneditsgroupel = $this->get_suffixed_name('completioneditsgroup');
        $mform->addGroup($group, $completioneditsgroupel, get_string('completioneditsgroup', 'ouwiki'), [' '], false);
        $mform->disabledIf('completionedits', 'completioneditsenabled', 'notchecked');

        return [$completionpagesgroupel, $completioneditsgroupel];
    }

    public function completion_rule_enabled($data) {
        return
            ((!empty($data[$this->get_suffixed_name('completionpagesenabled')]) &&
                    $data[$this->get_suffixed_name('completionpages')] != 0)) ||
            ((!empty($data[$this->get_suffixed_name('completioneditsenabled')]) &&
                    $data[$this->get_suffixed_name('completionedits')] != 0));
    }

    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        // Turn off completion settings if the checkboxes aren't ticked
        if (!empty($data->completionunlocked)) {
            $completion = $data->{$this->get_suffixed_name('completion')};
            $autocompletion = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{$this->get_suffixed_name('completionpagesenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completionpages')} = 0;
            }
            if (empty($data->{$this->get_suffixed_name('completioneditsenabled')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completionedits')} = 0;
            }
        }

        if (empty($data->allowimport)) {
            $data->allowimport = 0;
        }

        return $data;
    }

    public function data_preprocessing(&$default_values) {
        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $completionpagesenabledel = $this->get_suffixed_name('completionpagesenabled');
        $completionpagesel = $this->get_suffixed_name('completionpages');
        $default_values[$completionpagesenabledel] = !empty($default_values[$completionpagesel]) ? 1 : 0;
        if (empty($default_values[$completionpagesel])) {
            $default_values[$completionpagesel] = 1;
        }
        $completioneditsenabledel = $this->get_suffixed_name('completioneditsenabled');
        $completioneditsel = $this->get_suffixed_name('completionedits');
        $default_values[$completioneditsenabledel] = !empty($default_values[$completioneditsel]) ? 1 : 0;
        if (empty($default_values[$completioneditsel])) {
            $default_values[$completioneditsel] = 1;
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ( (($data['subwikis'] == 0) || ($data['subwikis'] == 2) ) && ($data['groupmode'] > 0) ) {
            $errors['groupmode'] = get_string('errorcoursesubwiki', 'ouwiki');
        }
        if ( ($data['subwikis'] == 1) && ($data['groupmode'] == 0) ) {
            $errors['groupmode'] = get_string('errorgroupssubwiki', 'ouwiki');
        }
        return $errors;
    }

    /**
     * Get the suffix of name.
     *
     * @param string $fieldname The field name of the completion element.
     * @return string The suffixed name.
     */
    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }
}
