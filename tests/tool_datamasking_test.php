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
 * Tests the tool_datamasking class for this plugin.
 *
 * @package mod_ouwiki
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking_test extends \advanced_testcase {

    /**
     * Tests actual behaviour of the masking applied in this plugin.
     */
    public function test_behaviour(): void {
        global $DB;

        $this->resetAfterTest();

        // Set up data to be masked.
        $p1 = $DB->insert_record('ouwiki_pages', ['subwikiid' => 123, 'title' => 'First']);
        $p2 = $DB->insert_record('ouwiki_pages', ['subwikiid' => 123, 'title' => '']);

        $DB->insert_record('ouwiki_versions', ['pageid' => $p1, 'timecreated' => 0,
                'xhtml' => 'Q.']);
        $DB->insert_record('ouwiki_versions', ['pageid' => $p1, 'timecreated' => 0,
                'xhtml' => '']);

        $DB->insert_record('ouwiki_annotations', ['pageid' => $p1, 'timemodified' => 0,
                'content' => 'Q.']);
        $DB->insert_record('ouwiki_annotations', ['pageid' => $p1, 'timemodified' => 0,
                'content' => '']);

        // Add some files.
        $fileids = [];
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_ouwiki', 'attachment',
                'a.txt', 'a');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_ouwiki', 'content',
                'b.txt', 'bb');
        $fileids[] = \tool_datamasking\testing_utils::add_file('mod_ouwiki', 'intro',
                'c.txt', 'ccc');

        // Before checks.
        $ouwikipagessql = 'SELECT title FROM {ouwiki_pages} ORDER BY id';
        $this->assertEquals(['First', ''], $DB->get_fieldset_sql($ouwikipagessql));
        $ouwikiversionssql = 'SELECT xhtml FROM {ouwiki_versions} ORDER BY id';
        $this->assertEquals(['Q.', ''], $DB->get_fieldset_sql($ouwikiversionssql));
        $ouwikiannotationssql = 'SELECT content FROM {ouwiki_annotations} ORDER BY id';
        $this->assertEquals(['Q.', ''], $DB->get_fieldset_sql($ouwikiannotationssql));

        // We don't check the details of the complicated version replacement here because that
        // is tested in wiki_content_mask_test.

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'a.txt', 1);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'b.txt', 2);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'c.txt', 3);

        // Run the full masking plan including this plugin, but without requiring mapping tables.
        \tool_datamasking\api::get_plan()->execute([], [\tool_datamasking\tool_datamasking::TAG_SKIP_ID_MAPPING]);

        // After checks.
        $this->assertEquals(['Masked page ' . $p1, ''], $DB->get_fieldset_sql($ouwikipagessql));
        $this->assertEquals(['X.', ''], $DB->get_fieldset_sql($ouwikiversionssql));
        $this->assertEquals(['X.', ''], $DB->get_fieldset_sql($ouwikiannotationssql));

        \tool_datamasking\testing_utils::check_file($this, $fileids[0], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[1], 'masked.txt', 224);
        \tool_datamasking\testing_utils::check_file($this, $fileids[2], 'c.txt', 3);
    }
}
