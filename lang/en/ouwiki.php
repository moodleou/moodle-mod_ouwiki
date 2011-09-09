<?php
$string['modulename'] = 'OU wiki';
$string['modulenameplural'] = 'OU wikis';
$string['pluginadministration'] = 'OU wiki administration';
$string['pluginname'] = 'OU wiki';

$string['summary'] = 'Summary';

$string['attachments'] = 'Attachments';
$string['noattachments'] = 'No attachments';

$string['subwikis'] = 'Sub-wikis';
$string['subwikis_single'] = 'Single wiki for course';
$string['subwikis_groups'] = 'One wiki per group';
$string['subwikis_individual'] = 'Separate wiki for every user';

$string['timeout']='Time allowed for edit';
$string['timeout_none']='No timeout';

$string['editbegin']='Allow editing from';
$string['editend']='Prevent editing from';

$string['wouldyouliketocreate']='Would you like to create it?';
$string['pagedoesnotexist']='This page does not yet exist in the wiki.';
$string['startpagedoesnotexist']='This wiki\'s start page has not yet been created.';
$string['createpage']='Create page';

$string['recentchanges']='Latest edits';
$string['seedetails']='full history';
$string['startpage']='Start page';

$string['tab_view']='View';
$string['tab_edit']='Edit';
$string['tab_annotate']='Annotate';
$string['tab_discuss']='Discuss';
$string['tab_history']='History';

$string['preview']='Preview';
$string['previewwarning']='The following preview of your changes has not yet been saved.
<strong>If you do not save changes, your work will be lost.</strong> Save using the button
at the end of the page.';

$string['wikifor']='Viewing wiki for: ';
$string['changebutton']='Change';

$string['advice_edit']='
<p>Edit the page below.</p>
<ul>
<li>Make a link to another page by typing the page name in double square brackets: [[page name]]. The link will become active once you save changes.</li>
<li>To create a new page, first make a link to it in the same way. {$a}</li>
</ul>
';

$string['advice_annotate']='
<p>Annotate the page below.</p>
<ul>
<li>To annotate click one of the annotation markers and enter the required text.</li>
<li>New and existing annotations can be deleted by removing all the text in the form below.</li>
</ul>
';

$string['pagelockedtitle']='This page is being edited by somebody else.';
$string['pagelockeddetails']='{$a->name} started editing this page at {$a->lockedat}, and was
still editing it as of {$a->seenat}. You cannot edit it until they finish. ';
$string['pagelockeddetailsnojs']='{$a->name} started editing this page at {$a->lockedat}. They
have until {$a->nojs} to edit. You cannot edit it until they finish.';
$string['pagelockedtimeout']='Their editing slot finishes at {$a}.';
$string['pagelockedoverride']='You have special access to cancel their edit and unlock the page.
If you do this, whatever they have entered will be lost! Please think carefully before clicking
the Override button.';
$string['tryagain']='Try again';
$string['overridelock']='Override lock';

$string['savefailtitle']='Page cannot be saved';
$string['savefaillocked']='While you were editing this page, somebody else obtained the page lock.
(This could happen in various situations such as if you are using an unusual browser or have
Javascript turned off.) Unfortunately, your changes cannot be saved at this time.';
$string['savefaildesynch']='While you were editing this page, somebody else managed to make a change.
(This could happen in various situations such as if you are using an unusual browser or have
Javascript turned off.) Unfortunately, your changes cannot be saved because that would overwrite the
other person\'s changes.';
$string['savefailcontent']='Your version of the page is shown below so that you can copy and paste
the relevant parts into another program. If you put your changes back on the wiki later, be careful
you don\'t overwrite somebody else\'s work.';
$string['returntoview']='View current page';

$string['lockcancelled'] = 'Your editing lock has been overridden and somebody else is now editing this page. If you wish to keep your changes, please select and copy them before clicking Cancel; then try to edit again.';
$string['nojsbrowser'] = 'Our apologies, but you are using a browser we do not fully support.';
$string['nojsdisabled'] = 'You have disabled JavaScript in your browser settings.';
$string['nojswarning'] = 'As a result, we can only hold this page for you for {$a->minutes} minutes. Please ensure that you save your changes by {$a->deadline} (it is currently {$a->now}). Otherwise, somebody else might edit the page and your changes could be lost';

$string['countdowntext'] = 'This wiki allows only {$a} minutes for editing. Make your changes and click Save or Cancel before the remaining time (to right) reaches zero.';
$string['countdownurgent'] = 'Please finish or cancel your edit now. If you do not save before time runs out, your changes will be saved automatically.';


