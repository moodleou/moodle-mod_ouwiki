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
 * Version 1.0
 *
 * @package mod_ouwiki
 * @copyright 2016 The Open University
 * @author Joel Tschesche
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class TableOfContents
{

    private $html = "";
    private $headings = array();

    public function __construct($html) {
        $this->html = $html;
        $this->parse_html($html);
        $this->set_min_lvl();
    }

    /**
     * returns the table of contents as printable html.
     */
    public function to_html() {
        // No headings => no reason for a table of contents.
        if (count($this->headings) < 1) {
            return "";
        }

        $output = PHP_EOL . "<h3 class='ouwtopheading'>" . get_string('tableofcontents', 'ouwiki') . "</h3>" . PHP_EOL;
        $lastlvl = 0;

        foreach ($this->headings as $heading) {

            if ($lastlvl == 0) {
                $output .= '<ol class="toc">';
            } else if ($heading->lvl > $lastlvl) {
                $output .= '<ol>';
            } else {
                $output .= str_repeat('</li></ol>', $lastlvl - $heading->lvl);
                $output .= '</li>';
            }
            $output .= '<li class="toc_element"><a href="#' . $heading->id . '">' . $heading->name . '</a>';

            $lastlvl = $heading->lvl;
        }
        $output .= str_repeat('</li></ol>' . PHP_EOL, $lastlvl);
        return $output;
    }

    /**
     * Sets the minimum level of headings.
     *
     */
    private function set_min_lvl() {
        $lvls = array();
        foreach ($this->headings as $heading) {
            $lvls[] = $heading->lvl;
        }
        $min = min($lvls);
        foreach ($this->headings as $heading) {
                $heading->lvl = $heading->lvl - $min + 1;
        }
    }

    /**
     * Parses the html-Code and generates the table of contents.
     *
     * @param String $html The html-snippet to parse.
     */
    private function parse_html($html) {
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);

        // Get all Headings.
        $xpath = new DOMXPath($dom);
        $query = '//h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //H1 | //H2 | //H3 | //H4 | //H5 | //H6';
        $headings = $xpath->query($query);

        if ($headings->length > 0) {
            foreach ($headings as $heading) {
                // Get Heading level: <h6> => 6.
                $lvl = substr($heading->tagName, 1);

                $attributes = $heading->attributes;
                $id = $attributes->getNamedItem('id')->value;

                $element = new stdClass();
                $element->name = $heading->nodeValue;
                $element->id = $id;
                // Set the lvl to 3 if lvl < 3, as all headings <= 3 are treated as section headings.
                $element->lvl = $lvl < 3 ? 3: $lvl;
                $this->headings[] = $element;
            }
        }
    }
}
