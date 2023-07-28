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
 * This is a lib/helper class for ouwiki tests, containing useful setup functions
 * Include + Extend this class in your test rather than advance_testcase
 *
 * @package mod_ouwiki
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class ouwiki_test_utils {

    /**
     * Converts recordset to array, indexed numberically (0, 1, 2).
     *
     * @param moodle_recordset $rs Record set to convert
     * @return \stdClass[] Array of converted records
     */
    public static function recordset_to_array(moodle_recordset $rs) {
        $result = array();
        foreach ($rs as $rec) {
            $result[] = $rec;
        }
        $rs->close();
        return $result;
    }
}