$string['advice_history']='<p>The table below displays all changes that have been made to <a href="{$a}">the current page</a>.</p>
<p>You can view old versions or see what changed in a particular version. If you want to compare any two versions, select the relevant checkboxes and click \'Compare selected\'.</p>';

$string['changedby']='Changed by';
$string['compare']='Compare';
$string['compareselected']='Compare selected';
$string['changes']='changes';
$string['actionheading']='Actions';

$string['mustspecify2']='You must specify exactly two versions to compare.';

$string['oldversion']='Old version';
$string['previousversion']='Previous: {$a}';
$string['nextversion']='Next: {$a}';
$string['currentversion']='Current version';
$string['savedby']='saved by {$a}';
$string['system']='the system';
$string['advice_viewold']='You are viewing an old version of this page.';

$string['index']='Wiki index';
$string['tab_index_alpha']='Alphabetical';
$string['tab_index_tree']='Structure';

$string['lastchange']='Last change: {$a->date} / {$a->userlink}';
$string['orphanpages']='Unlinked pages';

$string['missingpages']='Missing pages';
$string['advice_missingpages']='These pages are linked to, but have not yet been created.';
$string['advice_missingpage']='This page is linked to, but has not yet been created.';
$string['frompage']='from {$a}';
$string['frompages']='from {$a}...';

$string['changesnav']='Changes';
$string['advice_diff']='The older version is shown on the
left<span class=\'accesshide\'> under the heading Older version</span>, where
deleted text is highlighted. Added text is indicated in the newer version on
the right<span class=\'accesshide\'> under the heading Newer
version</span>.<span class=\'accesshide\'> Each change is indicated by a pair
of images before and after the added or deleted text, with appropriate
alternative text.</span>';
$string['diff_nochanges']='This edit did not make changes to the actual text, so no differences are
highlighted below. There may be changes to appearance.';
$string['diff_someannotations']='This edit did not make changes to the actual text, so no differences are
highlighted below, however annotations have been changed. There may also be changes to appearance.';
$string['returntohistory']='(<a href=\'{$a}\'>Return to history view</a>.)';
$string['addedbegins']='[Added text follows]';
$string['addedends']='[End of added text]';
$string['deletedbegins']='[Deleted text follows]';
$string['deletedends']='[End of deleted text]';


$string['ouwiki:edit']='Edit wiki pages';
$string['ouwiki:view']='View wikis';
$string['ouwiki:overridelock']='Override locked pages';
$string['ouwiki:viewgroupindividuals']='Per-user subwikis: view same group';
$string['ouwiki:viewallindividuals']='Per-user subwikis: view all';
$string['ouwiki:viewcontributions']='View list of contributions organised by user';

$string['wikirecentchanges']='Wiki changes';
$string['wikirecentchanges_from']='Wiki changes (page {$a})';
$string['advice_wikirecentchanges_changes']='<p>The table below lists all changes to any page on this wiki, beginning with the latest changes. The most recent version of each page is highlighted.</p>
<p>Using the links you can view a page as it looked after a particular change, or see what changed at that moment.</p>';
$string['advice_wikirecentchanges_changes_nohighlight']='<p>The table below lists all changes to any page on this wiki, beginning with the latest changes.</p>
<p>Using the links you can view a page as it looked after a particular change, or see what changed at that moment.</p>';
$string['advice_wikirecentchanges_pages']='<p>This table shows when each page was added to the wiki, beginning with the most recently-created page.</p>';
$string['wikifullchanges']='View full change list';
$string['tab_index_changes']='All changes';
$string['tab_index_pages']='New pages';
$string['page']='Page';
$string['next']='Older changes';
$string['previous']='Newer changes';

$string['newpage']='first version';
$string['current']='current';
$string['currentversionof']='Current version of ';

$string['linkedfrom']='Pages that link to this one';
$string['linkedfromsingle']='Page that links to this one';

$string['editpage']='Edit page';
$string['editsection']='Edit section';

$string['editingpage']='Editing page';
$string['editingsection']='Editing section: {$a}';
$string['editedby'] = 'Edited by {$a}';

$string['annotatingpage']='Annotating page';

$string['historyfor']= 'History for';
$string['historycompareaccessibility']='Select {$a->lastdate} {$a->createdtime}';

