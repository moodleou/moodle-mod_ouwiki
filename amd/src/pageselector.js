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
 * JavaScript to handle ouwiki.
 *
 * @module mod_ouwiki/pageselector
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Pending from 'core/pending';
class PageSelector {
    /**
     * Initialize the PageSelector class.
     * @param {Array} pagesSelected Array of selected page IDs passed from PHP.
     */
    init(pagesSelected) {
        // Find all index trees.
        const pendingpage = new Pending('mod_ouwiki/indexTrees');
        const indexTrees = document.querySelectorAll('.ouw_indextree');
        indexTrees.forEach((list) => {
            // Find all page checkboxes within the index tree.
            const checkboxes = list.querySelectorAll('input.ouwiki_page_checkbox');
            checkboxes.forEach((checkbox) => {
                const pageId = checkbox.value;

                // Check if the page is selected.
                if (pagesSelected && pagesSelected.includes(pageId)) {
                    checkbox.checked = true;
                }

                // Add click event listener to each checkbox.
                checkbox.addEventListener('click', (event) => {
                    const pending = new Pending('mod_ouwiki/childCheckboxes');
                    const isChecked = event.target.checked;

                    // Find child checkboxes within the same branch and update their state.
                    const childCheckboxes = event.target.closest('li')?.querySelectorAll('ul li input.ouwiki_page_checkbox');
                    if (childCheckboxes) {
                        childCheckboxes.forEach((child) => {
                            child.checked = isChecked;
                        });
                    }
                    pending.resolve();
                });
            });
        });
        pendingpage.resolve();
    }
}

export const init = (pagesSelected) => {
    const pageSelector = new PageSelector();
    pageSelector.init(pagesSelected);
};
