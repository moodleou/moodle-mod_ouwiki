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
 * @module mod_ouwiki/annotate
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import AnnotateModal from 'mod_ouwiki/annotatemodal';
import * as FocusLockManager from 'core/local/aria/focuslock';
import ModalEvents from 'core/modal_events';

class Annotate {
    constructor() {
        this.currentMarker = null;
        this.modal = null;
    }

    /**
     * Initialize the Annotate module.
     */
    async init() {
        await this.setupDialog();
        await this.setupMarkers();
    }

    async setupDialog() {
        this.modal = await AnnotateModal.create({});
    }

    /**
     * Show the annotation dialog.
     * @param {HTMLElement} marker The annotation marker element
     */
    async showDialog(marker) {
        if (!this.modal) {
            return;
        }

        this.currentMarker = marker.id;

        // Clear the textarea and show the modal.
        const annotationTextArea = this.modal.getBody().find('#annotationtext');
        if (annotationTextArea.length) {
            annotationTextArea.val('');
            annotationTextArea.focus();
        }
        this.modal.show();
        const $root = await this.modal.getRoot();
        const textarea = $root.find('#annotationtext')[0];
        // Lock tab control inside modal.
        FocusLockManager.trapFocus(document.querySelector('.annotate-modal'));
        $root.on(ModalEvents.shown, () => {
            textarea.focus();
        });
        $root.on(ModalEvents.hidden, () => {
            this.modal.destroy();
            FocusLockManager.untrapFocus();
        });
        $root.on(ModalEvents.save, (e) => {
            e.preventDefault();
            const annotationText = $root.find('#annotationtext').val();
            this.newAnnotation(annotationText);
            this.modal.hide();
        });
    }

    /**
     * Setup annotation markers with event listeners.
     */
    async setupMarkers() {
        const markers = document.querySelectorAll('span.ouwiki-annotation-marker');
        markers.forEach((marker) => {
            marker.style.cursor = 'pointer';
            marker.tabIndex = 0;

            marker.addEventListener('keydown', async(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    await this.showDialog(marker);
                }
            });

            marker.addEventListener('click', async() => {
                await this.showDialog(marker);
            });
        });
    }

    /**
     * Create a new annotation.
     * @param {string} newText The text of the new annotation.
     */
    newAnnotation(newText) {
        // Get the number of the next form textarea.
        const annotationCount = document.getElementById('annotationcount');
        const annotationNum = parseInt(annotationCount?.textContent || '0', 10) + 1;

        // Create the new form section.
        const newFItem = document.createElement('div');
        newFItem.id = `newfitem${annotationNum}`;
        newFItem.className = 'fitem';
        newFItem.style.display = 'none';

        // Create the title div with label.
        const fItemTitle = document.createElement('div');
        fItemTitle.className = 'fitemtitle';

        const fItemLabel = document.createElement('label');
        fItemLabel.htmlFor = `id_annotationedit${annotationNum}`;
        fItemLabel.textContent = annotationNum;
        fItemTitle.appendChild(fItemLabel);

        // Create the div for the textarea.
        const fElement = document.createElement('div');
        fElement.className = 'felement ftextarea';

        const fElementTextarea = document.createElement('textarea');
        fElementTextarea.id = `id_annotationedit${annotationNum}`;
        fElementTextarea.name = `new${this.currentMarker.substring(6)}`;
        fElementTextarea.rows = 3;
        fElementTextarea.cols = 40;
        fElementTextarea.value = newText;
        fElementTextarea.textContent = newText;

        fElement.appendChild(fElementTextarea);

        // Append the title and textarea to the new form section.
        newFItem.appendChild(fItemTitle);
        newFItem.appendChild(fElement);

        // Insert the new fitem before the last fitem (which is the delete orphaned checkbox).
        const endMarker = document.getElementById('end');
        const fContainer = endMarker.parentNode.parentNode.parentNode;
        fContainer.insertBefore(newFItem, endMarker.parentNode.parentNode);

        // Update the marker ID.
        const markerId = this.markNewAnnotation(annotationNum);

        // Show the new form section.
        newFItem.style.display = 'block';

        // Update the annotation count.
        annotationCount.textContent = annotationNum;

        // Focus on the next marker or the first annotation text.
        if (markerId !== 0) {
            const nextMarker = document.getElementById(markerId);
            setTimeout(() => nextMarker.focus(), 0);
        } else {
            const firstTextArea = document.querySelector('.felement .ftextarea');
            if (firstTextArea) {
                setTimeout(() => firstTextArea.focus(), 0);
            }
        }
    }

    /**
     * Updates the annotation marker and replaces the current marker with a visual strong element.
     *
     * @param {number} annotationNum The number to associate with the current annotation
     * @returns {number} The ID of the next marker if it exists, otherwise 0
     */
    markNewAnnotation(annotationNum) {
        // Get the current marker and list of all markers.
        const markers = Array.from(document.querySelectorAll('span.ouwiki-annotation-marker'));
        let nextMarkerId = 0;

        // Find the next marker after the current one.
        markers.forEach((marker, index) => {
            if (marker.id === this.currentMarker) {
                if (index < markers.length - 1) {
                    // Get the ID of the next marker.
                    nextMarkerId = markers[index + 1].id;
                }
            }
        });

        // Replace the current marker with a strong element.
        const theMarker = document.getElementById(this.currentMarker);
        if (theMarker) {
            const visualMarker = document.createElement('strong');
            visualMarker.textContent = `(${annotationNum})`;
            theMarker.parentNode.insertBefore(visualMarker, theMarker);
            theMarker.remove();
        }

        return nextMarkerId;
    }
}

export const init = (args) => {
    const annotate = new Annotate();
    annotate.init(args);
};
