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
 * Moodle renderer used to display special elements of the ouwiki module
 *
 * @package    mod
 * @subpackage ouwiki
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

class mod_ouwiki_renderer extends plugin_renderer_base {

    /**
     * Print the main page content
     *
     * @param object $subwiki For details of user/group and ID so that
     *   we can make links
     * @param object $cm Course-module object (again for making links)
     * @param object $pageversion Data from page and version tables.
     * @return string HTML content for page
     */
    public function ouwiki_print_page($subwiki, $cm, $pageversion, $gewgaws = null,
            $page = 'history') {
        $output = '';
        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

        global $CFG, $ouwikiinternalre;

        require_once($CFG->libdir . '/filelib.php');

        $pageversion->xhtml = file_rewrite_pluginfile_urls($pageversion->xhtml, 'pluginfile.php',
                $modcontext->id, 'mod_ouwiki', 'content', $pageversion->versionid);

        // Get annotations - only if using annotation system. prevents unnecessary db access
        if ($subwiki->annotation) {
            $annotations = ouwiki_get_annotations($pageversion);
        } else {
            $annotations = '';
        }

        // Title
        $title = is_null($pageversion->title) ? get_string('startpage', 'ouwiki') :
                htmlspecialchars($pageversion->title);

        // setup annotations according to the page we are on
        if ($page == 'view') {
            // create the annotations
            if ($subwiki->annotation && count($annotations)) {
                ouwiki_highlight_existing_annotations(&$pageversion->xhtml, $annotations, 'view');
            }
        } else if ($page == 'annotate') {
            // call function for the annotate page
            ouwiki_setup_annotation_markers(&$pageversion->xhtml);
            ouwiki_highlight_existing_annotations(&$pageversion->xhtml, $annotations, 'annotate');
        }

        // get files up here so we have them for the portfolio button addition as well
        $fs = get_file_storage();
        $files = $fs->get_area_files($modcontext->id, 'mod_ouwiki', 'attachment',
                $pageversion->versionid, "timemodified", false);

        $output .= html_writer::start_tag('div', array('class' => 'ouwiki-content'));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_topheading'));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_heading'));
        $output .= html_writer::tag('h1', format_string($title),
                array('class' => 'ouw_topheading'));

        if ($gewgaws) {
            $output .= $this->ouwiki_internal_print_heading_bit(1, $pageversion->title, $subwiki,
                    $cm, null, $annotations, $pageversion->locked, $files,
                    $pageversion->versionid);
        } else {
            $output .= html_writer::end_tag('div');
        }

        // List of recent changes
        if ($gewgaws && $pageversion->recentversions) {
            $output .= html_writer::start_tag('div', array('class' => 'ouw_recentchanges'));
            $output .= get_string('recentchanges', 'ouwiki').': ';
            $output .= html_writer::start_tag('span', array('class' => 'ouw_recentchanges_list'));

            $first = true;
            foreach ($pageversion->recentversions as $recentversion) {
                if ($first) {
                    $first = false;
                } else {
                    $output .= '; ';
                }

                $output .= ouwiki_recent_span($recentversion->timecreated);
                $output .= ouwiki_nice_date($recentversion->timecreated);
                $output .= html_writer::end_tag('span');
                $output .= ' (';
                $recentversion->id = $recentversion->userid; // so it looks like a user object
                $output .= ouwiki_display_user($recentversion, $cm->course, false);
                $output .= ')';
            }

            if (class_exists('ouflags') && ou_get_is_mobile()) {
                $output .= '; ';
                $output .= html_writer::end_tag('span');
                $output .= html_writer::end_tag('div');
            } else {
                $output .= '; ';
                $pagestr = '';
                if (strtolower(trim($title)) !== strtolower(get_string('startpage', 'ouwiki'))) {
                    $pagestr = '&page='.$title;
                }
                $output .= html_writer::tag('a', get_string('seedetails', 'ouwiki'),
                        array('href' => $CFG->wwwroot.'/mod/ouwiki/history.php?id='.
                        $cm->id . $pagestr));
                $output .= html_writer::end_tag('span');
                $output .= html_writer::end_tag('div');

            }
        }

        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'ouw_belowmainhead'));

        // spacer
        $output .= html_writer::start_tag('div', array('class' => 'ouw_topspacer'));
        $output .= html_writer::end_tag('div');

        // Content of page
        $output .= ouwiki_convert_content($pageversion->xhtml, $subwiki, $cm, null,
                $pageversion->xhtmlformat);

        if ($gewgaws) {
            // Add in links/etc. around headings
            $ouwikiinternalre->pagename = $pageversion->title;
            $ouwikiinternalre->subwiki = $subwiki;
            $ouwikiinternalre->cm = $cm;
            $ouwikiinternalre->annotations = $annotations;
            $ouwikiinternalre->locked = $pageversion->locked;
            $ouwikiinternalre->pageversion = $pageversion;
            $output = preg_replace_callback(
                    '|<h([1-9]) id="ouw_s([0-9]+_[0-9]+)">(.*?)(<br\s*/>)?</h[1-9]>|s',
                    'ouwiki_internal_re_heading', $output);
        }
        $output .= html_writer::start_tag('div', array('class'=>'clearer'));
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // attached files
        if ($files) {
            $output .= html_writer::start_tag('div', array('class' => 'ouwiki-post-attachments'));
            $output .= html_writer::tag('h3', get_string('attachments', 'ouwiki'),
                    array('class' => 'ouw_topheading'));
            $output .= html_writer::start_tag('ul');
            foreach ($files as $file) {
                $output .= html_writer::start_tag('li');
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $iconimage = html_writer::empty_tag('img',
                        array('src' => $this->output->pix_url(file_mimetype_icon($mimetype)),
                        'alt' => $mimetype, 'class' => 'icon'));
                $path = file_encode_url($CFG->wwwroot . '/pluginfile.php', '/' . $modcontext->id .
                        '/mod_ouwiki/attachment/' . $pageversion->versionid . '/' . $filename);
                $output .= html_writer::tag('a', $iconimage, array('href' => $path));
                $output .= html_writer::tag('a', s($filename), array('href' => $path));
                $output .= html_writer::end_tag('li');
            }
            $output .= html_writer::end_tag('ul');
            $output .= html_writer::end_tag('div');
        }

        // pages that link to this page
        if ($gewgaws) {
            $links = ouwiki_get_links_to($pageversion->pageid);
            if (count($links) > 0) {
                $output .= html_writer::start_tag('div', array('class'=>'ouw_linkedfrom'));
                $output .= html_writer::tag('h3', get_string(
                        count($links) == 1 ? 'linkedfromsingle' : 'linkedfrom', 'ouwiki'),
                        array('class'=>'ouw_topheading'));
                $output .= html_writer::start_tag('ul');
                $first = true;
                foreach ($links as $link) {
                    $output .= html_writer::start_tag('li');
                    if ($first) {
                        $first = false;
                    } else {
                        $output .= '&#8226; ';
                    }
                    $linktitle = ($link->title) ? htmlspecialchars($link->title) :
                            get_string('startpage', 'ouwiki');
                    $output .= html_writer::tag('a', $linktitle,
                            array('href'=>$CFG->wwwroot.'/mod/ouwiki/view.php?id=' . $cm->id .
                            '&page=' . $link->title));
                    $output .= html_writer::end_tag('li');
                }
                $output .= html_writer::end_tag('ul');
                $output .= html_writer::end_tag('div');
            }
        }

        // disply the orphaned annotations
        if ($subwiki->annotation && $annotations && $page != 'history') {
            $orphaned = '';
            foreach ($annotations as $annotation) {
                if ($annotation->orphaned) {

                    $orphaned .= $this->ouwiki_print_hidden_annotation($annotation);
                }
            }
            if ($orphaned !== '') {
                $output .= html_writer::tag('h3', get_string('orphanedannotations', 'ouwiki'));
                $output .= $orphaned;
            } else {
                $output = $output;
            }
        }

        return array($output, $annotations);
    }

    public function ouwiki_internal_print_heading_bit($headingnumber, $pagename, $subwiki, $cm,
            $xhtmlid, $annotations, $locked, $files, $versionid) {
        global $CFG;

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'ouw_byheading'));

        if ($subwiki->canedit && !$locked) {
            $output .= html_writer::tag('a', get_string(
                    $xhtmlid ? 'editsection' : 'editpage', 'ouwiki'),
                    array('href' => $CFG->wwwroot.'/mod/ouwiki/edit.php?id='.$cm->id.'&page='.
                    $pagename.($xhtmlid ? '&section='.$xhtmlid : '')));
        }

        // output the annotate link if using annotation system
        if ($subwiki->annotation) {
            if ($subwiki->canannotate) {
                $output .= ' ' .html_writer::tag('a', get_string('annotate', 'ouwiki'),
                        array('href' => $CFG->wwwroot.'/mod/ouwiki/annotate.php?id='.$cm->id.
                        '&page='.$pagename, 'class' => 'ouw_annotate'));
            }

            if ($annotations != false) {
                $orphancount = 0;
                foreach ($annotations as $annotation) {
                    if ($annotation->orphaned == 1) {
                        $orphancount++;
                    }
                }
                if (count($annotations) > $orphancount) {
                    $output .= html_writer::start_tag('span', array('id' => 'showhideannotations'));
                    $output .= ' '.html_writer::tag('a', 'Show all annotations',
                        array(
                            'href' => 'javascript:ouwikiShowAllAnnotations("block")',
                            'id' => 'showallannotations'
                        ));
                    $output .= html_writer::tag('a', 'Hide all annotations',
                        array(
                            'href' => 'javascript:ouwikiShowAllAnnotations("none")',
                            'id' => 'hideallannotations'
                        ));
                    $output .= html_writer::end_tag('span');
                }
            }
        }

        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new portfolio_add_button();
        $button->set_callback_options('ouwiki_portfolio_caller',
                array('versionid' => $versionid), '/mod/ouwiki/locallib.php');
        if (empty($files)) {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        }
        $output .= ' ' . $button->to_html(PORTFOLIO_ADD_TEXT_LINK).' ';

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function ouwiki_internal_re_heading_bits($matches) {
        global $ouwikiinternalre;

        $tag = "h$matches[1]";
        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'ouw_heading ouw_heading'.
                $matches[1]));
        $output .= html_writer::tag($tag, $matches[3], array('id' => 'ouw_s'.$matches[2]));

        $output .= $this->ouwiki_internal_display_heading_bit($matches[1],
                $ouwikiinternalre->pagename, $ouwikiinternalre->subwiki,
                $ouwikiinternalre->cm, $matches[2], $ouwikiinternalre->annotations,
                $ouwikiinternalre->locked, $ouwikiinternalre->pageversion);

        return $output;
    }

    public function ouwiki_internal_display_heading_bit($headingnumber, $pagename, $subwiki, $cm,
            $xhtmlid, $annotations, $locked, $pageversion) {
        global $CFG;

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'ouw_byheading'));

        if ($subwiki->canedit && !$locked) {
            $output .= html_writer::tag('a',
                    get_string($xhtmlid ? 'editsection' : 'editpage', 'ouwiki'),
                    array('href' => $CFG->wwwroot.'/mod/ouwiki/edit.php?id='.$cm->id.'&page='.
                    $pagename.($xhtmlid ? '&section='.$xhtmlid : ''),
                    'class' => 'ouw_editsection'));
        }

        // output the annotate link if using annotation system
        if ($subwiki->annotation) {
            if ($subwiki->canannotate) {
                $output .= html_writer::tag('a', get_string('annotate', 'ouwiki'),
                        array('href' => $CFG->wwwroot.'/mod/ouwiki/annotate.php?id='.$cm->id.
                        '&page='.$pagename, 'class' => 'ouw_annotate'));
            }
        }

        if (!$xhtmlid) {
            require_once($CFG->libdir . '/portfoliolib.php');
            $button = new portfolio_add_button();
            $button->set_callback_options('ouwiki_portfolio_caller',
                    array('versionid' => $pageversion->versionid), '/mod/ouwiki/locallib.php');
            if (empty($files)) {
                $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
            } else {
                $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
            }
            $output .= ' ' . $button->to_html(PORTFOLIO_ADD_TEXT_LINK).' ';
        }

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function ouwiki_print_preview($content, $page, $subwiki, $cm, $contentformat) {
        $output = html_writer::tag('p', get_string('previewwarning', 'ouwiki'),
                array('class' => 'ouw_warning'));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_preview'));
        $output .= $this->output->box_start("generalbox boxaligncenter");

        // Title & content of page
        $title = $page !== null && $page !== '' ? htmlspecialchars($page) :
                get_string('startpage', 'ouwiki');
        $output .= html_writer::tag('h1', $title);
        $output .= ouwiki_convert_content($content, $subwiki, $cm, null, $contentformat);

        $output .= html_writer::end_tag('div');
        $output .= $this->output->box_end();

        return $output;
    }

    /**
     * Format the diff content for rendering
     *
     * @param v1 object version one of the page
     * @param v2 object version two of the page
     * @return output
     */
    public function ouwiki_print_diff($v1, $v2) {

        $output = '';

        // left: v1
        $output .= html_writer::start_tag('div', array('class' => 'ouw_left'));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_versionbox'));
        $output .= html_writer::tag('h1', $v1->version, array('class' => 'accesshide'));
        $output .= html_writer::tag('div', $v1->date, array('class' => 'ouw_date'));
        $output .= html_writer::tag('div', $v1->savedby, array('class' => 'ouw_person'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'ouw_diff ouwiki_content'));
        $output .= $v1->content;
        $output .= html_writer::tag('h3', get_string('attachments', 'ouwiki'), array());
        $output .= html_writer::tag('div', $v1->attachments,
                array('class' => 'ouwiki_attachments'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // right: v2
        $output .= html_writer::start_tag('div', array('class' => 'ouw_right'));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_versionbox'));
        $output .= html_writer::tag('h1', $v2->version, array('class' => 'accesshide'));
        $output .= html_writer::tag('div', $v2->date, array('class' => 'ouw_date'));
        $output .= html_writer::tag('div', $v2->savedby, array('class' => 'ouw_person'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'ouw_diff ouwiki_content'));
        $output .= $v2->content;
        $output .= html_writer::tag('h3', get_string('attachments', 'ouwiki'), array());
        $output .= html_writer::tag('div', $v2->attachments,
                array('class' => 'ouwiki_attachments'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        // clearer
        $output .= html_writer::tag('div', '&nbsp;', array('class' => 'clearer'));

        return $output;
    }

    /**
     * Format the compared file for rendering as part of the diff
     *
     * @param file object
     * @param action string
     * @return output
     */
    public function ouwiki_print_attachment_diff($file, $action = 'none') {
        global $OUTPUT, $CFG;

        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();
        $iconimage = html_writer::empty_tag('img',
                array('src' => $OUTPUT->pix_url(file_mimetype_icon($mimetype)),
                'alt' => $mimetype, 'class' => 'icon'));

        if ($action === 'add') {
            $addedstart = html_writer::empty_tag('img', array(
                'src' => $OUTPUT->pix_url('diff_added_begins', 'ouwiki'),
                'alt' => get_string('addedbegins', 'ouwiki'),
                'class' => 'icon')
            );
            $addedend = html_writer::empty_tag('img', array(
                'src' => $OUTPUT->pix_url('diff_added_ends', 'ouwiki'),
                'alt' => get_string('addedends', 'ouwiki'),
                'class' => 'icon')
            );

            $output = html_writer::start_tag('li');
            $output .= $addedstart;
            $output .= html_writer::tag('span', " $iconimage $filename ",
                    array('class' => 'ouw_added'));
            $output .= $addedend;
            $output .= html_writer::end_tag('li');

        } else if ($action === 'delete') {
            $deletedstart = html_writer::empty_tag('img' , array(
                'src' => $OUTPUT->pix_url('diff_deleted_begins', 'ouwiki'),
                'alt' => get_string('deletedbegins', 'ouwiki'),
                'class' => 'icon')
            );
            $deletedend = html_writer::empty_tag('img', array(
                'src' => $OUTPUT->pix_url('diff_deleted_ends', 'ouwiki'),
                'alt' => get_string('deletedends', 'ouwiki'),
                'class' => 'icon')
            );

            $output = html_writer::start_tag('li');
            $output .= $deletedstart;
            $output .= html_writer::tag('span', " $iconimage $filename ",
                    array('class' => 'ouw_deleted'));
            $output .= $deletedend;
            $output .= html_writer::end_tag('li');
        } else {
            // default case; no change in file
            $output = html_writer::tag('li', "$iconimage $filename");
        }

        return $output;
    }

    /**
     * Format the hidden annotations for rendering
     *
     * @param annotation object
     * @return output
     */
    public function ouwiki_print_hidden_annotation($annotation) {
        global $DB, $COURSE, $OUTPUT;

        $author = $DB->get_record('user', array('id' => $annotation->userid), '*', MUST_EXIST);
        $picture = null;
        $size = 0;
        $return = true;
        $classname = ($annotation->orphaned) ? 'ouwiki-orphaned-annotation' : 'ouwiki-annotation';
        $output = html_writer::start_tag('span',
                array('class' => $classname, 'id' => 'annotationbox'.$annotation->id));
        $output .= $OUTPUT->user_picture($author, array('courseid' => $COURSE->id));
        $output .= html_writer::start_tag('span', array('class' => 'ouwiki-annotation-content'));
        $output .= html_writer::tag('span', fullname($author),
                array('class' => 'ouwiki-annotation-content-title'));
        $output .= $annotation->content;
        $output .= html_writer::end_tag('span');
        $output .= html_writer::end_tag('span');

        return $output;
    }

    /**
     * Format the annotations for portfolio export
     *
     * @param annotation object
     * @return output
     */
    public function ouwiki_print_portfolio_annotation($annotation) {
        global $DB, $COURSE, $OUTPUT;

        $author = $DB->get_record('user', array('id' => $annotation->userid), '*', MUST_EXIST);

        $output = '[';
        $output .= html_writer::start_tag('i');
        $output .= html_writer::tag('span', $annotation->content, array('style' => 'colour: red'));
        $output .= ' - '. fullname($author) . ', ' . userdate($annotation->timemodified);
        $output .= html_writer::end_tag('i');
        $output .= '] ';

        return $output;
    }

    /**
     * Prints the header and (if applicable) group selector.
     *
     * @param object $ouwiki Wiki object
     * @param object $cm Course-modules object
     * @param object $course
     * @param object $subwiki Subwiki objecty
     * @param string $pagename Name of page
     * @param object $context Context object
     * @param string $afterpage If included, extra content for navigation string after page link
     * @param bool $hideindex If true, doesn't show the index/recent pages links
     * @param bool $notabs If true, prints the after-tabs div here
     * @param string $head Things to include inside html head
     * @param string $title
     */
    public function ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context,
            $afterpage = null, $hideindex = null, $notabs = null, $head = '', $title='') {
        $output = '';

        ouwiki_print_header($ouwiki, $cm, $subwiki, $pagename, $afterpage, $head, $title);

        // Print group selector
        $selector = ouwiki_display_subwiki_selector($subwiki, $ouwiki, $cm, $context, $course);
        $output .= $selector;

        // Print index link
        if (!$hideindex) {
            $output .= html_writer::start_tag('div', array('id' => 'ouwiki_indexlinks'));
            $output .= html_writer::start_tag('ul');

            $isindex = basename($_SERVER['PHP_SELF']) == 'wikiindex.php';
            if ($isindex) {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_index'));
                $output .= html_writer::start_tag('span');
                $output .= get_string('index', 'ouwiki');
                $output .= html_writer::end_tag('span');
                $output .= html_writer::end_tag('li');
            } else {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_index'));
                $output .= html_writer::tag('a', get_string('index', 'ouwiki'),
                        array('href' => 'wikiindex.php?'.
                        ouwiki_display_wiki_parameters(null, $subwiki, $cm)));
                $output .= html_writer::end_tag('li');
            }
            $ishistory = basename($_SERVER['PHP_SELF']) == 'wikihistory.php';
            if ($ishistory) {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_history'));
                $output .= html_writer::start_tag('span');
                $output .= get_string('wikirecentchanges', 'ouwiki');
                $output .= html_writer::end_tag('span');
                $output .= html_writer::end_tag('li');
            } else {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_history'));
                $output .= html_writer::tag('a', get_string('wikirecentchanges', 'ouwiki'),
                        array('href' => 'wikihistory.php?'.
                        ouwiki_display_wiki_parameters(null, $subwiki, $cm)));
                $output .= html_writer::end_tag('li');
            }

            $output .= html_writer::end_tag('ul');

            $output .= html_writer::end_tag('div');
        } else {
            $output .= html_writer::start_tag('div', array('id' => 'ouwiki_noindexlink'));
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::start_tag('div', array('class' => 'clearer'));
        $output .= html_writer::end_tag('div');
        if ($notabs) {
            $extraclass = $selector ? ' ouwiki_gotselector' : '';
            $output .= html_writer::start_tag('div',
                    array('id' => 'ouwiki_belowtabs', 'class' => 'ouwiki_notabs'.$extraclass));
            $output .= html_writer::end_tag('div');
        }

        return $output;
    }
}
