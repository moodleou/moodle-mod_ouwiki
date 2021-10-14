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
 * Tests the wiki_content_mask class.
 *
 * @package mod_ouwiki
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wiki_content_mask_test extends \advanced_testcase {
    /**
     * Tests executing the mask, including retaining the wiki links.
     */
    public function test_execute(): void {
        global $DB;

        $this->resetAfterTest();

        // Create some wiki pages.
        $p1 = $DB->insert_record('ouwiki_pages', ['subwikiid' => 123, 'title' => 'First page']);
        $p2 = $DB->insert_record('ouwiki_pages', ['subwikiid' => 123, 'title' => 'Second page']);
        $p3 = $DB->insert_record('ouwiki_pages', ['subwikiid' => 123, 'title' => 'Third page']);
        $p4 = $DB->insert_record('ouwiki_pages', ['subwikiid' => 456, 'title' => 'First page']);
        $p5 = $DB->insert_record('ouwiki_pages', ['subwikiid' => 456, 'title' => 'IInd page']);

        // And a couple of versions.
        $DB->insert_record('ouwiki_versions', ['pageid' => $p1, 'timecreated' => 0, 'xhtml' =>
                '<p>[[ First page ]]</p><ul><li>[[ Second page | Whatever ]]</li>' .
                '<li>[[Third page ]]</li><li>[[Fourth page]]</li></ul>']);
        $DB->insert_record('ouwiki_versions', ['pageid' => $p5, 'timecreated' => 0, 'xhtml' =>
                '<p>[[First page]]</p><p>[[Second page]]</p><p>[[IInd page]]</p>']);

        // Run full processing.
        $table = new \tool_datamasking\table('ouwiki_versions');
        $table->add(new wiki_content_mask());
        $table->execute([], [], new \core\progress\none());

        $result = $DB->get_fieldset_sql('SELECT xhtml FROM {ouwiki_versions} ORDER BY id');
        $this->assertRegExp('~<p>\[\[Masked page ' . $p1 . '\]\]</p>'.
                '<ul><li>\[\[Masked page ' . $p2 . '|[^\\]]+\]\]</li>' .
                '<li>\[\[Masked page ' . $p3 . '\]\]</li>' .
                '<li>\[\[Missing page\]\]</li></ul>~', $result[0]);
        $this->assertEquals('<p>[[Masked page ' . $p4 . ']]</p>' .
                '<p>[[Missing page]]</p>' .
                '<p>[[Masked page ' . $p5 . ']]</p>', $result[1]);
    }

    /**
     * Tests the get_affected_fields function.
     */
    public function test_get_affected_fields(): void {
        $mask = new wiki_content_mask();
        $this->assertEquals(['xhtml'], $mask->get_affected_fields());
    }

    /**
     * Tests the description text.
     */
    public function test_get_description_text(): void {
        $mask = new wiki_content_mask();
        $this->assertEquals('Replace with fake text of a similar length, retaining HTML tags and '.
                '[[wiki page]] links', $mask->get_description_text());
    }

}