$string['timelocked_before']='This wiki is currently locked. It can be edited from {$a}.';
$string['timelocked_after']='This wiki is currently locked and can no longer be edited.';

$string['returntopage']='Return to wiki page';

$string['savetemplate']='Save wiki as template';
$string['template']='Template';

$string['contributionsbyuser']='Contributions by user';
$string['changebutton']='Change';
$string['contributionsgrouplabel']='Group';
$string['nousersingroup']='The selected group contains no users.';
$string['nochanges']='Users who made no contribution';
$string['contributions']='<strong>{$a->pages}</strong> new page{$a->pagesplural}, <strong>{$a->changes}</strong> other change{$a->changesplural}.';

$string['entirewiki']='Entire wiki';
$string['onepageview']='You can view all pages of this wiki at once for convenient printing or permanent reference.';
$string['format_html']='View online';
$string['format_rtf']='Download in word processor format';
$string['format_template']='Download as wiki template file';
$string['savedat']='Saved at {$a}';

$string['feedtitle']='{$a->course} wiki: {$a->name} - {$a->subtitle}';
$string['feeddescriptionchanges']='Lists all changes made to the wiki. Subscribe to this feed if you want to be updated whenever the wiki changes.';
$string['feeddescriptionpages']='Lists all new pages on the wiki. Subscribe to this feed if you want to be updated whenever someone creates a new page.';
$string['feeddescriptionhistory']='Lists all changes to this individual wiki page. Subscribe to this feed if you want to be updated whenever someone edits this page.';
$string['feedchange']='Changed by {$a->name} (<a href=\'{$a->url}\'>view change</a>)';
$string['feednewpage']='Created by {$a->name}';
$string['feeditemdescriptiondate']='{$a->main} on {$a->date}.';
$string['feeditemdescriptionnodate']='{$a->main}.';
$string['feedsubscribe']='You can subscribe to a feed containing this information: <a href=\'{$a->atom}\'>Atom</a> or <a href=\'{$a->rss}\'>RSS</a>.';
$string['feedalt']='Subscribe to Atom feed';


$string['olderversion']='Older version';
$string['newerversion']='Newer version';


$string['completionpagesgroup']='Require new pages';
$string['completionpages']='User must create new pages:';
$string['completionpageshelp']='requiring new pages to complete';
$string['completioneditsgroup']='Require edits';
$string['completionedits']='User must make edits:';
$string['completioneditshelp']='requiring edits to complete';

$string['reverterrorversion'] = 'Cannot revert to nonexistent page version';
$string['reverterrorcapability'] = 'You do not have permission to revert to an earlier version';
$string['revert'] = 'Revert';
$string['revertversion'] = 'Revert';
$string['revertversionconfirm']='<p>This page will be returned to the state it was in as of {$a}, discarding all changes made since then. However, the discarded changes
will still be available in the page history.</p><p>Are you sure you want to revert to this version of the page?</p>';

$string['deleteversionerrorversion'] = 'Cannot delete nonexistent page version';
$string['viewdeletedversionerrorcapability'] = 'Error viewing page version';
$string['deleteversionerror'] = 'Error deleting page version';
$string['pagedeletedinfo']='Some deleted versions are shown in the list below. These are visible only to users with permission to delete versions. Ordinary users do not see them at all.';
$string['undelete'] = 'Undelete';
$string['advice_viewdeleted']='You are viewing a deleted version of this page.';

$string['csvdownload']='Download in spreadsheet format (UTF-8 .csv)';
$string['excelcsvdownload']='Download in Excel-compatible format (.csv)';

$string['create']='Create';
$string['createnewpage']='Create new page';
$string['typeinpagename']='Type page name here';
$string['add']='Add';
$string['typeinsectionname']='Type section title here';
$string['addnewsection']='Add new section to this page';
$string['createdbyon'] = 'created by {$a->name} on {$a->date}';

$string['numedits'] = '{$a} edit(s)';
$string['overviewnumentrysince1'] = 'new wiki entry since last login.';
$string['overviewnumentrysince'] = 'new wiki entries since last login.';

$string['pagenametoolong'] = 'The page name is too long. Use a shorter page name.';
$string['pagenameisstartpage'] = 'The page name is the same as the start page. Use a different page name.';

