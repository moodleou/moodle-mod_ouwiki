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
 * @package   mod_ouwiki
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_ouwiki_view = {
    Y : null,

    init : function(Y, args) {
        // Store the YUI3 object.
        this.Y = Y;

        var _this = this;

        // TODO: Change wiki JavaScript to actually use Moodle 2 style. At
        // present this is mostly here in order to pass language strings.

        // check to see whether there anno tags to show - either because there are none
        // or show annotations is disabled in ouwiki settings
        var icon = this.Y.one('#showannotationicons');
        if (icon) {
            // Turn the annotation icon show/hide links to use JS
            icon.on('click', function(e) {
                e.preventDefault();
                _this.show_annotation_icons(true);
                var hide = document.getElementById("hideannotationicons");
                setTimeout(function() { hide.focus(); }, 0);
            });
        }
        icon = this.Y.one('#hideannotationicons');
        if (icon) {
            icon.on('click', function(e) {
                e.preventDefault();
                _this.show_annotation_icons(false);
                var show = document.getElementById("showannotationicons");
                setTimeout(function() { show.focus(); }, 0);
            });
        }

        // check to see whether there anno tags to show - either because there are none
        // or show annotations is disabled in ouwiki settings
        var annospans = this.Y.all('span.ouwiki-annotation-tag');
        if (annospans) {
            this.ouwikiShowAllAnnotations("none");
            annospans.each(function (annospan) { _this.setupspans(annospan); });
        }

        // set add page and section fields
        if(document.getElementById('ouw_create') != null) {
            this.ouwikiSetFields();
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
    },

    ouwikiToggleFunction : function(target, link) {
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
    },

    ouwikiKeyFunction : function(link) {
        return function(e) {
            if((e && e.keyCode==13) || (window.event && window.event.keyCode==13))  {
                link.onclick();
                return false;
            } else {
                return true;
            }
        };
    },

    ouwikiShowFormFunction : function(target, header, link) {
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
    },

    ouwikiSetFields : function() {
        var _this = this;
        var createbutton = document.getElementById('ouw_create');
        createbutton.disabled = true;

        var pagename = document.getElementById('ouw_newpagename');
        pagename.style.color = "gray";
        pagename.notusedyet = true;
        pagename.onfocus = function() { _this.ouwikiResetThisField(pagename); };
        pagename.onkeyup = function() { _this.ouwikiClearDisabled(createbutton, pagename); };
        pagename.value = M.str.ouwiki.typeinpagename;

        var addbutton = document.getElementById('ouw_add');
        addbutton.disabled = true;

        var sectionname = document.getElementById('ouw_newsectionname');
        sectionname.style.color = "gray";
        sectionname.notusedyet = true;
        sectionname.onfocus = function() { _this.ouwikiResetThisField(sectionname); };
        sectionname.onkeyup = function() { _this.ouwikiClearDisabled(addbutton, sectionname); };
        sectionname.value = M.str.ouwiki.typeinsectionname;
    },

    ouwikiClearDisabled : function(element, field) {
        if (field.value.length == 0) {
            element.disabled = true;
        } else {
            element.disabled = false;
        }
    },

    ouwikiResetThisField : function(field) {
        if (field.notusedyet) {
            field.value = '';
            field.style.color = "black";
            field.notusedyet = false;
        }
    },

    ouwikiShowAllAnnotations : function(action) {
        annoboxes = this.Y.all('span.ouwiki-annotation');
        annoboxes.each(function (annobox) {
            annobox.setStyle('display', action);
            var annotag = annobox.get('parentNode');
            var imgtag = annotag.get('firstChild');
            if (action == "block") {
                imgtag.set('alt', M.str.ouwiki.collapseannotation);
                imgtag.set('title', M.str.ouwiki.collapseannotation);
            } else if (action == "none") {
                imgtag.set('alt', M.str.ouwiki.expandannotation);
                imgtag.set('title', M.str.ouwiki.expandannotation);
            }
        });

        if(action == "block") {
            this.ouwikiSwapAnnotationUrl("hide");
        } else if(action == "none") {
            this.ouwikiSwapAnnotationUrl("show");
        }
    },

    ouwikiSwapAnnotationUrl : function(action){
        var show = document.getElementById("expandallannotations");
        var hide = document.getElementById("collapseallannotations");
        if (show && hide) {
            if (action == "hide") {
                show.style.display = "none";
                hide.style.display = "inline";
            } else if (action == "show") {
                show.style.display = "inline";
                hide.style.display = "none";
        }
        }
    },

    ouwikiShowHideAnnotation : function(id) {
        var box = document.getElementById(id);
        var annotag = box.parentNode;
        var imgtag = annotag.firstChild;
        if (box.style.display == "block") {
            box.style.display = "none";
            this.ouwikiSwapAnnotationUrl("show");
            imgtag.alt = M.str.ouwiki.expandannotation;
            imgtag.title = M.str.ouwiki.expandannotation;
        } else {
            box.style.display = "block";
            imgtag.alt = M.str.ouwiki.collapseannotation;
            imgtag.title = M.str.ouwiki.collapseannotation;
            annoboxes = this.Y.all('span.ouwiki-annotation');
            annoboxes = annoboxes.getDOMNodes();
            var allblock = 1;
            for (var i = 0; i < annoboxes.length; i++) {
                if (annoboxes[i].style.display != "block") {
                    allblock = 0;
                }
            }
            if (allblock == 1) {
                this.ouwikiSwapAnnotationUrl("hide");
            }
        }
    },

    setupspans : function(span) {
        var _this = this;
        span.setStyle('cursor', 'pointer');
        span.set('tabIndex', '0');
        span.on('keydown', function(e) {
            //Cross browser event object.
            var evt = window.event || e;
            if (evt.keyCode == 13 || evt.keyCode == 32) {
                _this.ouwikiShowHideAnnotation("annotationbox" + span.get('id').substring(10));
                span.all('a').item(0).set('tabIndex', -1);
                e.preventDefault();
                return false;
            }
        });
        span.on('click', function() {
            _this.ouwikiShowHideAnnotation("annotationbox" + span.get('id').substring(10));
        });
    }
};

M.mod_ouwiki_annotate = {
    Y : null,
    YAHOO : null,

    init : function(Y, args) {
        // Store the YUI3 and YUI2 object.
        this.Y = Y;
        this.YAHOO = Y.YUI2;

        var _this = this;
        var save = M.util.get_string('add', 'ouwiki');
        var cancel = M.util.get_string('cancel', 'ouwiki');

        // Define various event handlers for Dialog
        var handleSubmit = function() {
            var data = this.getData();
            _this.newAnnotation(data.annotationtext);
            this.submit();
        };
        var handleCancel = function() {
            this.cancel();
        };
        var handleSuccess = function(o) {
            var response = o.responseText;
            response = response.split("<!")[0];
            document.getElementById("resp").innerHTML = response;
        };
        var handleFailure = function(o) {
            alert("Submission failed: " + o.status);
        };

        // Instantiate the Dialog
        var annotationdialog = this.YAHOO.util.Dom.get('annotationdialog');
        if (annotationdialog) {
            this.YAHOO.util.Dom.get(document.body).appendChild(annotationdialog);
        }
        annotationdialog = new this.YAHOO.widget.Dialog('annotationdialog', {
                modal: true,
                width: '100%',
                iframe: true,
                zIndex: 1000, // zIndex must be way above 99 to be above the active quiz tab
                fixedcenter: true,
                visible: false,
                close: true,
                constraintoviewport: true,
                postmethod: 'none',
                buttons: [ { text:save, handler:handleSubmit, isDefault: true },
                { text:cancel, handler:handleCancel } ]
        });

        // Wire up the success and failure handlers
        annotationdialog.callback = { success: handleSuccess, failure: handleFailure };
        annotationdialog.cancel = function() {
            this.hide();
            Y.one('#' + currentMarker).focus();
        };
        annotationdialog.render();
        var div = document.getElementById('annotationdialog');
        if (div) {
            div.style.display = 'block';
        }

        // setup keycodes
        var markers = this.Y.all('span.ouwiki-annotation-marker');
        markers.each(function (marker) {
            _this.setupmarkers(marker, annotationdialog);
        });

        // Make escape close the dialogue.
        annotationdialog.cfg.setProperty('keylisteners', [new this.YAHOO.util.KeyListener(
                document, {keys:[27]}, function(types, args, obj) { annotationdialog.cancel();
        })]);

        // Nasty hack, remove once the YUI bug causing MDL-17594 is fixed.
        // https://sourceforge.net/tracker/index.php?func=detail&aid=2493426&group_id=165715&atid=836476
        var elementcauseinglayoutproblem = document.getElementById('_yuiResizeMonitor');
        if (elementcauseinglayoutproblem) {
            elementcauseinglayoutproblem.style.left = '0px';
        }
    },

    setupmarkers : function(marker, dialog) {
        var _this = this;
        marker.setStyle('cursor', "pointer");
        marker.set('tabIndex', "0");
        marker.on('keydown', function(e) {
            var keycode = null;
            if (e) {
                keycode = e.which;
            } else if (window.event) {
                keycode = window.event.keyCode;
            }
            if(keycode == 13 || keycode == 32) {
                // call the function that handles adding an annotation
                _this.openNewWindow(marker, dialog);
                return false;
            }
        });

        marker.on('click', function() {
            _this.openNewWindow(marker, dialog);
            return false;
        });
    },

    openNewWindow : function(marker, mydialog1) {
        currentMarker = marker.get('id');
        mydialog1.show();
    },

    newAnnotation : function(newtext) {
        // we need the number of the next form textarea
        var annotationcount = document.getElementById('annotationcount');
        var annotationnum = parseInt(annotationcount.firstChild.nodeValue) + 1;

        //create the new form section
        var newfitem = document.createElement('div');
        newfitem.id = 'newfitem'+annotationnum;
        newfitem.className = 'fitem';
        newfitem.style.display = 'none';

        var fitemtitle = document.createElement('div');
        fitemtitle.className = 'fitemtitle';

        var fitemlabel = document.createElement('label');
        fitemlabel.htmlFor = 'id_annotationedit' + annotationnum;
        //create a textnode and add it to the label
        var fitemlabeltext = document.createTextNode(annotationnum);
        fitemlabel.appendChild(fitemlabeltext);
        // append the label to the div
        fitemtitle.appendChild(fitemlabel);

        //create the div for the textarea
        var felement = document.createElement('div');
        felement.className = 'felement ftextarea';

        var textareatext  = document.createTextNode(newtext);
        var felementtextarea = document.createElement('textarea');
        felementtextarea.id = 'id_annotationedit' + annotationnum;
        felementtextarea.name = 'new'+currentMarker.substring(6);
        // we need the textare size set in the moodle form rather than setting explicitly here
        felementtextarea.rows = '3';
        felementtextarea.cols = '40';
        felementtextarea.appendChild(textareatext);
        felement.appendChild(felementtextarea);

        newfitem.appendChild(fitemtitle);
        newfitem.appendChild(felement);

        // insert the new fitem before the last fitem (which is the delete orphaned checkbox)
        var endmarker = document.getElementById('end');
        var fcontainer = endmarker.parentNode.parentNode.parentNode;
        fcontainer.insertBefore(newfitem, endmarker.parentNode.parentNode);

        var markerid = this.markNewAnnotation(annotationnum);

        newfitem.style.display = 'block';
        annotationcount.firstChild.nodeValue = annotationnum;

        // Set focus to next marker or list of annotations to be added if last marker.
        if (markerid != 0) {
            // Another annotation marker found.
            var nextmarker = document.getElementById(markerid);
            setTimeout(function() { nextmarker.focus(); }, 0);
        } else {
            // At end so focus on first annotation text if it exists.
            var divannotext = this.Y.one('.felement .ftextarea');
            if (divannotext) {
                var annotext = divannotext.get('firstChild');
                setTimeout(function() { annotext.focus(); }, 0);
            } else {
                // Do nothing.
            }
        }
    },

    markNewAnnotation : function(annotationnum) {
        // Get next marker using currentMarker as the starting point.
        // And loop through markers getting next marker object after current marker
        var markers = this.Y.all('span.ouwiki-annotation-marker');
        var id = 0;
        var nextmarkerid = 0;
        if (markers) {
            markers = markers.getDOMNodes();
            for (var i = 0; i < markers.length; i++) {
                id = markers[i].id;
                if (currentMarker == id) {
                    if (i == (markers.length - 1) ) {
                        // We are at the end - just break
                        break;
                    } else {
                        // Get next marker id - and get out
                        nextmarkerid = markers[i+1].id;
                        break;
                    }
                }
            }
        }
        markers = null;

        var theMarker = document.getElementById(currentMarker);
        // Create new strong element and replace current marker
        var visualmarker = document.createElement('strong');
        var visualtext = document.createTextNode('('+annotationnum+')');
        visualmarker.appendChild(visualtext);
        theMarker.parentNode.insertBefore(visualmarker, theMarker);
        theMarker.parentNode.removeChild(theMarker);

        return nextmarkerid;
    }
};

M.mod_ouwiki_edit = {
        Y : null,
        init : function(Y, args) {
            this.Y = Y;
            this.YAHOO = Y.YUI2;
            // Trap edit saving and test server is up.
            var btns = Y.all('#save, #preview');
            btns.on('click', function(e) {
                function savefail() {
                    // Save failed, alert of network or session issue.
                    btns.set('disabled', true);
                    var content = M.util.get_string('savefailnetwork', 'ouwiki');
                    var panel = new M.core.alert({
                        title: M.util.get_string('savefailtitle', 'ouwiki'),
                        message: content,
                        render: true,
                        plugins: [Y.Plugin.Drag],
                        modal: true
                    });
                    panel.show();
                    function oncancel(evt) {
                        evt.preventDefault();
                        panel.hide();
                    }
                    e.preventDefault();
                    // Trap cancel and make it a GET - so works with login.
                    var cancel = Y.one('#cancel');
                    cancel.on('click', function(e) {
                        var form = Y.one('#ouwiki_belowtabs #mform1');
                        var text = form.one('#fitem_id_content');
                        var attach = form.one('#fitem_id_attachments');
                        text.remove();
                        attach.remove();
                        form.set('method', 'get');
                    });
                }
                function checksave(transactionid, response, args) {
                    // Check response OK.
                    if (response.responseText != 'ok') {
                        // Send save failed due to login/session error.
                        savefail();
                    }
                }
                var cfg = {
                    method: 'POST',
                    data: 'sesskey=' + M.cfg.sesskey + '&contextid=' + args,
                    on: {
                        success: checksave,
                        failure: savefail
                    },
                    sync: true,// Wait for result so we can cancel submit.
                    timeout: 10000
                };
                Y.io('confirmloggedin.php', cfg);
            });
        }
};
