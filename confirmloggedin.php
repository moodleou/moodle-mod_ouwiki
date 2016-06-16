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
 * This script is called through AJAX. It confirms that a user is still
 * logged in and has a valid session before saving edits to a wiki page
 *
 * @package    mod
 * @subpackage ouwiki
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');

header('Content-Type: text/plain');

try {
    // Test session - These functions throw exceptions so trap and exit if they fail.
    // This saves 404 errors and sends a smaller page.
    $contextid = required_param('contextid', PARAM_INT);
    list($context, $course, $cm) = get_context_info_array($contextid);
    $PAGE->set_url('/mod/ouwiki/confirmloggedin.php');
    $PAGE->set_context($context);
    require_login($course, false, $cm);
    require_sesskey();
} catch (moodle_exception $e) {
    $pid = 0;
    $url = '/mod/ouwiki/edit.php';
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $url = new moodle_url($_SERVER['HTTP_REFERER']);
        $rpid = $url->get_param('id');
        if (!empty($rpid)) {
            $pid = $rpid;
        }
        $url = $url->out_as_local_url();
    }
    $params = array(
            'context' => context_system::instance(),
            'other' => array('page' => $url, 'pid' => $pid)
    );
    $event = \mod_ouwiki\event\save_failed::create($params);
    $event->trigger();
    exit;
}

echo 'ok';
