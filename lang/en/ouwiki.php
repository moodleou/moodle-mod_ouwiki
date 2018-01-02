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
$string['modulename'] = 'OU wiki';
$string['modulenameplural'] = 'OU wikis';
$string['pluginadministration'] = 'OU wiki administration';
$string['pluginname'] = 'OU wiki';

$string['lastmodified'] = 'Last edit: {$a}';
$string['strftimerecent'] = '%d %B %y, %H:%M';

$string['summary'] = 'Summary';

$string['attachments'] = 'Attachments';
$string['noattachments'] = 'No attachments';

$string['subwikis'] = 'Sub-wikis';
$string['subwikis_single'] = 'Single wiki for course';
$string['subwikis_groups'] = 'One wiki per group';
$string['subwikis_individual'] = 'Separate wiki for every user';

$string['note'] = 'Note:';
$string['subwikiexist'] = 'Sub-wiki\'s have already been created. Adding a template file only affects
newly created and empty sub-wiki\'s, existing content will remain as at present.';
$string['templatefileexists'] = 'A template file \'{$a}\' is already in use.';

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
<p>Annotate the page below, then click <strong>Save changes</strong>.</p>
<ul>
<li>To annotate click one of the annotation markers and enter the required text.</li>
<li>New and existing annotations can be deleted by removing all the text in the form below.</li>
<li>The numbers in brackets refer to annotations.</li>
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
$string['savefailnetwork'] = '<p>Unfortunately, your changes cannot be saved at this time. This is due to a
network error; the website is temporarily unavailable or you have been signed out. </p><p>Saving has been disabled
on this page. In order to retain any changes you must copy the edited page content, access the Edit page again and then paste in your changes.</p>';

$string['lockcancelled'] = 'Your editing lock has been overridden and somebody else is now editing this page. If you wish to keep your changes, please select and copy them before clicking Cancel; then try to edit again.';
$string['nojsbrowser'] = 'Our apologies, but you are using a browser we do not fully support.';
$string['nojsdisabled'] = 'You have disabled JavaScript in your browser settings.';
$string['nojswarning'] = 'As a result, we can only hold this page for you for {$a->minutes} minutes. Please ensure that you save your changes by {$a->deadline} (it is currently {$a->now}). Otherwise, somebody else might edit the page and your changes could be lost';
$string['jsnotenabled'] = 'Javascript is not enabled in your browser.';
$string['ajaxnotenabled'] = 'AJAX not enabled in your profile.';
$string['jsajaxrequired'] = ' This Annotate page requires Javascript to be enabled in your browser and the AJAX and Javascript setting in your user profile to be set to Yes: use advanced web features.';

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
$string['nowikipages'] = 'This wiki does not have any pages.';

$string['error_export'] = 'An error occurred while exporting wiki data.';
$string['error_nopermission'] = 'You do not have permission to see the content of this page';

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

$string['ouwiki:addinstance'] = 'Add a new OU wiki';
$string['ouwiki:edit']='Edit wiki pages';
$string['ouwiki:view']='View wikis';
$string['ouwiki:overridelock']='Override locked pages';
$string['ouwiki:viewgroupindividuals']='Per-user subwikis: view same group';
$string['ouwiki:viewallindividuals']='Per-user subwikis: view all';
$string['ouwiki:viewcontributions']='View list of contributions organised by user';
$string['ouwiki:deletepage'] = 'Delete wiki pages';
$string['ouwiki:viewparticipation'] = 'View participation of all enrolled users who have access to wiki';
$string['ouwiki:grade'] = 'Grade users who have have access to wiki';
$string['ouwiki:editothers'] = 'Edit the content of any sub-wiki that can be viewed';
$string['ouwiki:annotateothers'] = 'Annotate any sub-wiki that can be viewed';

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
$string['downloadcsv'] = 'Comma separated values text file';

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
$string['addnewsection1']='Add new section';
$string['createdbyon'] = 'created by {$a->name} on {$a->date}';

