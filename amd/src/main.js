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

import {getString} from 'core/str';
import Pending from 'core/pending';
/**
 * JavaScript to handle ouwiki.
 *
 * @module mod_ouwiki/main
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Main {
    constructor() {}

    /**
     * Initialize event listeners and set up annotation handling.
     */
    init() {
        const iconShow = document.getElementById('showannotationicons');
        const iconHide = document.getElementById('hideannotationicons');

        if (iconShow) {
            iconShow.addEventListener('click', (e) => {
                e.preventDefault();
                this.showAnnotationIcons(true);
                setTimeout(() => document.getElementById('hideannotationicons')?.focus(), 0);
            });
        }

        if (iconHide) {
            iconHide.addEventListener('click', (e) => {
                e.preventDefault();
                this.showAnnotationIcons(false);
                setTimeout(() => document.getElementById('showannotationicons')?.focus(), 0);
            });
        }

        const annoSpans = document.querySelectorAll('span.ouwiki-annotation-tag');
        if (annoSpans.length > 0) {
            this.ouwikiShowAllAnnotations('none');
            annoSpans.forEach((span) => this.setupSpans(span));
        }

        const createButton = document.getElementById('ouw_create');
        if (createButton) {
            this.ouwikiSetFields();
        }
        this.setupExpandCollapseAnnotations();
    }

    /**
     * Show or hide annotation icons.
     * @param {boolean} show  Whether to show the annotation icons
     */
    showAnnotationIcons(show) {
        const pending = new Pending("mod_ouwiki/showannotationicons");
        const container = document.querySelector('.ouwiki-content');
        const hideClass = 'ouwiki-hide-annotations';
        if (container) {
            if (show) {
                container.classList.remove(hideClass);
            } else {
                container.classList.add(hideClass);
            }
        }

        const url = show
            ? document.querySelector('#showannotationicons')?.href
            : document.querySelector('#hideannotationicons')?.href;

        if (url) {
            fetch(`${url}&ajax=1`);
            pending.resolve();
        }
    }

    /**
     * Initialize fields for creating new pages.
     */
    async ouwikiSetFields() {
        const createButton = document.getElementById('ouw_create');
        const pageName = document.getElementById('ouw_newpagename');
        const addButton = document.getElementById('ouw_add');
        const sectionName = document.getElementById('ouw_newsectionname');

        // Fetch localized strings.
        const typeInPageName = await getString('typeinpagename', 'ouwiki');
        const typeInSectionName = await getString('typeinsectionname', 'ouwiki');

        // Initialize page creation fields.
        if (createButton && pageName) {
            createButton.disabled = true;
            pageName.style.color = 'gray';
            pageName.value = typeInPageName;
            pageName.addEventListener('focus', () => this.ouwikiResetField(pageName));
            pageName.addEventListener('keyup', () =>
                this.ouwikiClearDisabled(createButton, pageName)
            );
        }

        // Initialize section creation fields.
        if (addButton && sectionName) {
            addButton.disabled = true;
            sectionName.style.color = 'gray';
            sectionName.value = typeInSectionName;
            sectionName.addEventListener('focus', () => this.ouwikiResetField(sectionName));
            sectionName.addEventListener('keyup', () =>
                this.ouwikiClearDisabled(addButton, sectionName)
            );
        }
    }

    /**
     * Enable or disable a button based on the presence of text in a field.
     * @param {HTMLElement} button  The button to enable or disable
     * @param {HTMLElement} field The text field to monitor
     */
    ouwikiClearDisabled(button, field) {
        button.disabled = !field.value.trim();
    }

    /**
     * Reset a field to an empty state if it contains a placeholder.
     * @param {HTMLElement} field The field to reset
     */
    ouwikiResetField(field) {
        if (field.style.color === 'gray') {
            field.value = '';
            field.style.color = 'black';
        }
    }

    /**
     * Show or hide all annotation boxes.
     * @param {string} action The CSS display value ('block' or 'none')
     */
    async ouwikiShowAllAnnotations(action) {
        const annoBoxes = document.querySelectorAll('span.ouwiki-annotation');
        const collapseAnnotation = await getString('collapseannotation', 'ouwiki');
        const expandAnnotation = await getString('expandannotation', 'ouwiki');
        annoBoxes.forEach((box) => {
            box.style.display = action;
            const imgTag = box.parentNode.querySelector('img');
            if (imgTag) {
                imgTag.alt = action === 'block' ? collapseAnnotation : expandAnnotation;
                imgTag.title = action === 'block' ? collapseAnnotation : expandAnnotation;
            }
        });

        if (action === 'block') {
            this.ouwikiSwapAnnotationUrl('hide');
        } else {
            this.ouwikiSwapAnnotationUrl('show');
        }
    }

    /**
     * Swap the URLs for showing or hiding all annotations.
     * @param {string} action The action to perform ('hide' or 'show')
     */
    ouwikiSwapAnnotationUrl(action) {
        const show = document.getElementById('expandallannotations');
        const hide = document.getElementById('collapseallannotations');
        if (show && hide) {
            if (action === 'hide') {
                show.style.display = 'none';
                hide.style.display = 'inline';
            } else {
                show.style.display = 'inline';
                hide.style.display = 'none';
            }
        }
    }

    /**
     * Set up event listeners for expand and collapse annotations.
     */
    setupExpandCollapseAnnotations() {
        const links = document.querySelectorAll('#expandcollapseannotations a');
        links.forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const action = link.dataset.action;
                if (action === 'expand') {
                    this.ouwikiShowAllAnnotations('block');
                } else if (action === 'collapse') {
                    this.ouwikiShowAllAnnotations('none');
                }
            });
        });
    }

    /**
     * Set up click and keyboard events for annotation spans.
     * @param {HTMLElement} span The annotation span to set up
     */
    setupSpans(span) {
        span.style.cursor = 'pointer';
        span.tabIndex = 0;

        span.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                this.ouwikiShowHideAnnotation(`annotationbox${span.id.substring(10)}`);
                e.preventDefault();
            }
        });

        span.addEventListener('click', () => {
            this.ouwikiShowHideAnnotation(`annotationbox${span.id.substring(10)}`);
        });
    }

    /**
     * Show or hide a specific annotation box.
     * @param {string} id The ID of the annotation box to toggle
     */
    async ouwikiShowHideAnnotation(id) {
        const box = document.getElementById(id);
        const annotag = box.parentNode;
        const imgTag = annotag.querySelector('img');

        if (box.style.display === 'block') {
            box.style.display = 'none';
            imgTag.alt = await getString('expandannotation', 'ouwiki');
            imgTag.title = await getString('expandannotation', 'ouwiki');
            this.ouwikiSwapAnnotationUrl('show');
        } else {
            box.style.display = 'block';
            imgTag.alt = await getString('collapseannotation', 'ouwiki');
            imgTag.title = await getString('collapseannotation', 'ouwiki');

            const annoBoxes = document.querySelectorAll('span.ouwiki-annotation');
            const allVisible = Array.from(annoBoxes).every(
                (el) => el.style.display === 'block'
            );

            if (allVisible) {
                this.ouwikiSwapAnnotationUrl('hide');
            }
        }
    }
}

export const init = () => {
    const main = new Main();
    main.init();
};
