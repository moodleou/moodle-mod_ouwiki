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
 * Javascript helper function for wiki
 *
 * @package   mod-wiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var ouwiki_view = {};

YAHOO.util.Event.onDOMReady(init);
ouwikiAddOnLoad(ouwikiOnLoad);

function ouwikiAddOnLoad(fn) {
    var oldHandler = window.onload;
    window.onload = function() {
      if(oldHandler) oldHandler();
      fn();
    }
}

function ouwikiToggleFunction(target, link) {
  return function() {
    if(target.style.display == 'block') {
        target.style.display = 'none';
        link.removeChild(link.firstChild);
        link.appendChild(link.originalLink);
    } else {
        target.style.display = 'block';
        link.originalLink=link.firstChild;
        link.removeChild(link.firstChild);
    }
    return false;
  };
}

function ouwikiKeyFunction(link) {
  return function(e) {
    if((e && e.keyCode==13) || (window.event && window.event.keyCode==13))  {
      link.onclick();
      return false;
    } else {
        return true;
    }
  }
}

function ouwikiShowFormFunction(target, header, link) {
  return function() {

    var form = document.getElementById('ouw_ac_formcontainer');
    if(form.parentNode.firstChild == form) {
        form.parentNode.style.display = 'none';
    }
    if(target == form.parentNode && form.style.display != 'none') {
      form.style.display = 'none';
      return false;
    }
    form.parentNode.removeChild(form);
    target.appendChild(form);
    form.style.display = 'block';

    link.originalLink = link.firstChild;
    link.removeChild(link.firstChild);

    document.getElementById('ouw_ac_section').value =
        header.id ? header.id.substring(5): '';
    document.getElementById('ouw_ac_title').focus();

    return false;
  };
}


function ouwikiSetFields() {
    var createbutton = document.getElementById('ouw_create');
    createbutton.disabled = true;

    var pagename = document.getElementById('ouw_newpagename');
    pagename.style.color = "gray";
    pagename.notusedyet = true;
    pagename.onfocus = function() { ouwikiResetThisField(pagename); };
    pagename.onkeyup = function() { ouwikiClearDisabled(createbutton, pagename); };
    pagename.value = M.str.ouwiki.typeinpagename;

    var addbutton = document.getElementById('ouw_add');
    addbutton.disabled = true;

    var sectionname = document.getElementById('ouw_newsectionname');
    sectionname.style.color = "gray";
    sectionname.notusedyet = true;
    sectionname.onfocus = function() { ouwikiResetThisField(sectionname); };
    sectionname.onkeyup = function() { ouwikiClearDisabled(addbutton, sectionname); };
    sectionname.value = M.str.ouwiki.typeinsectionname;
}

function ouwikiClearDisabled(element, field) {
    if(field.value.length == 0) {
        element.disabled = true;
    } else {
       element.disabled = false;
    }
}

function ouwikiResetThisField(field) {
    if(field.notusedyet) {
        field.value = '';
        field.style.color = "black";
        field.notusedyet = false;
    }
}

function ouwikiShowAllAnnotations(action) {
    annoboxes = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation', 'span');
    for (var box = 0; box < annoboxes.length; box++) {
        annoboxes[box].style.display = action;
        var annotag = annoboxes[box].parentNode;
        var imgtag = annotag.firstChild;
        if (action == "block") {
            imgtag.alt = M.str.ouwiki.collapseannotation;
            imgtag.title = M.str.ouwiki.collapseannotation;
        } else if (action == "none") {
            imgtag.alt = M.str.ouwiki.expandannotation;
            imgtag.title = M.str.ouwiki.expandannotation;
        }
    }
    if(action == "block") {
        ouwikiSwapAnnotationUrl("hide");
    } else if(action == "none") {
        ouwikiSwapAnnotationUrl("show");
    }
}

