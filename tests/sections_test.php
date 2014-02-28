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
 * OUWiki unit tests - test locallib functions
 *
 * @package    mod_ouwiki
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
global $CFG;

require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

class ouwiki_test_sections extends advanced_testcase {

    public $sample = '
<h1 id="ouw_s0_0">Start</h1>
x
x<h1>No section ID</h1>x
x<h1 id="ouw_sly">Invalid section ID</h1>x
x<h1 id="ouw_s13_x">Invalid section ID</h1>x
x<h1 id="ouw_s13_13">Non-match heading tags</h2>x
x<h1 id="ouw_s1_666">Valid section heading</h1>x
x<h2 id="ouw_s2_666">Test&amp;</h2>x
x<h3 id="ouw_s3_666">Test<span class="frog">whatever</span></h3>x
x<h4 id="ouw_s4_666">
Test

spacing

and     stuff

</h4>x
x<h5 id="ouw_s5_666">V5</h5>x
x<h6 id="ouw_s6_666">V6</h6>x
x
<h1 id="ouw_s0_1">End</h1>';

    public function test_find_sections() {
        $sections = ouwiki_find_sections($this->sample);
        $this->assertEquals(array(
                '0_0' => 'Start',
                '1_666' => 'Valid section heading',
                '2_666' => 'Test&',
                '3_666' => 'Testwhatever',
                '4_666' => 'Test spacing and stuff',
                '5_666' => 'V5',
                '6_666' => 'V6',
                '0_1' => 'End'
            ), $sections);
    }
}
