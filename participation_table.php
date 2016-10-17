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
 * Subclass of flexible_table for participation and download
 *
 * @package    mod
 * @subpackage ouwiki
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once($CFG->libdir.'/tablelib.php');

/**
 * Class ouwiki_participation_table
 * extends flexible_table to override header and download rules
 */
class ouwiki_participation_table extends flexible_table {

    public $cm;
    public $course;
    public $ouwiki;
    public $pagename;
    public $groupid;
    public $groupname;
    public $grade;
    public $extraheaders;

    public function __construct($cm, $course, $ouwiki, $pagename, $groupid = 0,
        $groupname, $grade = null) {

        $this->cm = $cm;
        $this->course = $course;
        $this->ouwiki = $ouwiki;
        $this->pagename = $pagename;
        $this->groupid = $groupid;
        $this->groupname = $groupname;
        $this->grade = $grade;
        parent::__construct('mod-ouwiki-participation');
    }

    /**
     * Setup the columns and headers and other properties of the table and then
     * call flexible_table::setup() method.
     */
    public function setup($download = '') {
        global $CFG;

        // extra headers for export only
        if (!empty($download)) {
            $this->extraheaders = array(
                format_string($this->course->shortname, true),
                format_string($this->ouwiki->name, true),
            );
            if (!empty($this->groupname)) {
                $this->extraheaders[] = $this->groupname;
            }
        }

        // Define table columns
        $columns = array(
            'picture',
            'fullname',
            'pagescreated',
            'pageedits'
        );
        $headers = array(
            '',
            get_string('user'),
            get_string('pagescreated', 'ouwiki'),
            get_string('pageedits', 'ouwiki'),
        );

        if (!empty($download)) {
            unset($columns[0]);
            unset($headers[0]);
        }

        if ($this->ouwiki->enablewordcount) {
            $columns[] = 'wordsadded';
            $columns[] = 'wordsdeleted';
            $headers[] = get_string('wordsadded', 'ouwiki');
            $headers[] = get_string('wordsdeleted', 'ouwiki');
        }

        if ($this->ouwiki->allowimport) {
            $columns[] = 'importedfrom';
            $headers[] = get_string('pagesimported', 'ouwiki');
        }

        if ($this->grade) {
            $columns[] = 'grade';
            $headers[] = get_string('grades');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($CFG->wwwroot . '/mod/ouwiki/participation.php?id=' .
            $this->cm->id . '&amp;pagename=' . $this->pagename . '&amp;group=' . $this->groupid);

        $this->column_class('picture', 'picture');
        $this->column_class('fullname', 'fullname');
        $this->column_class('pagescreated', 'pagescreated');
        $this->column_class('pageedits', 'pageedits');
        $this->column_class('wordsadded', 'wordsadded');
        $this->column_class('wordsdeleted', 'wordsdeleted');

        $this->set_attribute('cellspacing', '0');
        $this->set_attribute('id', 'participation');
        $this->set_attribute('class', 'participation');
        $this->set_attribute('width', '100%');
        $this->set_attribute('align', 'center');
        $this->sortable(false);

        parent::setup();
    }

    /**
     * This function is not part of the public api.
     *
     * Overriding here to avoid downloading in unsupported formats
     */
    public function get_download_menu() {
        $exportclasses = array('csv' => get_string('downloadcsv', 'ouwiki'));
        return $exportclasses;
    }

    /**
     * Override to output grade form header
     * @see flexible_table::wrap_html_start()
     */
    public function wrap_html_start() {
        if ($this->grade && !$this->is_downloading()) {
            echo $this->grade_form_header();
        }
    }

    public function grade_form_header() {
        $output = '';
        $formattrs = array();
        $formattrs['action'] = new moodle_url('/mod/ouwiki/savegrades.php');
        $formattrs['id']     = 'savegrades';
        $formattrs['method'] = 'post';
        $output = html_writer::start_tag('form', $formattrs);
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id',
            'value' => $this->cm->id));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'group',
            'value' => $this->groupid));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pagename',
            'value' => $this->pagename));
        return $output;
    }

    public function grade_form_footer() {
        $output = '';
        $savegrades = html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'savegrades', 'value' => get_string('savegrades', 'ouwiki')));
        $output = html_writer::tag('div', $savegrades, array('class' => 'savegradesbutton'));
        $output .= html_writer::end_tag('form');
        return $output;
    }

    /**
     * This function is not part of the public api.
     * You don't normally need to call this. It is called automatically when
     * needed when you start adding data to the table.
     *
     */
    public function start_output() {
        $this->started_output = true;
        if ($this->exportclass !== null) {
            $this->exportclass->start_table($this->sheettitle);
            $this->exportclass->output_headers($this->extraheaders);
            $this->exportclass->output_headers($this->headers);
        } else {
            $this->start_html();
            $this->print_headers();
        }
    }
}