$string['numedits'] = '{$a} edits';
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
$string['collapseannotation'] = 'Collapse annotation';
$string['expandannotation'] = 'Expand annotation';
$string['annotationmarker'] = 'Annotation marker';
$string['cannotlockpage'] = 'The page could not be locked, your changes have not been saved.';
$string['thispageislocked'] = 'This wiki page is locked and cannot be edited.';
$string['emptypagetitle'] = 'The new page title must not be blank.';
$string['duplicatepagetitle'] = 'The new page title must not be the same as one of the existing page titles.';
$string['emptysectiontitle'] = 'The new section name must not be blank.';

// Wordcount
$string['showwordcounts'] = 'Show word counts';
$string['showwordcounts_help'] = 'If switched on then word counts for the pages will be calculated and displayed at the bottom of the main content.';
$string['numwords'] = 'Words: {$a}';
$string['words'] = 'Words';

// Participation
$string['myparticipation'] = 'My participation';
$string['userparticipation'] = 'User participation';
$string['participationbyuser'] = 'Participation by user';
$string['viewparticipationerrror'] = 'Cannot view user participation.';
$string['pagescreated'] = 'Pages created';
$string['pageedits'] = 'Page edits';
$string['wordsadded'] = 'Words added';
$string['wordsdeleted'] = 'Words deleted';
$string['noparticipation'] = 'No participation to show.';
$string['detail'] = 'detail';
$string['downloadspreadsheet'] = 'Download as spreadsheet';
$string['participation'] = 'Participation';
$string['usergrade'] = 'User grade';
$string['savegrades'] = 'Save grades';
$string['gradesupdated'] = 'Grades updated';
$string['userdetails'] = 'Detail for {$a}';
$string['viewwikistartpage'] = 'View {$a}';
$string['viewwikichanges'] = 'Changes for {$a}';

$string['search'] = 'Search this wiki';
$string['search_help'] = 'Type your search term and press Enter or click the button.

To search for exact phrases use quote marks.

To exclude a word insert a hyphen immediately before the word.

Example: the search term <tt>picasso -sculpture &quot;early works&quot;</tt> will return results for &lsquo;picasso&rsquo; or the phrase &lsquo;early works&rsquo; but will exclude items containing &lsquo;sculpture&rsquo;.';

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

$string['annotationsystem_help'] = 'Enables the Annotation tab, for users with the appropriate permission..

With this tab you can add inline annotations to wiki pages (for example, teacher comments on student work).';
$string['editbegin_help'] = '<p>If you enable this option the wiki enters read-only mode until the given date. In read-only mode users can see pages, navigate between them, view history, and participate in discussions, but they cannot edit pages.</p>';
$string['editend_help'] = 'If you enable this option the wiki enters read-only mode from the given date onwards.';
$string['createlinkedwiki'] = 'Creating a new page';
$string['createlinkedwiki_help'] = 'While editing, you can type a link to a page that doesn&rsquo;t exist yet, such as [[Frogs]]. Then save this page and click on the &lsquo;Frogs&rsquo; link to create the new page.

It is also possible to create new pages from the &lsquo;View&rsquo; tab using the &lsquo;Create new page&rsquo; box.';
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
You can add the template after the wiki has been created. Adding a template only affects
newly created sub-wiki\'s, existing ones will remain as at present. </p>';
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
$string['endannotation'] = 'End of annotation';
$string['expandallannotations'] = 'Expand annotations';
$string['collapseallannotations'] = 'Collapse annotations';
$string['showannotationicons'] = 'Show annotations';
$string['hideannotationicons'] = 'Hide annotations';
$string['errorcoursesubwiki'] = 'Must be &lsquo;No groups&rsquo; unless sub-wikis option is &lsquo;One wiki per group&rsquo;';
$string['errorgroupssubwiki'] = 'Must be enabled when sub-wikis option is &lsquo;One wiki per group&rsquo;';
$string['brokenimage'] = '<span class="imgremoved"> Missing image, not included in template. </span>';

$string['add']='Add';
$string['cancel']='Cancel';
$string['startannotation'] = 'Start of annotation';
$string['changedifferences'] = 'Change differences';