$string['ouwiki:lock'] = 'Allowed to lock and unlock pages';
$string['ouwiki:annotate'] = 'Allowed to annotate';
$string['orphanedannotations'] = 'Lost annotations';
$string['annotationsystem'] = 'Annotation system';
$string['addannotation'] = 'Add annotation';
$string['annotations'] = 'Annotations';
$string['deleteorphanedannotations'] = 'Delete lost annotations';
$string['lockediting'] = 'Lock wiki - no editing';
$string['lockpage'] = 'Lock page';
$string['unlockpage'] = 'Unlock page';
$string['annotate'] = 'Annotate';
$string['annotation'] = 'Annotation';
$string['annotationmarker'] = 'Annotation marker';
$string['cannotlockpage'] = 'The page could not be locked, your changes have not been saved.';
$string['thispageislocked'] = 'This wiki page is locked and cannot be edited.';
$string['emptypagetitle'] = 'The new page title must not be blank.';
$string['duplicatepagetitle'] = 'The new page title must not be the same as one of the existing page titles.';

$string['search'] = 'Search this wiki';
$string['search_help'] = 'This search option allows you to search within the wiki you are currently viewing.

To begin searching please enter a keyword within the search text box and press the arrow button.

You will be taken to a results page where your keyword will be displayed within the search text box.
 You will be presented with a page of search results where your keyword is featured.

From this results page you will also have the option to ‘Search the rest of this website’. Click on the
 link and you will be taken to a ‘Your search options’. Your keyword will be carried through but you
 will have the ability to change your keyword and search again. You will also be able to choose to
 search all of your forums or the OU Library.';

$string['sizewarning'] = 'This wiki page is very large and may operate slowly. 
If possible, please split the content into logical chunks and 
place it on separate linked pages.';

$string['displayversion'] = 'OU wiki version: <strong>{$a}</strong>';

// OU only
$string['externaldashboardadd'] = 'Add wiki to dashboard';
$string['externaldashboardremove'] = 'Remove wiki from dashboard';

// Wiki Form Help
$string['completion_help'] = '
<ul>
<li>
If you choose "Require new pages" then the wiki will be marked as complete for
a user once they have created the specified number of pages. With this option,
users have to create pages from scratch; if somebody else creates the page and
they then edit it, it doesn\'t count.
</li>

<li>
If you choose "Require edits" then the wiki will be marked as complete for a
user once they make a certain number of edits. The user could be editing
lots of pages, or editing the same page lots of times; either counts.
</li>
</ul>

<p>
Note that
writing the first version of a page also counts as an edit, so if you want
somebody to create a page <i>and</i> make at least one edit other than that,
set pages to 1 and edits to 2.
</p>';

$string['annotation_help'] = '<p>A user, with the appropriate permission, can add inline annotations to the wiki.</p>';
$string['editbegin_help'] = '<p>If you enable this option the wiki enters read-only mode until the given date. In read-only mode users can see pages, navigate between them, view history, and participate in discussions, but they cannot edit pages.</p>';
$string['editend_help'] = 'If you enable this option the wiki enters read-only mode from the given date onwards.';
$string['createlinkedwiki'] = 'Creating a new page';
$string['createlinkedwiki_help'] = '
<p>
Creating wiki pages can be confusing if you aren\'t familiar with wikis.
</p>
<ul>
<li>A key principle is that pages on a wiki should be <strong>linked together</strong> in some way.</li>
<li>In order to ensure this happens, you have to create a <strong>link to the new page</strong> before you can create the new page itself.</li>
</ul>
<h3>Wiki links</h3>
<p>
When editing a page, links are created by typing the title of a page you want to link to inside double square brackets.
</p>
<ul>
<li>If you wish to link to a page titled <strong>Fish</strong>, you would type <strong>[[Fish]]</strong>.</li>
</ul>
<h3>New pages</h3>
<p>
To create a new page:
</p>
<ol>
<li>Think of a <strong>title</strong> for your page. The title should be different to the titles of other pages on the wiki.
It should describe the content of your page.
An example title might be <strong>Frogs and other amphibians</strong>.</li>
<li>Decide which page should <strong>link</strong> to your new page. If the wiki is small this might be the
start page. Otherwise, find an appropriate page.</li>
<li><strong>Edit</strong> the page that will contain the link.</li>
<li>Find the point where you would like the link to go, and type it in: <strong>[[Frogs and other amphibians]]</strong>.</li>
<li><strong>Save</strong> this change. The link you have created should appear, ready for use.</li>
<li><strong>Click</strong> the link. You will be asked whether you want to create a new page.</li>
<li>Click <strong>Yes</strong>. The edit window appears for your new page.</li>
<li><strong>Type in</strong> the initial text of your page, then <strong>save</strong> it. Your page is now created.</li>
</ol>
<h3>Hints</h3>
<ul>
<li>If you\'re creating multiple pages, you might want to make all the links in one go.</li>
<li>You can make links to new pages even when you personally don\'t intend to create the new page.
When you do that, the "missing" pages are indicated in the wiki index view. This could be used
to indicate that you think the page should exist.</li>
<li>Be careful about titles - once a page has been created, the title can\'t be changed.</li>
</ul>';
$string['allowediting_help'] = '
<p>
If you enable this option the wiki enters read-only mode until the given date. In read-only mode
users can see pages, navigate between them, view history, and participate in discussions, but they
cannot edit pages.
</p>

