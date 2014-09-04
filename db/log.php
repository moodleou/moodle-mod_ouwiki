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
 * Definition of log events
 *
 *
 * @package    mod_ouwiki
 * @copyright  2012 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$logs = array(
    array('module' => 'ouwiki', 'action' => 'add', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'annotate', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'diff', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'edit', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'entirewiki', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'history', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'lock', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'participation', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'revert', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'search', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'unlock', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'update', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'userparticipation', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'versiondelete', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'versionundelete', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'view', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'view all', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'viewold', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'wikihistory', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'wikiindex', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'page created', 'mtable' => 'ouwiki', 'field' => 'name'),
    array('module' => 'ouwiki', 'action' => 'page updated', 'mtable' => 'ouwiki', 'field' => 'name')
);