$string['pagecheckboxlabel'] = 'Import page, {$a}';
$string['import'] = 'Import pages';
$string['import_nocontent'] = 'This wiki activity contains no available content to import.';
$string['import_selectsubwiki'] = 'Select wiki';
$string['import_selectsubwiki_help'] = 'Choose from available wikis. Wikis that do not have any content will not be listed.';
$string['import_selectwiki'] = 'Import from {$a}';
$string['import_confirm'] = 'Confirm import';
$string['import_lockedpage'] = 'Page locked';
$string['import_confirm_infoheader'] = 'Import information';
$string['import_confirm_mergeheader'] = 'Page conflicts';
$string['import_confirm_linkheader'] = 'Page links';
$string['import_confirm_from'] = 'Import from:';
$string['import_confirm_pages'] = 'Import pages:';
$string['import_confirm_pages_none'] = 'No pages selected for import.';
$string['import_confirm_pages_help'] = 'Pages you selected to import, plus any additional pages linked from these.';
$string['import_confirm_conflicts'] = 'Page conflicts:';
$string['import_confirm_conflicts_notlocked'] = 'Page not locked';
$string['import_confirm_conflicts_locked'] = 'Page {$a} is locked';
$string['import_confirm_conflicts_lockedwarn'] = 'A page to be overwritten is currently locked. Check you are able to edit this page before trying again.';
$string['import_confirm_conflicts_instruct'] = 'Page \'conflicts\' have been identified where the listed imported page names are the same as an existing page.';
$string['import_confirm_conflicts_label'] = 'Merge setting:';
$string['import_confirm_conflicts_option1'] = 'Merge page content';
$string['import_confirm_conflicts_option2'] = 'Replace existing page content';
$string['import_confirm_linkfrom'] = 'Add links to new pages to:';
$string['import_confirm_linkfrom_help'] = 'Select a page in which links to the top level of the new pages will be added.';
$string['import_confirm_linkfrom_newpage'] = 'New page';
$string['import_confirm_linkfrom_startpage'] = 'Use imported Start page:';
$string['import_confirm_linkfrom_startpage1'] = 'Merge into existing Start page content';
$string['import_confirm_linkfrom_startpage2'] = 'Create new page from imported Start page';
$string['import_process'] = 'Importing pages';
$string['import_process_locked'] = 'A page that is to have content imported into is currently locked;
Either permanently to prevent editing, or temporarily whilst another user is editing content.';
$string['import_process_startpage_locked'] = 'The start page that is to have content imported into is currently locked;
Either permanently to prevent editing, or temporarily whilst another user is editing content.';
$string['import_process_summary'] = 'Summary';
$string['import_process_summary_success'] = 'The import completed successfully.';
$string['import_process_summary_warn'] = 'The import completed with warnings.';
$string['import_process_summary_imported'] = 'Pages imported';
$string['import_process_summary_updated'] = 'Pages updated';
$string['allowimport_help'] = 'Checking the box will add the ability to \'import\' pages from other wikis in the course into the current wiki ';
$string['allowimport'] = 'Link to import pages';
$string['importedstartpage'] = 'Imported start page';
$string['importedpages'] = 'Imported pages';
$string['importedfrom'] = 'Imported from:';
$string['pagesimported'] = 'Pages imported';
$string['unabletoimport'] = 'No wiki\'s available to import from.';
$string['event:ouwikiviewed'] = 'ouwiki view';
$string['event:pageunlock'] = 'ouwiki unlock';
$string['event:pagelock'] = 'ouwiki lock';
$string['event:ouwikiundelete'] = 'ouwiki version undelete';
$string['event:ouwikidelete'] = 'ouwiki version delete';
$string['event:ouwikipagecreated'] = 'ouwiki page created';
$string['event:ouwikipageupdated'] = 'ouwiki page updated';
$string['event:savefailed'] = 'Session fail on page save';
$string['ouwikicrontask'] = 'OU wiki maintenance jobs';
$string['search:activity'] = 'OUWiki - activity information';
$string['search:page_version'] = 'OUWiki - page versions';