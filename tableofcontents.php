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
  private $reducedToC = null;

  private $html = "";

  private $lastH1 = 0;
  private $lastH2 = 0;
  private $lastH3 = 0;
  private $lastH4 = 0;
  private $lastH5 = 0;
  private $lastH6 = 0;

  private $lastLvl = 0;
  private $minLvl = 0;

  public $numberOfHeadings;

  public function __construct($html) {
    $this->html = $html;
    $this->parseHtml($html);
    $this->setMinLvl();
  }

  /*
  * returns the table of contents as printable html
  */
  public function toHtml() {
    // No headings => no reason for a table of contents
    if($this->numberOfHeadings < 1) {
      return "";
    }

    $toc = $this->ToC;
    //echo "<pre>" . print_r($toc, true) . "</pre>";
    $output = "<h3 class='ouwtopheading'>" . get_string('tableofcontents', 'ouwiki') . "</h3>";

    // Helps building the nested <ul>-elements
    $lastlvl = 0;

    foreach($toc as $h1 => $h2tree) {
      foreach($h2tree as $h2 => $h3tree) {
        foreach($h3tree as $h3 => $h4tree) {
          foreach($h4tree as $h4 => $h5tree) {
            foreach($h5tree as $h5 => $h6tree) {
              foreach($h6tree as $h6 => $obj) {
                if($obj) {
                  // Get the chapter number, for example 1.2.3
                  $h = array($h1, $h2, $h3, $h4, $h5, $h6);
                  $chapterNumber = $this->getChapterNumber($h);

                  // Get the level, for this example 3
                  $currentLvl = $this->getLvlByChapterNumber($chapterNumber);

                  // The elements heading
                  $element = '<li><a href="#'.$obj->id.'">'.$chapterNumber." ".$obj->name .'</a></li>';

                  // New nested <ul>
                  if($currentLvl > $lastlvl) {
                    $output .= str_repeat("<ul>", $currentLvl - $lastlvl);
                    $output .= $element;
                  }
                  // Close as many <ul> as necessary
                  elseif ($currentLvl < $lastlvl) {
                    $output .= str_repeat("</ul>", $lastlvl - $currentLvl);
                    $output .= $element;
                  }
                  // Same level, just add <li>
                  else {
                    $output .= $element;
                  }

                  // Set helper
                  $lastlvl = $currentLvl;
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

  /**
   * Returns the headings level by the chapter number
   *
   * For example: 1.2.3 => 3
   *              1.3   => 2
   * @param  string $n The chapter number
   * @return int The headings lvel
   */
  private function getLvlByChapterNumber($n) {
    $e = explode('.', $n);
    return count($e);
  }

  /**
   * Sets the minimum level of headings
   *
   * If there are only <h3> and <h4> headings the minlvl is 2
   */
  private function setMinLvl() {
    $reducedToC = $this->ToC;
    while(!isset($reducedToC[1]) && isset($reducedToC[0])) {
      $reducedToC = $reducedToC[0];
      $this->minLvl++;
    }
  }

  /**
   * Generates the chapter number for a heading, for example "1.0.2"
   * @param  array $h An array of alle heading numbers
   * @return string    The chapter number
   */
  private function getChapterNumber($h) {
    $number = "";

    // Generate full number with unnecessary zeros and dots
    // Example: 1.2.0.0.0.0
    for($i = 0; $i < 6; $i++) {
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

    $str = rtrim($number, '.');

    return substr($str, 2*$this->minLvl);
  }

  /*
  * Parses the html-Code and generates the table of contents
  *
  * @param String $html The html-snippet to parse
  */
  private function parseHtml($html) {
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);

    // Get all Headings
    $xpath = new DOMXPath($dom);
    $query = '//h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //H1 | //H2 | //H3 | //H4 | //H5 | //H6';
    $headings = $xpath->query($query);

    $this->numberOfHeadings = $headings->length;

    if($headings->length > 0 ) {
      foreach ($headings as $heading) {
        // Get Heading level: <h6> => 6
        $lvl = substr($heading->tagName, 1);

        // Get id:
        // <h1 id="ouw_s0_0"> => ouw_s0_0
        $attributes = $heading->attributes;
        $id = $attributes->getNamedItem('id')->value;

        // Add heading to data structure
        $this->addToTree($lvl, $heading->nodeValue, $id);
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
