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
 * This mask is based on the similar text mask but preserves wiki links. For example, if a page
 * includes a link such as [[Some other page|description]] it will be converted to a page title
 * based on the page id [[Masked page 1234|masked description]].
 *
 * @package mod_ouwiki
 */
class wiki_content_mask extends \tool_datamasking\similar_text_mask {

    /** @var array Array of subwikiid => pagename => pageid */
    protected $pageids = [];

    /** @var int Current processing subwiki id */
    protected $subwikiid = 0;

    /**
     * Constructs a new wiki content mask.
     */
    public function __construct() {
        parent::__construct('xhtml', true, \tool_datamasking\similar_text_mask::MODEL_POST);
    }

    public function before_table(\tool_datamasking\table $table, \core\progress\base $progress): void {
        global $DB;

        if ($table->get_name() !== 'ouwiki_versions') {
            throw new \coding_exception('You can only use wiki_content_mask on ouwiki_versions');
        }

        // Load the list of all pages in the entire database (at last count there are < 100k so it
        // should fit in RAM).
        $rs = $DB->get_recordset_select('ouwiki_pages', 'title != ?', [''], '', 'id, subwikiid, title');
        $progress->progress();
        foreach ($rs as $rec) {
            if (!array_key_exists($rec->subwikiid, $this->pageids)) {
                $this->pageids[$rec->subwikiid] = [];
            }
            $this->pageids[$rec->subwikiid][\core_text::strtoupper($rec->title)] = $rec->id;
        }
        $rs->close();
        $progress->progress();
    }

    public function after_table(\tool_datamasking\table $table, \core\progress\base $progress): void {
        // Free memory by discarding the page information.
        $this->pageids = [];
    }

    public function get_extra_fields(): array {
        // Join with the pages table to get current subwiki id.
        $subwikiid = new \tool_datamasking\extra_fields(
                "LEFT JOIN {ouwiki_pages} p# ON p#.id = base.pageid",
                [], ['p#.subwikiid' => 'subwikiid']);

        return [$subwikiid];
    }

    public function execute(array $options, \stdClass $rec): array {
        // Remember the current subwiki id.
        $this->subwikiid = $rec->subwikiid;

        // Do default behaviour.
        return parent::execute($options, $rec);
    }

    protected function replace_html(string $html): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');

        // Replace wiki links of the form [[link|description]] with tags:
        // <wikilink target="Masked page pageid">description</wikilink>.
        $html = preg_replace_callback(OUWIKI_LINKS_SQUAREBRACKETS, function($matches) {
            $details = ouwiki_get_wiki_link_details($matches[1]);

            // If it's blank then leave it blank.
            if ($details->page === '') {
                $target = '';
            } else {
                // Look up the page id.
                $upper = \core_text::strtoupper($details->page);
                if (array_key_exists($this->subwikiid, $this->pageids) &&
                        array_key_exists($upper, $this->pageids[$this->subwikiid])) {
                    $target = 'Masked page ' . $this->pageids[$this->subwikiid][$upper];
                } else {
                    // Page not found.
                    $target = 'Missing page';
                }
            }

            if ($details->rawtitle === null) {
                $title = '';
            } else {
                // This will be replaced next so just use a short phrase.
                $title = 'Masked title';
            }

            return '<wikilink target="' . $target . '">' . $title . '</wikilink>';
        }, $html);

        $html = parent::replace_html($html);

        // Replace the wikilink tags back again.
        return preg_replace_callback('~<wikilink target="([^"]+)">([^<]*)</wikilink>~', function($matches) {
            if ($matches[2]) {
                return '[[' . s($matches[1]) . '|' . s($matches[2]) . ']]';
            } else {
                return '[[' . s($matches[1]) . ']]';
            }
        }, $html);
    }

    public function get_description_text(): string {
        return get_string('wiki_content_mask', 'ouwiki');
    }
}
