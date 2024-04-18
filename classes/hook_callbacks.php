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

namespace mod_ouwiki;

/**
 * Hook callbacks.
 *
 * @package mod_ouwiki
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Called when the system wants to find out if an activity is searchable, to decide whether to
     * display a search box in the header.
     *
     * @param \local_moodleglobalsearch\hook\activity_search_info $hook
     */
    public static function activity_search_info(\local_moodleglobalsearch\hook\activity_search_info $hook) {
        // For ouwiki, we do the search on basically all pages within wiki.
        if ($hook->is_modname('ouwiki') && preg_match('~^mod-ouwiki-~', $hook->get_page_type())) {
            $hook->enable_search(get_string('search', 'ouwiki'));
        }
    }

}
