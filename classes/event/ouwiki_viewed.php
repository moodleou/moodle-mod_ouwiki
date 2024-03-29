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
 * The mod_ouwiki view ouwiki event.
 *
 * @package    mod_ouwiki
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ouwiki\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_ouwiki view ouwiki event class.
 *
 * @package    mod_ouwiki
 * @since      Moodle 2.7
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ouwiki_viewed extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ouwiki';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $str1 = "The user with id '$this->userid' viewed ouwiki page {$this->other['info']}
        with the course module id of '$this->contextinstanceid'.";
        $str2 = "Action was {$this->other['action']}";
        return $str1.$str2;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:ouwikiviewed', 'mod_ouwiki');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('\\mod\\ouwiki\\' . $this->other['logurl']);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'ouwikiid\' value must be set in the object.');
        }

        if (!isset($this->other['info'])) {
            throw new \coding_exception('The \'info\' value must be set in other.');
        }

        if (!isset($this->other['action'])) {
            throw new \coding_exception('The \'action\' value must be set in other.');
        }

        if (!isset($this->other['logurl'])) {
            throw new \coding_exception('The \'logurl\' value must be set in other.');
        }

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }

}
