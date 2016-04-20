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
 * Version.
 *
 * @package mod_ouwiki
 * @copyright 2016 The Open University
 * @author Steffen Pegenau
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class TableOfContents {
  // The table of contents is saved in a 6xn-Array
  // 6: <h1> - <h6>
  // n: the number of headings
  private $ToC = array(array(array(array(array(array())))));
  private $html = "";

  private $lastH1 = 0;
  private $lastH2 = 0;
  private $lastH3 = 0;
  private $lastH4 = 0;
  private $lastH5 = 0;
  private $lastH6 = 0;
  private $lastLvl = 0;

  public function __construct($html) {
    $this->html = $html;
    $this->parseHtml($html);
  }

  /*
  * returns the table of contents as printable html
  */
  public function toHtml() {
    $toc = $this->ToC;
    $output = "<h3>" . get_string('tableofcontents', 'ouwiki') . "</h3>";
    $output .= "<ul>";
    foreach($toc as $h1 => $h2tree) {
      foreach($h2tree as $h2 => $h3tree) {
        foreach($h3tree as $h3 => $h4tree) {
          foreach($h4tree as $h4 => $h5tree) {
            foreach($h5tree as $h5 => $h6tree) {
              foreach($h6tree as $h6 => $obj) {
                if($obj) {
                  $h = array($h1, $h2, $h3, $h4, $h5, $h6);
                  $output .= '<li><a href="#'.$obj->id.'">'.$this->getChapterNumber($h)." ".$obj->name .'</a></li>';
                }
              }
            }
          }
        }
      }
    }
    $output .= "</ul>";

    return $output;
  }

  private function getChapterNumber($h) {
    $number = "";

    // Generate full number with unnecessary zeros and dots
    // Example: 1.2.0.0.0.0
    for($i = 0; $i <= 6; $i++) {
      $number .= $h[$i] . '.';
    }

    $str = "";
    $bool = true;

    // Deletes unnecessary dots and zeros from the right side
    // Example: 1.2.0.0.0.0 becomes 1.2
    while($bool) {
      $str = rtrim($number, '.');
      $str = rtrim($str, '0');
      $bool = ($str !== $number) ? true : false;
      $number = $str;
    }

    return rtrim($number, '.');
  }

  /*
  * Parses the html-Code and generates the table of contents
  *
  * @param String $html The html-snippet to parse
  */
  private function parseHtml($html) {
    $reader = new XMLReader();
    $reader->xml($html);

    $headings = [];
    $output = "";

    $lastlevel = 0;

    // traverse the tree
    while($reader->read() !== false) {
      $tag = $reader->name;
      $content = $reader->readString();
      $matches = null;

      // is it a h1-h6 heading?
      preg_match('/[hH][1-6]/', $tag, $matches);
      if(!empty($content) && count($matches) > 0) {
        // example: h1 -> 1
        $lvl = substr($tag, 1);
        // <h1 id="ouw_s0_0"> => ouw_s0_0
        $id = $reader->getAttribute("id");
        $this->addToTree($lvl, $content, $id);
      }
    }
  }

  /**
  * Adds an entry with name and level to the table of contents
  *
  * param int $lvl The level of the heading
  * param string $name The title of the heading
  * param string $id html attribute id of heading
  */
  private function addToTree($lvl, $name, $id) {
    if($lvl < $this->lastLvl) {
      $lvlToDelete = $lvl + 1;
      switch($lvlToDelete) {
        case 1:
        $this->lastH1 = 0;
        case 2:
        $this->lastH2 = 0;
        case 3:
        $this->lastH3 = 0;
        case 4:
        $this->lastH4 = 0;
        case 5:
        $this->lastH5 = 0;
        case 6:
        $this->lastH6 = 0;
        break;
      }
    }

    switch ($lvl) {
      case 1:
      ++$this->lastH1;
      break;
      case 2:
      ++$this->lastH2;
      break;
      case 3:
      ++$this->lastH3;
      break;
      case 4:
      ++$this->lastH4;
      break;
      case 5:
      ++$this->lastH5;
      break;
      case 6:
      ++$this->lastH6;
      break;
    }
    $element = new stdClass();
    $element->name = $name;
    $element->id = $id;

    // Save element in array
    $this->ToC[$this->lastH1][$this->lastH2][$this->lastH3][$this->lastH4][$this->lastH5][$this->lastH6] = $element;

    $this->lastLvl = $lvl;
  }
}
