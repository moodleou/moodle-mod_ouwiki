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
declare(strict_types=1);

namespace mod_ouwiki\completion;
require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the data activity.
 *
 * Class for defining mod_ouwiki's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given data instance and a user.
 *
 * @package mod_ouwiki
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * @param string $rule
     * @return int
     * @throws \coding_exception
     */
    public function get_state(string $rule): int {
        // Use forum object to handle this request.
        $this->validate_rule($rule);

        $result = ouwiki_get_completion_state_lib($this->cm->id, $this->userid, $rule);
        return $result;
    }
    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [];
    }

}
