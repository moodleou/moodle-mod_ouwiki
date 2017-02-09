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
 * 'Wiki changes' page. Displays a list of recent changes to the wiki. You
 * can choose to view all changes or only new pages.
 *
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */

global $ouwiki_nologin;
$ouwiki_nologin = true;

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->dirroot.'/mod/ouwiki/basicpage.php');

$magic = required_param('magic', PARAM_RAW);
if ($magic != $subwiki->magic) {
    header('HTTP/1.0 404 not found');
    die('Incorrect magic number');
}

$newpages = optional_param('type', '', PARAM_ALPHA) == 'pages';
$rss = optional_param('format', '', PARAM_RAW) === 'rss';

// Get basic wiki parameters
$wikiparams = ouwiki_display_wiki_parameters('', $subwiki, $cm);

// Get changes
if ($newpages) {
    $changes = ouwiki_get_subwiki_recentpages($subwiki->id, 0, OUWIKI_FEEDSIZE);
    $wikiparams = $wikiparams . '&amp;type=pages';
} else {
    $changes = ouwiki_get_subwiki_recentchanges($subwiki->id, 0, OUWIKI_FEEDSIZE);
}

$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$oldbrowser =
  (!preg_match('/Opera/', $useragent) && preg_match('/MSIE [456]/', $useragent)) ||
  preg_match('/Firefox\/1\./', $useragent);

if ($oldbrowser) {
    header('Content-Type: text/xml; charset=UTF-8');
} else if ($rss) {
    header('Content-Type: application/rss+xml; charset=UTF-8');
} else {
    header('Content-Type: application/atom+xml; charset=UTF-8');
}

$a = new StdClass;
$a->course = htmlspecialchars($course->shortname);
$a->name = htmlspecialchars($ouwiki->name);
$a->subtitle = get_string($newpages ? 'tab_index_pages' : 'tab_index_changes', 'ouwiki');
$feedtitle = get_string('feedtitle', 'ouwiki', $a);
$feedlink = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
$feeddescription=get_string('feeddescription'. ($newpages ? 'pages' : 'changes'), 'ouwiki');

// Domain name, used for IDs (we assume this is owned by site operator in 2007)
$domainname = preg_replace('/^.*\/\/(www\.)?(.*?)\/.*$/', '$2', $CFG->wwwroot);
$id = 'tag:'.$domainname.',2007:ouwiki/'.$ouwiki->id.'/wikihistory/'.($newpages ? 'pages' : 'changes');

$pagelink = $CFG->wwwroot . '/mod/ouwiki/wikihistory.php?' . $wikiparams;

print '<?xml version="1.0"?>
<?xml-stylesheet type="text/xsl" href="'.$CFG->wwwroot.'/mod/ouwiki/feed.xsl"?>';
if ($rss) {
    print '
<rss version="2.0">
  <channel>
    <title>'.$feedtitle.'</title>
    <description>'.$feeddescription.'</description>
    <link>' . $pagelink . '</link>
    <pubDate>' . date('r', !empty($changes) ? reset($changes)->timecreated : time()). '</pubDate>';
} else {
    print '
<feed xmlns="http://www.w3.org/2005/Atom">
  <link rel="self" href="'.$feedlink.'"/>
  <title>'.$feedtitle.'</title>
  <subtitle>'.$feeddescription.'</subtitle>
  <link href="' . $pagelink . '"/>
  <updated>' . date('c', !empty($changes) ? reset($changes)->timecreated : time()) . '</updated>
  <author>
    <name>Wiki system</name>
  </author>
  <id>'.$id.'</id>';
}

foreach ($changes as $change) {
    $pageparams = ouwiki_display_wiki_parameters($change->title, $subwiki, $cm);

    $a = new StdClass;
    $a->name = htmlspecialchars(fullname($change));
    $a->date = ouwiki_nice_date($change->timecreated);

    $itemtitle = $ouwiki->name . ' - ' . ($change->title === ''
            ? get_string('startpage', 'ouwiki') : htmlspecialchars($change->title));
    $itemlink = $CFG->wwwroot.'/mod/ouwiki/view.php?'.$pageparams;
    if (!empty($change->previousversionid)) {
        $a->url = $CFG->wwwroot.'/mod/ouwiki/diff.php?'.$pageparams.'&amp;v1='.
            $change->previousversionid.'&amp;v2='.$change->versionid;
        $a->main = get_string('feedchange', 'ouwiki', $a);
    } else {
        $a->main = get_string('feednewpage', 'ouwiki', $a);
    }
    $itemdescription = get_string('feeditemdescriptiondate', 'ouwiki', $a);
    if ($rss) {
        // The 'permalink' guid just points to the wiki history page but with a unique-ifying versionid on end
        print '
<item>
  <title>'.$itemtitle.'</title>
  <link>'.$itemlink.'</link>
  <pubDate>'.date('r', $change->timecreated).'</pubDate>
  <description>'.htmlspecialchars($itemdescription).'</description>
  <guid>'.$CFG->wwwroot.'/mod/ouwiki/wikihistory.php?'.$wikiparams.'#v'.$change->versionid.'</guid>
</item>';
    } else {
        print '
<entry>
  <title>'.$itemtitle. '</title>
  <link href="'.$itemlink.'"/>
  <id>'.$id.'/'.$change->versionid.'</id>
  <updated>'.date('c', $change->timecreated).'</updated>
  <summary type="xhtml"><div xmlns="http://www.w3.org/1999/xhtml">'.$itemdescription.'</div></summary>
</entry>';
    }
}

if ($rss) {
    print '</channel></rss>';
} else {
    print '</feed>';
}
