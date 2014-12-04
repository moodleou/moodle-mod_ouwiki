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
 * Utility class handling all cron tasks.
 * @package mod_ouwiki
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ouwiki_cron {

    public static function cron() {
        // Newline after the ouwiki...
        mtrace("");

        // Delete old locks.
        self::daily_housekeeping();
    }

    /**
     * Do housekeeping which only runs once per day.
     */
    public static function daily_housekeeping() {
        global $CFG;

        $today = date('Y-m-d');
        $now = time();

        // Get last run date.
        $lastrun = get_config('ouwiki', 'housekeepinglastrun');
        if (!$lastrun) {
            // If there is no last-run date, set the last run date to today.
            $lastrun = date('Y-m-d');
        } else {
            if ($today == $lastrun) {
                // Do not run the housekeeping as it has been run today.
                return;
            }
        }

        // Remove old locks.
        self::delete_old_locks($now);

        // Update last run date.
        set_config('housekeepinglastrun', $today, 'ouwiki');
    }

    /**
     * Delete old locks from the ouwiki_locks database
     * @param $now current unix time
     */
    public static function delete_old_locks($now) {
        global $CFG, $DB;

        // Require to get OUWIKI_LOCK_TIMEOUT.
        require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

        // We are going to delete anything with a locked at time longer than now - 30 minutes ago.
        $timeout = $now - OUWIKI_LOCK_TIMEOUT;

        mtrace('Beginning ouwiki locks cleanup...');
        $before = microtime(true);

        $DB->delete_records_select('ouwiki_locks', 'lockedat < ?', array($timeout));

        mtrace(round(microtime(true)-$before, 1) .'s');

    }

}
