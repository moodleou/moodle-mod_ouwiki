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
 * OUWIKI data generator
 *
 * @package    mod_ouwiki
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * ouwiki module data generator class
 *
 * @package    mod_ouwiki
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ouwiki_generator extends testing_module_generator {

    private $modcount = 0;
    private $pagecount = 0;
    private $annotationcount = 0;

    public function reset() {
        $this->modcount = 0;
        $this->pagecount = 0;
        $this->annotationcount = 0;
        parent::reset();
    }

    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

        $record = (object)(array)$record;

        if (!isset($record->subwikis) && !isset($options['subwikis'])) {
            $record->subwikis = OUWIKI_SUBWIKIS_SINGLE;
        }
        if (!isset($record->annotation) && !isset($options['annotation'])) {
            $record->annotation = 1;
        }
        $this->modcount++;

        if (!isset($record->name) && !isset($options['name'])) {
            $record->name = 'OUWIKI' . $this->modcount;
        } else if (isset($options['name'])) {
            $record->name = $options['name'];// Name must be in $record.
        }
        if (!isset($record->grade) && !isset($options['grade'])) {
            $record->grade = 0;
        }

        return parent::create_instance($record, (array)$options);
    }

    public function create_content($instance, $record = array()) {
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

        $cm = get_coursemodule_from_instance('ouwiki', $instance->id);
        $context = context_module::instance($cm->id);
        // Setup subwiki.
        if (!isset($record['subwiki'])) {
            // Create a new sub wiki object for current user.
            $subwiki = ouwiki_get_subwiki($instance->course, $instance, $cm, $context, 0, $USER->id, true);
        } else {
            $subwiki = $record['subwiki'];
        }

        if (isset($record['newversion'])) {
            // Update an existing page with a new version.
            $newverinfo = $record['newversion'];
            if (!isset($newverinfo->formdata)) {
                $newverinfo->formdata = null;
            }
            if (!isset($newverinfo->pagename)) {
                $newverinfo->pagename = null;
            }
            if (!isset($newverinfo->content)) {
                $newverinfo->content = 'Test content';
            }
            return ouwiki_save_new_version($instance->course, $cm, $instance, $subwiki, $newverinfo->pagename,
                    $newverinfo->content, -1, -1, -1, null, $newverinfo->formdata);
        } else {
            // Create a new page - does this by default (off start page).
            if (!isset($record['newpage'])) {
                $record['newpage'] = new stdClass();
            }
            $newpageinfo = $record['newpage'];
            if (!isset($newpageinfo->formdata)) {
                $newpageinfo->formdata = null;
            }
            if (!isset($newpageinfo->pagename)) {
                $newpageinfo->pagename = null;
            }
            // Ensure linked from page exists.
            ouwiki_get_current_page($subwiki, $newpageinfo->pagename, OUWIKI_GETPAGE_CREATE);
            if (!isset($newpageinfo->newpagename)) {
                $this->pagecount++;
                $newpageinfo->newpagename = 'OU Wiki Test Page' . $this->pagecount;
            }
            if (!isset($newpageinfo->content)) {
                $newpageinfo->content = 'Test content';
            }
            ouwiki_create_new_page($instance->course, $cm, $instance, $subwiki, $newpageinfo->pagename,
                    $newpageinfo->newpagename, $newpageinfo->content, $newpageinfo->formdata);
            return ouwiki_get_current_page($subwiki, $newpageinfo->newpagename);
        }
    }

    /**
     * Create annotation record in ouwiki_annotations table only.
     *
     * @param int $pageid
     * @param int $userid
     * @param string $content
     * @return bool|int
     */
    public function create_annotation($pageid, $userid = null, $content = null) {
        global $DB;

        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        $dataobject = new stdClass();
        $dataobject->pageid = $pageid;
        $dataobject->userid = $userid;
        $dataobject->timemodified = time();

        if (empty($content)) {
            $this->annotationcount++;
            $content = 'OU Wiki Test Annotation' . $this->annotationcount;
        }

        $dataobject->content = $content;
        $newannoid = $DB->insert_record('ouwiki_annotations', $dataobject);

        return $newannoid;
    }
}
