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
     * @param bool $hideannotations If true, adds extra class to hide annotations
     * @return string HTML content for page
     */
    public function ouwiki_print_page($subwiki, $cm, $pageversion, $gewgaws = null,
            $page = 'history', $showwordcount = 0, $hideannotations = false) {
        $output = '';
        $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

        global $CFG, $ouwikiinternalre;

        require_once($CFG->libdir . '/filelib.php');

        // Get annotations - only if using annotation system. prevents unnecessary db access
        if ($subwiki->annotation) {
            $annotations = ouwiki_get_annotations($pageversion);
        } else {
            $annotations = '';
        }

        // Title
        $title = $pageversion->title === '' ? get_string('startpage', 'ouwiki') :
                htmlspecialchars($pageversion->title);

        $pageversion->xhtml = ouwiki_convert_content($pageversion->xhtml, $subwiki, $cm, null,
                $pageversion->xhtmlformat);
        // setup annotations according to the page we are on
        if ($page == 'view') {
            // create the annotations
            if ($subwiki->annotation && count($annotations)) {
                $pageversion->xhtml = ouwiki_highlight_existing_annotations($pageversion->xhtml, $annotations, 'view');
            }
        } else if ($page == 'annotate') {
            // call function for the annotate page
            $pageversion->xhtml = ouwiki_setup_annotation_markers($pageversion->xhtml);
            $pageversion->xhtml = ouwiki_highlight_existing_annotations($pageversion->xhtml, $annotations, 'annotate');
        }

        // Must rewrite plugin urls AFTER doing annotations because they depend on byte position.
        $pageversion->xhtml = file_rewrite_pluginfile_urls($pageversion->xhtml, 'pluginfile.php',
                $modcontext->id, 'mod_ouwiki', 'content', $pageversion->versionid);

        // get files up here so we have them for the portfolio button addition as well
        $fs = get_file_storage();
        $files = $fs->get_area_files($modcontext->id, 'mod_ouwiki', 'attachment',
                $pageversion->versionid, "timemodified", false);

        $output .= html_writer::start_tag('div', array('class' => 'ouwiki-content' .
                ($hideannotations ? ' ouwiki-hide-annotations' : '')));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_topheading'));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_heading'));
        $output .= html_writer::tag('h1', format_string($title),
                array('class' => 'ouw_topheading'));

        if ($gewgaws) {
            $output .= $this->render_heading_bit(1, $pageversion->title, $subwiki,
                    $cm, null, $annotations, $pageversion->locked, $files,
                    $pageversion->pageid);
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

        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class' => 'ouw_belowmainhead'));

        // spacer
        $output .= html_writer::start_tag('div', array('class' => 'ouw_topspacer'));
        $output .= html_writer::end_tag('div');

        // Content of page
        $output .= $pageversion->xhtml;

        if ($gewgaws) {
            // Add in links/etc. around headings.
            $ouwikiinternalre = new stdClass();
            $ouwikiinternalre->pagename = $pageversion->title;
            $ouwikiinternalre->subwiki = $subwiki;
            $ouwikiinternalre->cm = $cm;
            $ouwikiinternalre->annotations = $annotations;
            $ouwikiinternalre->locked = $pageversion->locked;
            $ouwikiinternalre->pageversion = $pageversion;
            $ouwikiinternalre->files = $files;
            $output = preg_replace_callback(
                    '|<h([1-9]) id="ouw_s([0-9]+_[0-9]+)">(.*?)(<br\s*/>)?</h[1-9]>|s',
                    'ouwiki_internal_re_heading', $output);
        }
        $output .= html_writer::start_tag('div', array('class'=>'clearer'));
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');

        // Render wordcount
        if ($showwordcount) {
            $output .= $this->ouwiki_render_wordcount($pageversion->wordcount);
        }

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
                            array('href' => $CFG->wwwroot . '/mod/ouwiki/view.php?' .
                            ouwiki_display_wiki_parameters(
                                $link->title, $subwiki, $cm, OUWIKI_PARAMS_URL)));
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

    public function render_heading_bit($headingnumber, $pagename, $subwiki, $cm,
            $xhtmlid, $annotations, $locked, $files, $pageid) {
        global $CFG;

        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'ouw_byheading'));

        // Add edit link for page or section
        if ($subwiki->canedit && !$locked) {
            $str = $xhtmlid ? 'editsection' : 'editpage';

            $output .= html_writer::tag('a', get_string($str, 'ouwiki'), array(
                    'href' => $CFG->wwwroot . '/mod/ouwiki/edit.php?' .
                        ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL) .
                        ($xhtmlid ? '&section=' . $xhtmlid : ''),
                    'class' => 'ouw_' . $str));
        }

        // output the annotate link if using annotation system, only for page not section
        if (!$xhtmlid && $subwiki->annotation) {
            // Add annotate link
            if ($subwiki->canannotate) {
                $output .= ' ' .html_writer::tag('a', get_string('annotate', 'ouwiki'),
                        array('href' => $CFG->wwwroot.'/mod/ouwiki/annotate.php?' .
                        ouwiki_display_wiki_parameters($pagename, $subwiki, $cm, OUWIKI_PARAMS_URL),
                        'class' => 'ouw_annotate'));
            }

            // 'Expand/collapse all' and 'Show/hide all' annotation controls
            if ($annotations != false) {
                $orphancount = 0;
                foreach ($annotations as $annotation) {
                    if ($annotation->orphaned == 1) {
                        $orphancount++;
                    }
                }
                if (count($annotations) > $orphancount) {
                    // Show and hide annotation icon links. Visibility controlled by CSS.
                    $output .= html_writer::start_tag('span', array('id' => 'showhideannotationicons'));
                    $output .= ' '.html_writer::tag('a', get_string('showannotationicons', 'ouwiki'),
                            array('href' => 'hideannotations.php?hide=0&' . ouwiki_display_wiki_parameters(
                            $pagename, $subwiki, $cm, OUWIKI_PARAMS_URL) . '&sesskey=' . sesskey(),
                            'id' => 'showannotationicons'));
                    $output .= html_writer::tag('a', get_string('hideannotationicons', 'ouwiki'),
                            array('href' => 'hideannotations.php?hide=1&' . ouwiki_display_wiki_parameters(
                            $pagename, $subwiki, $cm, OUWIKI_PARAMS_URL) . '&sesskey=' . sesskey(),
                            'id' => 'hideannotationicons'));
                    $output .= html_writer::end_tag('span');

                    // Expand and collapse annotations links.
                    $output .= html_writer::start_tag('span', array('id' => 'expandcollapseannotations'));
                    $output .= ' '.html_writer::tag('a', get_string('expandallannotations', 'ouwiki'),
                        array(
                            'href' => 'javascript:ouwikiShowAllAnnotations("block")',
                            'id' => 'expandallannotations'
                        ));
                    $output .= html_writer::tag('a', get_string('collapseallannotations', 'ouwiki'),
                        array(
                            'href' => 'javascript:ouwikiShowAllAnnotations("none")',
                            'id' => 'collapseallannotations'
                        ));
                    $output .= html_writer::end_tag('span');
                }
            }
        }

        // On main page, add export button
        if (!$xhtmlid && $CFG->enableportfolios) {
            require_once($CFG->libdir . '/portfoliolib.php');
            $button = new portfolio_add_button();
            $button->set_callback_options('ouwiki_page_portfolio_caller',
                    array('pageid' => $pageid), '/mod/ouwiki/portfoliolib.php');
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

    /**
     * Renders the 'export entire wiki' link.
     * @param object $subwiki Subwiki data object
     * @param bool $anyfiles True if any page of subwiki contains files
     * @return string HTML content of list item with link, or nothing if none
     */
    public function render_export_all_li($subwiki, $anyfiles) {
        global $CFG;

        if (!$CFG->enableportfolios) {
            return '';
        }

        require_once($CFG->libdir . '/portfoliolib.php');
        $button = new portfolio_add_button();
        $button->set_callback_options('ouwiki_all_portfolio_caller',
                array('subwikiid' => $subwiki->id), '/mod/ouwiki/portfoliolib.php');
        if ($anyfiles) {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        }
        return html_writer::tag('li', $button->to_html(PORTFOLIO_ADD_TEXT_LINK));
    }

    public function ouwiki_internal_re_heading_bits($matches) {
        global $ouwikiinternalre;

        $tag = "h$matches[1]";
        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'ouw_heading ouw_heading'.
                $matches[1]));
        $output .= html_writer::tag($tag, $matches[3], array('id' => 'ouw_s'.$matches[2]));

        $output .= $this->render_heading_bit($matches[1],
                $ouwikiinternalre->pagename, $ouwikiinternalre->subwiki,
                $ouwikiinternalre->cm, $matches[2], $ouwikiinternalre->annotations,
                $ouwikiinternalre->locked, $ouwikiinternalre->files,
                $ouwikiinternalre->pageversion->pageid);

        return $output;
    }

    public function ouwiki_print_preview($content, $page, $subwiki, $cm, $contentformat) {
        global $CFG;

        // Convert content.
        $content = ouwiki_convert_content($content, $subwiki, $cm, null, $contentformat);
        // Need to replace brokenfile.php with draftfile.php since switching off filters
        // will switch off all filter.
        $content = str_replace("\"$CFG->httpswwwroot/brokenfile.php#",
                "\"$CFG->httpswwwroot/draftfile.php", $content);
        // Create output to be returned for printing.
        $output = html_writer::tag('p', get_string('previewwarning', 'ouwiki'),
                array('class' => 'ouw_warning'));
        $output .= html_writer::start_tag('div', array('class' => 'ouw_preview'));
        $output .= $this->output->box_start("generalbox boxaligncenter");
        // Title & content of page.
        $title = $page !== null && $page !== '' ? htmlspecialchars($page) :
                get_string('startpage', 'ouwiki');
        $output .= html_writer::tag('h1', $title);
        $output .= $content;
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
        $output .= html_writer::tag('span', get_string('endannotation', 'ouwiki'),
                array('class' => 'accesshide'));
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
     * @param string $querytext for use when changing groups against search criteria
     */
    public function ouwiki_print_start($ouwiki, $cm, $course, $subwiki, $pagename, $context,
            $afterpage = null, $hideindex = null, $notabs = null, $head = '', $title='', $querytext = '') {
            global $USER;
        $output = '';

        ouwiki_print_header($ouwiki, $cm, $subwiki, $pagename, $afterpage, $head, $title);

        $canview = ouwiki_can_view_participation($course, $ouwiki, $subwiki, $cm);
        $page = basename($_SERVER['PHP_SELF']);

        // Print group/user selector
        $showselector = true;
        if (($page == 'userparticipation.php' && $canview != OUWIKI_USER_PARTICIPATION)
            || $page == 'participation.php'
                && (int)$ouwiki->subwikis == OUWIKI_SUBWIKIS_INDIVIDUAL) {
            $showselector = false;
        }
        if ($showselector) {
            $selector = ouwiki_display_subwiki_selector($subwiki, $ouwiki, $cm,
                $context, $course, $page, $querytext);
            $output .= $selector;
        }

        // Print index link
        if (!$hideindex) {
            $output .= html_writer::start_tag('div', array('id' => 'ouwiki_indexlinks'));
            $output .= html_writer::start_tag('ul');

            if ($page == 'wikiindex.php') {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_index'));
                $output .= html_writer::start_tag('span');
                $output .= get_string('index', 'ouwiki');
                $output .= html_writer::end_tag('span');
                $output .= html_writer::end_tag('li');
            } else {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_index'));
                $output .= html_writer::tag('a', get_string('index', 'ouwiki'),
                        array('href' => 'wikiindex.php?'.
                        ouwiki_display_wiki_parameters('', $subwiki, $cm, OUWIKI_PARAMS_URL)));
                $output .= html_writer::end_tag('li');
            }
            if ($page == 'wikihistory.php') {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_history'));
                $output .= html_writer::start_tag('span');
                $output .= get_string('wikirecentchanges', 'ouwiki');
                $output .= html_writer::end_tag('span');
                $output .= html_writer::end_tag('li');
            } else {
                $output .= html_writer::start_tag('li', array('id' => 'ouwiki_nav_history'));
                $output .= html_writer::tag('a', get_string('wikirecentchanges', 'ouwiki'),
                        array('href' => 'wikihistory.php?'.
                        ouwiki_display_wiki_parameters('', $subwiki, $cm, OUWIKI_PARAMS_URL)));
                $output .= html_writer::end_tag('li');
            }
            if ($canview == OUWIKI_USER_PARTICIPATION) {
                $participationstr = get_string('participationbyuser', 'ouwiki');
                $participationpage = 'participation.php?' .
                    ouwiki_display_wiki_parameters('', $subwiki, $cm, OUWIKI_PARAMS_URL);
            } else if ($canview == OUWIKI_MY_PARTICIPATION) {
                $participationstr = get_string('myparticipation', 'ouwiki');
                $participationpage = 'userparticipation.php?' .
                        ouwiki_display_wiki_parameters('', $subwiki, $cm, OUWIKI_PARAMS_URL);
                $participationpage .= '&user='.$USER->id;
            }

            if ($canview > OUWIKI_NO_PARTICIPATION) {
                if (($cm->groupmode != 0) && isset($subwiki->groupid)) {
                    $participationpage .= '&group='.$subwiki->groupid;
                }
                if ($page == 'participation.php' || $page == 'userparticipation.php') {
                    $output .= html_writer::start_tag('li',
                        array('id' => 'ouwiki_nav_participation'));
                    $output .= html_writer::start_tag('span');
                    $output .= $participationstr;
                    $output .= html_writer::end_tag('span');
                    $output .= html_writer::end_tag('li');
                } else {
                    $output .= html_writer::start_tag('li',
                        array('id' => 'ouwiki_nav_participation'));
                    $output .= html_writer::tag('a', $participationstr,
                            array('href' => $participationpage));
                    $output .= html_writer::end_tag('li');
                }
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

    /**
     * Format the wordcount for display
     *
     * @param string $wordcount
     * @return output
     */
    public function ouwiki_render_wordcount($wordcount) {
        $output = html_writer::start_tag('div', array('class' => 'ouw_wordcount'));
        $output .= html_writer::tag('span', get_string('numwords', 'ouwiki', $wordcount));
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Print all user participation records for display
     *
     * @param object $cm
     * @param object $course
     * @param string $pagename
     * @param int $groupid
     * @param object $ouwiki
     * @param object $subwiki
     * @param string $download (csv)
     * @param int $page flexible_table pagination page
     * @param bool $grading_info gradebook grade information
     * @param array $participation mixed array of user participation values
     * @param object $context
     * @param bool $viewfullnames
     * @param string groupname
     */
    public function ouwiki_render_participation_list($cm, $course, $pagename, $groupid, $ouwiki,
        $subwiki, $download, $page, $grading_info, $participation, $context, $viewfullnames,
        $groupname) {
        global $DB, $CFG, $OUTPUT;

        require_once($CFG->dirroot.'/mod/ouwiki/participation_table.php');
        $perpage = OUWIKI_PARTICIPATION_PERPAGE;

        // filename for downloading setup
        $filename = "$course->shortname-".format_string($ouwiki->name, true);
        if (!empty($groupname)) {
            $filename .= '-'.format_string($groupname, true);
        }

        $table = new ouwiki_participation_table($cm, $course, $ouwiki,
            $pagename, $groupid, $groupname, $grading_info);
        $table->setup($download);
        $table->is_downloading($download, $filename, get_string('participation', 'ouwiki'));

        // participation doesn't need standard ouwiki tabs so we need to
        // add this one div in manually
        if (!$table->is_downloading()) {
            echo html_writer::start_tag('div', array('id' => 'ouwiki_belowtabs'));
        }

        if (!empty($participation)) {
            if (!$table->is_downloading()) {
                $table->pagesize($perpage, count($participation));
                $offset = $page * $perpage;
                $endposition = $offset + $perpage;
            } else {
                // always export all users
                $endposition = count($participation);
                $offset = 0;
            }
            $currentposition = 0;
            foreach ($participation as $user) {
                if ($currentposition == $offset && $offset < $endposition) {
                    $fullname = fullname($user, $viewfullnames);

                    // control details link
                    $details = false;

                    // pages
                    $pagecreates = 0;
                    if (isset($user->pagecreates)) {
                        $pagecreates = $user->pagecreates;
                        $details = true;
                    }
                    $pageedits = 0;
                    if (isset($user->pageedits)) {
                        $pageedits = $user->pageedits;
                        $details = true;
                    }

                    // words
                    if ($ouwiki->enablewordcount) {
                        $wordsadded = 0;
                        if (isset($user->wordsadded)) {
                            $wordsadded = $user->wordsadded;
                            $details = true;
                        }
                        $wordsdeleted = 0;
                        if (isset($user->wordsdeleted)) {
                            $wordsdeleted = $user->wordsdeleted;
                            $details = true;
                        }
                    }

                    // grades
                    if ($grading_info) {
                        if (!$table->is_downloading()) {
                            $attributes = array('userid' => $user->id);
                            if (!isset($grading_info->items[0]->grades[$user->id]->grade)) {
                                $user->grade = -1;
                            } else {
                                $user->grade = $grading_info->items[0]->grades[$user->id]->grade;
                                $user->grade = abs($user->grade);
                            }
                            $menu = html_writer::select(make_grades_menu($ouwiki->grade),
                                'menu['.$user->id.']', $user->grade,
                                array(-1 => get_string('nograde')), $attributes);
                            $gradeitem = '<div id="gradeuser'.$user->id.'">'. $menu .'</div>';
                        } else {
                            if (!isset($grading_info->items[0]->grades[$user->id]->grade)) {
                                $gradeitem = get_string('nograde');
                            } else {
                                $gradeitem = $grading_info->items[0]->grades[$user->id]->grade;
                            }
                        }
                    }

                    // user details
                    if (!$table->is_downloading()) {
                        $picture = $OUTPUT->user_picture($user);
                        $userurl = new moodle_url('/user/view.php?',
                            array('id' => $user->id, 'course' => $course->id));
                        $userdetails = html_writer::link($userurl, $fullname);
                        if ($details) {
                            $detailparams = array('id' => $cm->id, 'pagename' => $pagename,
                                'user' => $user->id, 'group' => $groupid);
                            $detailurl = new moodle_url('/mod/ouwiki/userparticipation.php',
                                $detailparams);
                            $accesshidetext = get_string('userdetails', 'ouwiki', $fullname);
                            $detaillink = html_writer::start_tag('small');
                            $detaillink .= ' (';
                            $detaillink .= html_writer::tag('span', $accesshidetext,
                                    array('class' => 'accesshide'));
                            $detaillink .= html_writer::link($detailurl,
                                get_string('detail', 'ouwiki'));
                            $detaillink .= ')';
                            $detaillink .= html_writer::end_tag('small');
                            $userdetails .= $detaillink;
                        }
                    }

                    // add row
                    if (!$table->is_downloading()) {
                        if ($ouwiki->enablewordcount) {
                            $row = array($picture, $userdetails, $pagecreates,
                                $pageedits, $wordsadded, $wordsdeleted);
                        } else {
                            $row = array($picture, $userdetails, $pagecreates, $pageedits);
                        }
                    } else {
                        $row = array($fullname, $pagecreates, $pageedits,
                            $wordsadded, $wordsdeleted);
                    }
                    if (isset($gradeitem)) {
                        $row[] = $gradeitem;
                    }
                    $table->add_data($row);
                    $offset++;
                }
                $currentposition++;
            }
        }

        if (!$table->is_downloading()) {
            $table->print_html();  /// Print the whole table

            // print the grade form footer if necessary
            if ($grading_info && !empty($participation)) {
                echo $table->grade_form_footer();
            }
        }
    }

    /**
     * Render single user participation record for display
     *
     * @param object $user
     * @param array $changes user participation
     * @param object $cm
     * @param object $course
     * @param object $ouwiki
     * @param object $subwiki
     * @param string $pagename
     * @param int $groupid
     * @param string $download
     * @param bool $canview level of participation user can view
     * @param object $context
     * @param string $fullname
     * @param bool $cangrade permissions to grade user participation
     * @param string $groupname
     */
    public function ouwiki_render_user_participation($user, $changes, $cm, $course,
        $ouwiki, $subwiki, $pagename, $groupid, $download, $canview, $context, $fullname,
        $cangrade, $groupname) {
        global $DB, $CFG, $OUTPUT;

        require_once($CFG->dirroot.'/mod/ouwiki/participation_table.php');

        $filename = "$course->shortname-".format_string($ouwiki->name, true);
        if (!empty($groupname)) {
            $filename .= '-'.format_string($groupname, true);
        }
        $filename .= '-'.format_string($fullname, true);

        // setup the table
        $table = new ouwiki_user_participation_table($cm, $course, $ouwiki,
            $pagename, $groupname, $user, $fullname);
        $table->setup($download);
        $table->is_downloading($download, $filename, get_string('participation', 'ouwiki'));

        // participation doesn't need standard ouwiki tabs so we need to
        // add this one div in manually
        if (!$table->is_downloading()) {
            echo html_writer::start_tag('div', array('id' => 'ouwiki_belowtabs'));
        }

        $previouswordcount = false;
        $lastdate = null;
        foreach ($changes as $change) {
            $date = userdate($change->timecreated, get_string('strftimedate'));
            $time = userdate($change->timecreated, get_string('strftimetime'));
            if (!$table->is_downloading()) {
                if ($date == $lastdate) {
                    $date = null;
                } else {
                    $lastdate = $date;
                }
                $now = time();
                $edittime = $time;
                if ($now - $edittime < 5*60) {
                    $category = 'ouw_recenter';
                } else if ($now - $edittime < 4*60*60) {
                    $category = 'ouw_recent';
                } else {
                    $category = 'ouw_recentnot';
                }
                $time = html_writer::start_tag('span', array('class' => $category));
                $time .= $edittime;
                $time .= html_writer::end_tag('span');
            }
            $page = $change->title ? htmlspecialchars($change->title) :
                get_string('startpage', 'ouwiki');
            $row = array($date, $time, $page);

            // word counts
            if ($ouwiki->enablewordcount) {
                $previouswordcount = false;
                if ($change->previouswordcount) {
                    $words = ouwiki_wordcount_difference($change->wordcount,
                        $change->previouswordcount, true);
                } else {
                    $words = ouwiki_wordcount_difference($change->wordcount, 0, false);
                }
                if (!$table->is_downloading()) {
                    $row[] = $words;
                } else {
                    if ($words <= 0) {
                        $row[] = 0;
                        $row[] = $words;
                    } else {
                        $row[] = $words;
                        $row[] = 0;
                    }
                }
            }

            if (!$table->is_downloading()) {
                $pageparams = ouwiki_display_wiki_parameters($change->title, $subwiki, $cm);
                $pagestr = $page . ' ' . $lastdate . ' ' . $edittime;
                if ($change->id != $change->firstversionid) {
                    $accesshidetext = get_string('viewwikichanges', 'ouwiki', $pagestr);
                    $changeurl = new moodle_url("/mod/ouwiki/diff.php?$pageparams" .
                        "&v2=$change->id&v1=$change->previousversionid");
                    $changelink = html_writer::start_tag('small');
                    $changelink .= ' (';
                    $changelink .= html_writer::link($changeurl, get_string('changes', 'ouwiki'));
                    $changelink .= ')';
                    $changelink .= html_writer::end_tag('small');
                } else {
                    $accesshidetext = get_string('viewwikistartpage', 'ouwiki', $pagestr);
                    $changelink = html_writer::start_tag('small');
                    $changelink .= ' (' . get_string('newpage', 'ouwiki') . ')';
                    $changelink .= html_writer::end_tag('small');
                }
                $current = '';
                if ($change->id == $change->currentversionid) {
                    $viewurl = new moodle_url("/mod/ouwiki/view.php?$pageparams");
                } else {
                    $viewurl = new moodle_url("/mod/ouwiki/viewold.php?" .
                        "$pageparams&version=$change->id");
                }
                $actions = html_writer::tag('span', $accesshidetext, array('class' => 'accesshide'));
                $actions .= html_writer::link($viewurl, get_string('view'));
                $actions .= $changelink;
                $row[] = $actions;
            }

            // add to the table
            $table->add_data($row);
        }

        if (!$table->is_downloading()) {
            $table->print_html();  /// Print the whole table

            // Grade
            if ($cangrade && $ouwiki->grade != 0) {
                $this->ouwiki_render_user_grade($course, $cm, $ouwiki, $user, $pagename, $groupid);
            }
        }
    }

    /**
     * Render single users grading form
     *
     * @param object $course
     * @param object $cm
     * @param object $ouwiki
     * @param object $user
     */
    public function ouwiki_render_user_grade($course, $cm, $ouwiki, $user, $pagename, $groupid) {
        global $CFG;

        require_once($CFG->libdir.'/gradelib.php');
        $grading_info = grade_get_grades($course->id, 'mod', 'ouwiki', $ouwiki->id, $user->id);

        if ($grading_info) {
            if (!isset($grading_info->items[0]->grades[$user->id]->grade)) {
                $user->grade = -1;
            } else {
                $user->grade = abs($grading_info->items[0]->grades[$user->id]->grade);
            }
            $grademenu = make_grades_menu($ouwiki->grade);
            $grademenu[-1] = get_string('nograde');

            $formparams = array();
            $formparams['id'] = $cm->id;
            $formparams['user'] = $user->id;
            $formparams['page'] = $pagename;
            $formparams['group'] = $groupid;
            $formaction = new moodle_url('/mod/ouwiki/savegrades.php', $formparams);
            $mform = new MoodleQuickForm('savegrade', 'post', $formaction,
                '', array('class' => 'savegrade'));

            $mform->addElement('header', 'usergrade', get_string('usergrade', 'ouwiki'));

            $mform->addElement('select', 'grade', get_string('grade'),  $grademenu);
            $mform->setDefault('grade', $user->grade);

            $mform->addElement('submit', 'savechanges', get_string('savechanges'));

            $mform->display();
        }
    }
}
