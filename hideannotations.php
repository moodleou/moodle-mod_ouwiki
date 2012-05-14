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
 * Toggles the controls whether to load HQ videos or SQ videos for this user
 * @package mod_ouwiki
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require($CFG->dirroot . '/mod/ouwiki/basicpage.php');

require_login();
require_sesskey();

$hide = required_param('hide', PARAM_INT);

if ($hide) {
    set_user_preference(OUWIKI_PREF_HIDEANNOTATIONS, 1);
} else {
    unset_user_preference(OUWIKI_PREF_HIDEANNOTATIONS);
}

// If this is the AJAX version, stop processing now.
if (optional_param('ajax', 0, PARAM_INT)) {
    exit;
}

// Otherwise redirect back.
redirect('view.php?' .ouwiki_display_wiki_parameters(
        $pagename, $subwiki, $cm, OUWIKI_PARAMS_URL));
