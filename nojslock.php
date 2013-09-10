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
 * This script is called via an IMG tag when JavaScript is disabled.
 * It updates the lock to allow 15 minutes without requiring confirmation.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

$lockid = required_param('lockid', PARAM_INT);
if ($lock = $DB->get_record('ouwiki_locks', array('id' => $lockid))) {
    $lock->seenat = time() + OUWIKI_LOCK_NOJS;
    $lock->expiresat = null;
    $DB->update_record('ouwiki_locks', $lock);
    header('Content-Type: image/png');
    readfile('pix/dot.png');
    exit;
} else {
    print_error('No such lock');
}
