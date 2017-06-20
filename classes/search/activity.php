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
 * Search area for mod_ouwiki activities.
 *
 * @package mod_ouwiki
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ouwiki\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Search area for mod_ouwiki activities.
 *
 * @package mod_ouwiki
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends \core_search\base_activity {

    /**
     * File areas related to document
     *
     * List all file areas in files table
     */
    const FILEAREA = [
        'INTRO' => 'intro',
    ];

    /**
     * Returns the document associated with this activity.
     *
     * This default implementation for activities sets the activity name to title and the activity intro to
     * content. Any activity can overwrite this function if it is interested in setting other fields than the
     * default ones, or to fill description optional fields with extra stuff.
     *
     * @param \stdClass $record
     * @param array    $options
     * @return \core_search\document
     */
    public function get_document($record, $options = array()) {

        try {
            $cm = $this->get_cm($this->get_module_name(), $record->id, $record->course);
            $context = \context_module::instance($cm->id);
            if (!empty($record->intro)) {
                $record->intro = file_rewrite_pluginfile_urls($record->intro, 'pluginfile.php', $context->id,
                    $this->componentname, self::FILEAREA['INTRO'], null);
            }
        } catch (\dml_missing_record_exception $ex) {
            $donothingforcodechecker = true;
        }

        return parent::get_document($record, $options);
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
                    0, 'sortorder DESC, id ASC', false));
        }

        foreach ($files as $file) {
            $document->add_stored_file($file);
        }
    }
}
