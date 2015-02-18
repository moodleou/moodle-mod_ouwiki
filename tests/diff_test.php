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

require_once($CFG->dirroot . '/mod/ouwiki/difflib.php');

class ouwiki_test_diff extends advanced_testcase {

    public $html1 = '
<p>This is a long paragraph
split over several lines
and including <b>bold</b> and
<i>italic</i> and <span class="frog">span</span> tags.</p>
<p>This is a second paragraph.</p>
<div>This div contain\'s some greengrocer\'s apostrophe\'s.</div>
<ul>
<li>A list</li>
<li>With multiple
items</li>
<li>Some of them have

multiple


line breaks</li>
</ul>', $html2 = '
<div><!-- Extra structure, to be ignored -->
<p>This is a long paragraph
split over several lines
and including <b>bold</b> and
<i>italic</i> and <span class="frog">span</span> tags.</p>
</div>
<p>This is a second paragraph which I have added some text to.</p>
<div>This div contain\'s <span class="added html tags">some</span> <b>greengrocer\'s</b> <img src="notthere.jpg" /> apostrophe\'s.</div>
<ul>
<li>A</li><!-- Deleted word -->
<!-- Deleted entire line -->
<li>Some of them have

multiple


line breaks</li>
</ul>';

    public function test_add_markers() {
        $html = '01frog67890zombie789';
        $words = array();
        $words[] = new ouwiki_word('frog', 2);
        $words[] = new ouwiki_word('zombie', 11);
        $result = ouwiki_diff_add_markers($html, $words, 'ouw_marker', '!!', '??');
        $this->assertEquals(
            '01!!<span class="ouw_marker">frog</span>??67890!!<span class="ouw_marker">zombie</span>??789', $result);
    }

    public function test_diff_html() {
        list($result1, $result2, $changes) = ouwiki_diff_html($this->html1, $this->html2);
        $this->assertEquals(13, $changes);
    }

    public function test_diff_words() {
        $lines1 = ouwiki_diff_html_to_lines($this->html1);
        $lines2 = ouwiki_diff_html_to_lines($this->html2);
        list($deleted, $added) = ouwiki_diff_words($lines1, $lines2);

        $delarray = array();
        foreach ($deleted as $word) {
            $delarray[] = $word->word;
        }
        sort($delarray);
        $addarray = array();
        foreach ($added as $word) {
            $addarray[] = $word->word;
        }
        sort($addarray);

        $this->assertEquals(array('With', 'items', 'list', 'multiple', 'paragraph.'), $delarray);
        $this->assertEquals(array('I', 'added', 'have', 'paragraph', 'some', 'text', 'to.', 'which'), $addarray);
    }

    public function test_diff_changes() {
        // Initial file for comparison (same for all examples).
        $file1 = array (1 => 'a', 'b', 'c', 'd', 'e', 'f', 'g');

        // Add text at beginning.
        $file2 = array(1 => '0', '1', 'a', 'b', 'c', 'd', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(), $result->deletes);
        $this->assertEquals(array(), $result->changes);
        $this->assertEquals(array(1, 2), $result->adds);

        // Add text at end.
        $file2 = array(1 => 'a', 'b', 'c', 'd', 'e', 'f', 'g', '0', '1');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(), $result->deletes);
        $this->assertEquals(array(), $result->changes);
        $this->assertEquals(array(8, 9), $result->adds);

        // Add text in middle.
        $file2 = array(1 => 'a', 'b', 'c', '0', '1', 'd', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(), $result->deletes);
        $this->assertEquals(array(), $result->changes);
        $this->assertEquals(array(4, 5), $result->adds);

        // Delete text at beginning.
        $file2 = array(1 => 'c', 'd', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(1, 2), $result->deletes);
        $this->assertEquals(array(), $result->changes);
        $this->assertEquals(array(), $result->adds);