function ouwikiSwapAnnotationUrl(action){
    var show = document.getElementById("expandallannotations");
    var hide = document.getElementById("collapseallannotations");
    if (action == "hide") {
        show.style.display = "none";
        hide.style.display = "inline";
        setTimeout(function() { hide.focus(); }, 0);
    } else if (action == "show") {
        show.style.display = "inline";
        hide.style.display = "none";
        setTimeout(function() { show.focus(); }, 0);
    }
}

function ouwikiShowHideAnnotation(id) {
    var box = document.getElementById(id);
    var annotag = box.parentNode;
    var imgtag = annotag.firstChild;
    if (box.style.display == "block") {
        box.style.display = "none";
        ouwikiSwapAnnotationUrl("show")
        imgtag.alt = M.str.ouwiki.expandannotation;
        imgtag.title = M.str.ouwiki.expandannotation;
    } else {
        box.style.display = "block";
        imgtag.alt = M.str.ouwiki.collapseannotation;
        imgtag.title = M.str.ouwiki.collapseannotation;
        annoboxes = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation', 'span');
        var allblock = 1;
        for (var i = 0; i < annoboxes.length; i++) {
            if(annoboxes[i].style.display != "block") {
                allblock = 0;
            };
        }
        if(allblock == 1){ouwikiSwapAnnotationUrl("hide");}
    }
}

function setupspans(span) {
    span.style.cursor = "pointer";
    span.tabIndex = "0";
    span.onkeydown = function(e) {
        //Cross browser event object.
        var evt = window.event || e;
        if (evt.keyCode == 13 || evt.keyCode == 32) {
            ouwikiShowHideAnnotation("annotationbox" + span.id.substring(10));
        }
    };
    span.onclick = function() {
        ouwikiShowHideAnnotation("annotationbox" + span.id.substring(10));
    };
}

function ouwikiOnLoad() {
  // set add page and section fields
  if(document.getElementById('ouw_create') != null) {
    ouwikiSetFields();
  }
}

function init() {

    // check to see whether there anno tags to show - either because there are none
    // or show annotations is disabled in ouwiki settings
    var annospans = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation-tag', 'span');
    if (annospans.length > 0) {
        ouwikiShowAllAnnotations("none");
        for (var span = 0; span < annospans.length; span++) {
            setupspans(annospans[span]);
        }
        setupAnnotationIcons();
    }
}

M.mod_ouwiki = {
    Y : null,

    /**
     * Main init function called from HTML.
     */
    init : function(Y) {
        this.Y = Y;

        // TODO: Change wiki JavaScript to actually use Moodle 2 style. At
        // present this is mostly here in order to pass language strings.

        // check to see whether there anno tags to show - either because there are none
        // or show annotations is disabled in ouwiki settings
        var icon = Y.one('#showannotationicons');
        if (icon) {
            // Turn the annotation icon show/hide links to use JS
            icon.on('click', function(e) {
                e.preventDefault();
                M.mod_ouwiki.show_annotation_icons(true);
                var hide = document.getElementById("hideannotationicons");
                setTimeout(function() { hide.focus(); }, 0);
            });
        }
        icon = Y.one('#hideannotationicons');
        if (icon) {
            icon.on('click', function(e) {
                e.preventDefault();
                M.mod_ouwiki.show_annotation_icons(false);
                var show = document.getElementById("showannotationicons");
                setTimeout(function() { show.focus(); }, 0);
            });
        }
    },

    /**
     * Called when user selects to show or hide the annotations. Does two
     * things: makes AJAX call to set the option, and adds the class to hide
     * the icons.
     * @param show If true, shows icons
     */
    show_annotation_icons : function(show) {
        // Set or remove the class
        var container = this.Y.one('.ouwiki-content');
        var hideclass = 'ouwiki-hide-annotations';
        if (show) {
            container.removeClass(hideclass);
        } else {
            container.addClass(hideclass);
        }

        // Get URL from original link
        var url = this.Y.one(show ? '#showannotationicons' : '#hideannotationicons').get('href');

        // Add on the 'ajax' marker
        url += '&ajax=1';

        // Request it with AJAX, ignoring result
        this.Y.io(url);
    }
};
