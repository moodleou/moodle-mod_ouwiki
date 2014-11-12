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
 * Steps definitions related with the ouwiki activity.
 *
 * @package    mod_ouwiki
 * @category   test
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given,
Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Behat\Context\Step\Then;
use Behat\Mink\Exception\ElementNotFoundException;

/**
 * wiki-related steps definitions.
 *
 * @package    mod_ouwiki
 * @category   test
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_ouwiki extends behat_base {

    /**
     * Adds a page to the current ouwiki with the provided data. You should be in the main view page..
     *
     * @Given /^I add a ouwiki page with the following data:$/
     * @param TableNode $data
     */
    public function i_add_a_ouwiki_page_with_the_following_data(TableNode $data) {
        $steps = array();
        $datahash = $data->getRowsHash();
        $i = 0;
        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $steps[] = new Given('I set the field "'  .$locator . '" to "' . $value . '"');
            if ($i == 0) {
                $steps[] = new Given('I press "' . get_string('create', 'ouwiki') . '"');
            } else {
                continue;
            }
            $i++;
        }
        $steps[] = new Given('I press "' . get_string('savechanges') . '"');
        return $steps;
    }

    /**
     * Edit current ouwiki page with the provided data. You should be in the page view..
     *
     * @Given /^I edit a ouwiki page with the following data:$/
     * @param TableNode $data
     */
    public function i_edit_a_ouwiki_page_with_the_following_data(TableNode $data) {
        $steps = array(
                new Given('I click on "Edit page" "link"'),
                new Given('I set the following fields to these values:', $data),
                new Given('I press "' .get_string('savechanges') . '"'),
        );
        return $steps;
    }

}