        // Delete text at end.
        $file2 = array(1 => 'a', 'b', 'c', 'd', 'e');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(6, 7), $result->deletes);
        $this->assertEquals(array(), $result->changes);
        $this->assertEquals(array(), $result->adds);

        // Delete text in middle.
        $file2 = array(1 => 'a', 'b', 'c', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(4, 5), $result->deletes);
        $this->assertEquals(array(), $result->changes);
        $this->assertEquals(array(), $result->adds);

        // Change text in middle (one line).
        $file2 = array(1 => 'a', 'b', 'frog', 'd', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals($result->deletes, array());
        $this->assertEquals(1, count($result->changes));
        $this->assertEquals(array(3, 1, 3, 1), array_values((array)$result->changes[0]));
        $this->assertEquals(array(), $result->adds);

        // Change text in middle (two lines).
        $file2 = array(1 => 'a', 'b', 'frog', 'toad', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(), $result->deletes);
        $this->assertEquals(1, count($result->changes));
        $this->assertEquals(array(3, 2, 3, 2), array_values((array)$result->changes[0]));
        $this->assertEquals(array(), $result->adds);

        // Change text in middle (one line -> two).
        $file2 = array(1 => 'a', 'b', 'frog', 'toad', 'd', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(), $result->deletes);
        $this->assertEquals(1, count($result->changes));
        $this->assertEquals(array(3, 1, 3, 2), array_values((array)$result->changes[0]));
        $this->assertEquals(array(), $result->adds);

        // Change text in middle (two lines -> one).
        $file2 = array(1 => 'a', 'b', 'frog', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(), $result->deletes);
        $this->assertEquals(1, count($result->changes));
        $this->assertEquals(array(3, 2, 3, 1), array_values((array)$result->changes[0]));
        $this->assertEquals(array(), $result->adds);

        // Two changes.
        $file2 = array(1 => 'a', 'frog', 'toad', 'c', 'd', 'zombie', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals($result->deletes, array());
        $this->assertEquals(2, count($result->changes));
        $this->assertEquals(array(2, 1, 2, 2), array_values((array)$result->changes[0]));
        $this->assertEquals(array(5, 2, 6, 1), array_values((array)$result->changes[1]));
        $this->assertEquals(array(), $result->adds);

        // Changes at ends.
        $file2 = array(1 => 'ant', 'frog', 'toad', 'c', 'd', 'zombie');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(), $result->deletes);
        $this->assertEquals(2, count($result->changes));
        $this->assertEquals(array(1, 2, 1, 3), array_values((array)$result->changes[0]));
        $this->assertEquals(array(5, 3, 6, 1), array_values((array)$result->changes[1]));
        $this->assertEquals(array(), $result->adds);

        // A change, a delete, an add.
        $file2 = array(1 => 'ant', 'b', 'd', 'zombie', 'e', 'f', 'g');
        $result = ouwiki_diff($file1, $file2);
        $this->assertEquals(array(3), $result->deletes);
        $this->assertEquals(1, count($result->changes));
        $this->assertEquals(array(1, 1, 1, 1), array_values((array)$result->changes[0]));
        $this->assertEquals(array(4), $result->adds);
    }

    public function test_splitter() {
        $lines = ouwiki_diff_html_to_lines($this->html1);
        $this->assertEquals(array(
            1 => 'This is a long paragraph split over several lines and including bold and italic and span tags.',
            2 => 'This is a second paragraph.',
            3 => "This div contain's some greengrocer's apostrophe's.",
            4 => 'A list',
            5 => 'With multiple items',
            6 => 'Some of them have multiple line breaks'
        ), ouwiki_line::get_as_strings($lines));
        $lines = ouwiki_diff_html_to_lines($this->html2);
        $this->assertEquals(array(
            1 => 'This is a long paragraph split over several lines and including bold and italic and span tags.',
            2 => 'This is a second paragraph which I have added some text to.',
            3 => "This div contain's some greengrocer's apostrophe's.",
            4 => 'A',
            5 => 'Some of them have multiple line breaks'
        ), ouwiki_line::get_as_strings($lines));
    }

    public function test_basic_diff() {
        // Example from paper.
        $file1 = array(1 => 'a', 'b', 'c', 'd', 'e', 'f', 'g');
        $file2 = array(1 => 'w', 'a', 'b', 'x', 'y', 'z', 'e');
        $this->assertEquals(array(1 => 2, 2 => 3, 3 => 0, 4 => 0, 5 => 7, 6 => 0, 7 => 0), ouwiki_diff_internal($file1, $file2));
        $this->assertEquals(array(1 => 0, 2 => 1, 3 => 2, 4 => 0, 5 => 0, 6 => 0, 7 => 5), ouwiki_diff_internal($file2, $file1));

        // Add text at beginning.
        $file2 = array(1 => '0', '1', 'a', 'b', 'c', 'd', 'e', 'f', 'g');
        $this->assertEquals(array(1 => 3, 2 => 4, 3 => 5, 4 => 6, 5 => 7, 6 => 8, 7 => 9), ouwiki_diff_internal($file1, $file2));

        // Add text at end.
        $file2 = array(1 => 'a', 'b', 'c', 'd', 'e', 'f', 'g', '0', '1');
        $this->assertEquals(array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7), ouwiki_diff_internal($file1, $file2));

        // Add text in middle.
        $file2 = array(1 => 'a', 'b', 'c', '0', '1', 'd', 'e', 'f', 'g');
        $this->assertEquals(array(1 => 1, 2 => 2, 3 => 3, 4 => 6, 5 => 7, 6 => 8, 7 => 9), ouwiki_diff_internal($file1, $file2));

        // Delete text at beginning.
        $file2 = array(1 => 'c', 'd', 'e', 'f', 'g');
        $this->assertEquals(array(1 => 0, 2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5), ouwiki_diff_internal($file1, $file2));

        // Delete text at end.
        $file2 = array(1 => 'a', 'b', 'c', 'd', 'e');
        $this->assertEquals(array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 0, 7 => 0), ouwiki_diff_internal($file1, $file2));

        // Delete text in middle.
        $file2 = array(1 => 'a', 'b', 'c', 'f', 'g');
        $this->assertEquals(array(1 => 1, 2 => 2, 3 => 3, 4 => 0, 5 => 0, 6 => 4, 7 => 5), ouwiki_diff_internal($file1, $file2));
    }

    public function test_attachment_diff() {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

        $this->resetAfterTest(true);

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_ouwiki');
        $ouwiki = $generator->create_instance((object) array('course' => $course->id));
        $cm = get_coursemodule_from_instance('ouwiki', $ouwiki->id);
        $this->assertNotEmpty($cm);
        $context = context_module::instance($cm->id);
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, 0, $USER->id, true);

        // Add start page.
        $startpageversion = ouwiki_get_current_page($subwiki, null, OUWIKI_GETPAGE_CREATE);

        // Add version1 - no attachments.
        $ver1id = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, null, 'TEST', -1, -1, -1, false, null);

        // Add version 2 - no attachments.
        $ver2id = ouwiki_save_new_version($course, $cm, $ouwiki, $subwiki, null, 'TEST', -1, -1, -1, false, null);

        // Check diff when no attachments.
        $fs = get_file_storage();
        $files1 = ($files1 = $fs->get_area_files($context->id, 'mod_ouwiki', 'attachment', $ver1id, 'timemodified', false)) ? $files1 : null;
        $files2 = ($files2 = $fs->get_area_files($context->id, 'mod_ouwiki', 'attachment', $ver2id, 'timemodified', false)) ? $files2 : null;

        list($attachdiff1, $attachdiff2, $attachnumchanges) = ouwiki_diff_attachments($files1, $files2, $context->id, $ver1id, $ver2id);
        $this->assertEquals(0, $attachnumchanges);
        $hasno1 = strpos(get_string('noattachments', 'ouwiki'), $attachdiff1);
        $hasno2 = strpos(get_string('noattachments', 'ouwiki'), $attachdiff2);
        $this->assertTrue($hasno1 !== false && $hasno2 !== false);

        // Add attachment to first and re-check.
        $filerecord = (object) array(
                'contextid' => $context->id,
                'component' => 'mod_ouwiki',
                'filearea' => 'attachment',
                'itemid' => $ver1id,
                'filename' => 'test1.txt',
                'filepath' => '/'
                );
        $fs->create_file_from_string($filerecord, 'test');

        $files1 = ($files1 = $fs->get_area_files($context->id, 'mod_ouwiki', 'attachment', $ver1id, 'timemodified', false)) ? $files1 : null;
        list($attachdiff1, $attachdiff2, $attachnumchanges) = ouwiki_diff_attachments($files1, $files2, $context->id, $ver1id, $ver2id);
        $this->assertEquals(1, $attachnumchanges);
        $hasno1 = strpos($attachdiff1, 'test1.txt');
        $hasno2 = strpos($attachdiff2, get_string('noattachments', 'ouwiki'));
        $this->assertTrue($hasno1 !== false && $hasno2 !== false);

        // Add same attachment to second and test no changes.
        $filerecord->itemid = $ver2id;
        $fs->create_file_from_string($filerecord, 'test');
        $files2 = ($files2 = $fs->get_area_files($context->id, 'mod_ouwiki', 'attachment', $ver2id, 'timemodified', false)) ? $files2 : null;
        list($attachdiff1, $attachdiff2, $attachnumchanges) = ouwiki_diff_attachments($files1, $files2, $context->id, $ver1id, $ver2id);
        $this->assertEquals(0, $attachnumchanges);
        $hasno1 = strpos($attachdiff1, 'test1.txt');
        $hasno2 = strpos($attachdiff2, 'test1.txt');
        $this->assertTrue($hasno1 !== false && $hasno2 !== false);

        // Add a second attachemnt to second version and test.
        $filerecord->filename = 'test2.txt';
        $fs->create_file_from_string($filerecord, 'test2');
        $files2 = ($files2 = $fs->get_area_files($context->id, 'mod_ouwiki', 'attachment', $ver2id, 'timemodified', false)) ? $files2 : null;
        list($attachdiff1, $attachdiff2, $attachnumchanges) = ouwiki_diff_attachments($files1, $files2, $context->id, $ver1id, $ver2id);
        $this->assertEquals(1, $attachnumchanges);
        $hasno1 = strpos($attachdiff1, 'test1.txt');
        $hasno2 = strpos($attachdiff2, 'test2.txt');
        $this->assertTrue($hasno1 !== false && $hasno2 !== false);
    }

}
