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
 * A scheduled task for OUWiki cron.
 *
 * @package    mod_ouwiki
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ouwiki\task;

class daily_housekeeping extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('ouwikicrontask', 'mod_ouwiki');
    }

    /**
     * Delete old locks from the ouwiki_locks database
     */
    public function execute() {
        global $DB, $CFG;
        // Require to get OUWIKI_LOCK_TIMEOUT.
        require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

        $now = time();
        // We are going to delete anything with a locked at time longer than now - 30 minutes ago.
        $timeout = $now - OUWIKI_LOCK_TIMEOUT;
        mtrace('Beginning ouwiki locks cleanup...');
        $before = microtime(true);
        $DB->delete_records_select('ouwiki_locks', 'lockedat < ?', array($timeout));
        mtrace(round(microtime(true)-$before, 1) . 's');
    }
}
