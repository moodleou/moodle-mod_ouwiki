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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Pretends to wrap transactions. In fact does nothing. If you want a
 * real implementation of this, the OU have one, it goes in /local.
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');

class transaction_wrapper {

    function __construct(&$localdb = null) {
    }

    function complete($ok = true) {
        return $ok;
    }

    function commit() {
        return true;
    }

    function rollback() {
    }

    static function is_in_transaction() {
        return false;
    }
}
