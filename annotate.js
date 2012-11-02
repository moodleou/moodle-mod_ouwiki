/* Javascript for adding annotations */

var ouwiki_annotate = {};

var newWin = null;
var currentMarker = "";

YAHOO.util.Event.onDOMReady(ouwiki_annotate_init);

function ouwiki_annotate_init() {
    var save = ouwiki_annotate_config.save;
    var cancel = ouwiki_annotate_config.cancel;

    // Define various event handlers for Dialog
    var handleSubmit = function() {
        var data = this.getData();
        newAnnotation(data.annotationtext);
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
    var annotationdialog = YAHOO.util.Dom.get('annotationdialog');
    if (annotationdialog) {
        YAHOO.util.Dom.get(document.body).appendChild(annotationdialog);
    }
    ouwiki_annotate.annotationdialog = new YAHOO.widget.Dialog('annotationdialog', {
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
    ouwiki_annotate.annotationdialog.callback = { success: handleSuccess, failure: handleFailure };

    ouwiki_annotate.annotationdialog.render();
    var div = document.getElementById('annotationdialog');
    if (div) {
        div.style.display = 'block';
    }

    // setup keycodes
    markers = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation-marker', 'span');
    for (var i = 0; i < markers.length; i++) {
        setupmarkers(markers[i], ouwiki_annotate.annotationdialog);
    }

    // Make escape close the dialogue.
    ouwiki_annotate.annotationdialog.cfg.setProperty('keylisteners', [new YAHOO.util.KeyListener(
            document, {keys:[27]}, function(types, args, obj) { ouwiki_annotate.annotationdialog.hide();
    })]);

    // Nasty hack, remove once the YUI bug causing MDL-17594 is fixed.
    // https://sourceforge.net/tracker/index.php?func=detail&aid=2493426&group_id=165715&atid=836476
    var elementcauseinglayoutproblem = document.getElementById('_yuiResizeMonitor');
    if (elementcauseinglayoutproblem) {
        elementcauseinglayoutproblem.style.left = '0px';
    }
}

function setupmarkers(marker, dialog) {
    marker.style.cursor = "pointer";
    marker.tabIndex = "0";
    marker.onkeydown = function(e) {
        var keycode = null;
        if(e){
            keycode = e.which;
        } else if (window.event) {
            keycode = window.event.keyCode;
        }
        if(keycode == 13 || keycode == 32){
            // call the function that handles adding an annotation
            openNewWindow(marker, dialog);
            return false;
        }
    };

    marker.onclick = function() {
        openNewWindow(marker, dialog);
        return false;
    };
}

function openNewWindow(marker,mydialog1) {
    currentMarker = marker.id;
    mydialog1.show();
}

function newAnnotation(newtext) {
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
    //var textareas = YAHOO.util.Dom.getElementsByClassName('felement ftextarea', 'div');
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

    var markerid = markNewAnnotation(annotationnum);

    newfitem.style.display = 'block';
    annotationcount.firstChild.nodeValue = annotationnum;

    // Set focus to next marker or list of annotations to be added if last marker.
    if (markerid != 0) {
        // Another annotation marker found.
        var nextmarker = document.getElementById(markerid);
        setTimeout(function() { nextmarker.focus(); }, 0);
    } else {
        // At end so focus on first annotation text if it exists.
        var divannotext = YAHOO.util.Dom.getElementsByClassName('felement ftextarea', 'div');
        if (divannotext.length > 0) {
            var annotext = divannotext[0].firstChild;
            setTimeout(function() { annotext.focus(); }, 0);
        } else {
            // Do nothing.
        }
    }

}

function markNewAnnotation(annotationnum) {

    // Get next marker using currentMarker as the starting point.
    var markers = YAHOO.util.Dom.getElementsByClassName('ouwiki-annotation-marker', 'span');
    // Loop through markers getting next marker object after current marker
    var id = 0;
    var nextmarkerid = 0;
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

function ouwiki_yui_workaround(e) {
    // YUI does not send the button pressed with the form submission, so copy
    // the button name to a hidden input.
    var submitbutton = YAHOO.util.Event.getTarget(e);
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = submitbutton.name;
    input.value = 1;
    submitbutton.form.appendChild(input);
}
