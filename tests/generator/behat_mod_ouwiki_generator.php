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
 * Behat data generator for mod_ouwiki.
 *
 * @package mod_ouwiki
 * @category test
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_ouwiki_generator extends behat_generator_base {
    #[\Override]
    protected function get_creatable_entities(): array {
        return [
            'pages' => [
                'singular' => 'page',
                'datagenerator' => 'page',
                'required' => ['ouwiki', 'page'],
                'switchids' => ['ouwiki' => 'ouwiki'],
            ],
        ];
    }

    /**
     * Get the ouwkiki id using an activity idnumber or name.
     *
     * @param string $idnumberorname The forum activity idnumber or name.
     * @return int The forum id
     */
    protected function get_ouwiki_id(string $idnumberorname): int {
        return $this->get_cm_by_activity_name('ouwiki', $idnumberorname)->instance;
    }
}