/**
 * Class ouwiki_user_participation_table
 * extends flexible_table to override header and download rules
 */
class ouwiki_user_participation_table extends flexible_table {

    public $cm;
    public $course;
    public $ouwiki;
    public $pagename;
    public $groupname;
    public $user;
    public $userfullname;
    public $extraheaders;

    public function __construct($cm, $course, $ouwiki, $pagename,
        $groupname, $user, $userfullname) {

        $this->cm = $cm;
        $this->course = $course;
        $this->ouwiki = $ouwiki;
        $this->pagename = $pagename;
        $this->groupname = $groupname;
        $this->user = $user;
        $this->userfullname = $userfullname;
        parent::__construct('mod-ouwiki-user-participation');
    }

    public function setup($download = '') {
        global $CFG;

        // extra headers for export only
        if (!empty($download)) {
            $this->extraheaders = array(
                format_string($this->course->shortname, true),
                format_string($this->ouwiki->name, true),
            );
            if (!empty($this->groupname)) {
                $this->extraheaders[] = $this->groupname;
            }
            $this->extraheaders[] = $this->userfullname;
        }

        $columns = array('date', 'time', 'page');
        $headers = array(
            get_string('date'),
            get_string('time'),
            get_string('page', 'ouwiki')
        );
        if ($this->ouwiki->enablewordcount) {
            if (empty($download)) {
                $columns[] = 'words';
                $headers[] = get_string('words', 'ouwiki');
            } else {
                $columns[] = 'wordsadded';
                $columns[] = 'wordsdeleted';
                $headers[] = get_string('wordsadded', 'ouwiki');
                $headers[] = get_string('wordsdeleted', 'ouwiki');
            }
        }
        if ($this->ouwiki->allowimport) {
            $columns[] = 'importedfrom';
            $headers[] = get_string('importedfrom', 'ouwiki');
        }

        if (empty($download)) {
            $columns[] = 'view';
            $view = html_writer::tag('span', get_string('view'), array('class' => 'accesshide'));
            $headers[] = $view;
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($CFG->wwwroot . '/mod/ouwiki/userparticipation.php?id=' .
            $this->cm->id . '&amp;pagename=' . $this->pagename . '&amp;user=' . $this->user->id);

        $this->column_class('date', 'date');
        $this->column_class('time', 'time');
        $this->column_class('page', 'page');
        $this->column_class('view', 'view');

        if ($this->ouwiki->enablewordcount) {
            $this->column_class('words', 'words');
        }

        if ($this->ouwiki->allowimport) {
            $this->column_class('allowimport', 'allowimport');
        }

        $this->set_attribute('cellspacing', '0');
        $this->set_attribute('id', 'participation');
        $this->set_attribute('class', 'participation');
        $this->set_attribute('width', '100%');
        $this->set_attribute('align', 'center');
        $this->sortable(false);

        parent::setup();
    }

    /**
     * This function is not part of the public api.
     *
     * Overriding here to avoid downloading in unsupported formats
     */
    public function get_download_menu() {
        $exportclasses = array('csv' => get_string('downloadcsv', 'ouwiki'));
        return $exportclasses;
    }

    /**
     * This function is not part of the public api.
     * You don't normally need to call this. It is called automatically when
     * needed when you start adding data to the table.
     *
     */
    public function start_output() {
        $this->started_output = true;
        if ($this->exportclass !== null) {
            $this->exportclass->start_table($this->sheettitle);
            $this->exportclass->output_headers($this->extraheaders);
            $this->exportclass->output_headers($this->headers);
        } else {
            $this->start_html();
            $this->print_headers();
        }
    }
}