<h2>Prevent editing from</h2>

<p>
If you enable this option the wiki enters read-only mode from the given date onwards.
</p>';
$string['modulename_help'] = '
<p>
A wiki is a web-based system that lets users edit a set of linked pages. In Moodle, you would normally
use a wiki when you want your students to create content.
</p>

<p>
The OU wiki has a variety of options. Please see the individual help by each item for more information.
</p>';
$string['subwikis_help'] = '
<ul>
<li><strong>Single wiki for course</strong><br />
This wiki behaves as one single wiki. Everybody on the course sees the same pages.</li>
<li><strong>One wiki per group</strong><br />
Members of each group see an entirely separate copy of the wiki (sub-wiki) specific to their
group. You can only see pages created by people in the same group. If you are in
more than one group, or you have permissions that allow you to view all groups,
you get a dropdown to choose a group.</li>
<li><strong>Separate wiki for every user</strong><br />
Every single user gets an entirely different wiki. You can only see your own wiki unless
you have permissions that allow you to view others, when you get a dropdown to choose
a user. (This can be used as a way for students to contribute work, although you should
consider other ways to achieve this such as the Assessment activity.)</li>
</ul>

<p>
Note that the group option works with the chosen grouping. It will ignore groups in other
groupings.
</p>';
$string['summary_help'] = '
<p>
If you enter a summary it will appear on the start page of the wiki. The summary appears
above the normal, editable wiki text and cannot itself be edited by users.
</p>

<p>
Summaries are entirely optional and your wiki may not need one. If you don\'t need a
summary, just leave the box blank.
</p>';
$string['template_help'] = '
<p>
A template is a predefined set of wiki pages. When a template is set, the wiki starts off
with the content defined in the template.
</p>

<p>
The template applies to each subwiki; in "One wiki per group" mode, for example, each
group\'s wiki is initialised with the pages in the template.
</p>

<p>
To create a template, write the pages you want on any wiki, then visit the Index page and
click the "Save wiki as template" button. (You can also manually create templates in other
software; it is an extremely simple XML format. Look at a saved template to see the format.)
</p>

<p>
You cannot change the template after the wiki has been created. If you want to do this,
delete the wiki entirely, then create a new one using the template.
</p>';
$string['timeout_help'] = '
<p>
If you select a timeout, people editing the wiki are only allowed to edit it for a given time.
The wiki locks pages while they are being edited (so that two people can\'t edit the same page
at once), so setting a timeout prevents the wiki becoming locked for others.
</p>

<h3>What users see</h3>

<p>
When timeout is enabled, users see a countdown when they edit a page. If the countdown reaches
zero, their browser will automatically save any changes and stop editing.
</p>

<h3>Users without Javascript enabled</h3>

<p>
This option has no effect on users who don\'t have Javascript enabled or who have old browsers.
A fifteen-minute timeout always applies to these users. When they edit a page, it displays the time
by which they must save it; if they do not, they might lose their work.
</p>

<h3>Why you might not need this option</h3>

<p>
Even when this option is turned off, locks are automatically discarded in the following situations after
a user has begun to edit a page:
</p>

<ul>
<li>Without saving changes or cancelling, the user moves to a different page.</li>
<li>The user closes their browser.</li>
<li>The user\'s computer crashes.</li>
<li>The user loses their Internet connection.</li>
</ul>

<p>
In these situations the lock is automatically removed after about two minutes.
</p>

<p>
In addition, tutors and course staff have (by default) the ability to override any lock at any time.
</p>

<h3>What this option doesn\'t do</h3>

<p>
This option doesn\'t stop somebody holding on to a page and preventing other users from editing it if
they are very determined. They could edit a page and wait until the timeout is about to expire before
saving changes then very quickly editing it again.
</p>';
