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
 * Local library file for ouwiki.  These are non-standard functions that are used
 * only by ouwiki.
 *
 * @package    mod
 * @subpackage ouwiki
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

// Make sure this isn't being directly accessed.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ouwiki/locallib.php');
require_once($CFG->libdir . '/portfolio/caller.php');

abstract class ouwiki_portfolio_caller_base extends portfolio_module_caller_base {
    protected $withannotations;

    protected $subwiki, $ouwiki, $modcontext;

    // Attachments: array of arrays of stored_file, keyed on versionid
    protected $attachments = array();

    protected function load_base_data($subwikiid) {
        global $DB, $COURSE;

        // Load base data
        $this->subwiki = $DB->get_record(
                'ouwiki_subwikis', array('id' => $subwikiid), '*', MUST_EXIST);
        $this->ouwiki = $DB->get_record(
                'ouwiki', array('id' => $this->subwiki->wikiid), '*', MUST_EXIST);
        if (!empty($COURSE->id) && $COURSE->id == $this->ouwiki->course) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record(
                    'course', array('id' => $this->ouwiki->course), '*', MUST_EXIST);
        }
        $modinfo = get_fast_modinfo($course);
        $instances = $modinfo->get_instances_of('ouwiki');
        if (!array_key_exists($this->ouwiki->id, $instances)) {
            throw new portfolio_caller_exception('error_export', 'ouwiki');
        }
        $this->cm = $instances[$this->ouwiki->id];
        $this->modcontext = get_context_instance(CONTEXT_MODULE, $this->cm->id);
    }

    /**
     * Adds all the files from the given pageversions.
     * @param array $pageversions
     */
    protected function add_files($pageversions) {
        // This doesn't scale (2 queries/page) but follows the API. We could do
        // it faster but I'm doubtful about bypassing the API in this case.
        $fs = get_file_storage();
        $files = array();
        foreach ($pageversions as $pageversion) {
            $attach = $fs->get_area_files($this->modcontext->id, 'mod_ouwiki', 'attachment',
            $pageversion->versionid, "sortorder, itemid, filepath, filename", false);
            $this->attachments[$pageversion->versionid] = $attach;
            $embed  = $fs->get_area_files($this->modcontext->id, 'mod_ouwiki', 'content',
            $pageversion->versionid, "sortorder, itemid, filepath, filename", false);
            $files = array_merge($files, $attach, $embed);
        }
        $this->set_file_and_format_data($files);

        if (empty($this->multifiles) && !empty($this->singlefile)) {
            $this->multifiles = array($this->singlefile); // copy_files workaround
        }
        // If there are files, change to rich/plain
        if (!empty($this->multifiles)) {
            $this->add_format(PORTFOLIO_FORMAT_RICHHTML);
        } else {
            $this->add_format(PORTFOLIO_FORMAT_PLAINHTML);
        }
    }

    /**
     * @param array $files Array of file items to copy
     * @return void
     */
    protected function copy_files($files) {
        if (empty($files)) {
            return;
        }
        foreach ($files as $f) {
            $this->get('exporter')->copy_existing_file($f);
        }
    }

    /**
     * Obtains page html suitable for use in portfolio export.
     * @param object $pageversion Page and version data
     * @param array $attachments Attachments array indexed by versionid
     * @param object $context Moodle context object
     * @param object $ouwiki OU wiki object
     * @param object $subwiki Subwiki object
     * @param object $course Course object
     * @param bool $withannotations If true, includes annotations
     * @param portfolio_format $portfolioformat Portfolio format
     * @param string $plugin the portfolio plugin being used.
     * @return string HTML code
     */
    static function get_page_html($pageversion, $attachments,
            $context, $ouwiki, $subwiki, $course, $withannotations,
            portfolio_format $portfolioformat, $plugin) {
        global $DB;

        // Format the page body
        $options = portfolio_format_text_options();
        $formattedtext = format_text($pageversion->xhtml, $pageversion->xhtmlformat,
        $options, $course->id);
        $formattedtext = portfolio_rewrite_pluginfile_urls($formattedtext, $context->id,
                'mod_ouwiki', 'content', $pageversion->versionid, $portfolioformat);

        // Get annotations - only if using annotation system. prevents unnecessary db access
        if ($ouwiki->annotation) {
            $annotations = ouwiki_get_annotations($pageversion);
        } else {
            $annotations = array();
        }

        // Convert or remove the annotations
        if ($ouwiki->annotation && count($annotations)) {
            ouwiki_highlight_existing_annotations($formattedtext, $annotations,
                    $withannotations ? 'portfolio' : 'clear');
        }

        // Do overall page, starting with title
        $title = $pageversion->title;
        if ($title === null) {
            $title = get_string('startpage', 'ouwiki');
        }
        $output = html_writer::tag('h2', s($title));

        // Last change info
        $user = (object)array('id' => $pageversion->userid,
                'firstname' => $pageversion->firstname,
                'lastname' => $pageversion->lastname);
        $lastchange = get_string('lastchange', 'ouwiki', (object)array(
                'date' => userdate($pageversion->timecreated),
                'userlink' => ouwiki_display_user($user, $course->id)));
        $output .= html_writer::tag('p', html_writer::tag('small',
                html_writer::tag('i', $lastchange)));

        // Main text
        $output .= html_writer::tag('div', $formattedtext);

        // Word count
        if ($ouwiki->enablewordcount) {
            $wordcount = get_string('numwords', 'ouwiki', $pageversion->wordcount);
            $output .= html_writer::tag('div', html_writer::empty_tag('br'));
            $output .= html_writer::tag('p',
            html_writer::tag('small', $wordcount),
            array('class' => 'ouw_wordcount'));
        }

        // Attachments
        if ($attachments[$pageversion->versionid]) {
            $output .= html_writer::start_tag('div', array('class' => 'attachments'));
            $output .= html_writer::tag('h3', get_string('attachments', 'ouwiki'));
            $output .= html_writer::start_tag('ul');
            foreach ($attachments[$pageversion->versionid] as $file) {
                if ($plugin == 'rtf') {
                    $filename = $file->get_filename();
                    $path = moodle_url::make_pluginfile_url($context->id, 'mod_ouwiki',
                        'attachment', $pageversion->versionid, '/', $filename, true);
                    $atag = html_writer::tag('a', $filename, array('href' => $path));
                } else {
                    $atag = $portfolioformat->file_output($file);
                }
                $output .= html_writer::tag('li', $atag);
            }
            $output .= html_writer::end_tag('ul');
            $output .= html_writer::end_tag('div');
        }

        // Replace all user links with user name so that you can not access user links from within exported document.
        $output = preg_replace('~<a href="[^"]*/user/view.php[^"]*"\s*>(.*?)</a>~', '$1', $output);
        return $output;
    }

    public function get_navigation() {
        global $CFG;

        $navlinks = array();
        $navlinks[] = array(
            'name' => format_string($this->ouwiki->name),
            'link' => $CFG->wwwroot . '/mod/ouwiki/wikiindex.php?id=' . $this->cm->id,
            'type' => 'title'
        );
        return array($navlinks, $this->cm);
    }

    public function expected_time() {
        return $this->expected_time_file();
    }

    public function check_permissions() {
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        return (has_capability('mod/ouwiki:view', $context));
    }

    public static function display_name() {
        return get_string('modulename', 'ouwiki');
    }

    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_RICHHTML, PORTFOLIO_FORMAT_PLAINHTML);
    }

    /**
     * @param string $name Name to be used in filename
     * @return string Safe version of name (replaces unknown characters with _)
     */
    protected function make_filename_safe($name) {
        $result = @preg_replace('~[^A-Za-z0-9 _!,.-]~u', '_', $name);
        // Cope with Unicode support not being available
        if ($result === null) {
            $result = preg_replace('~[^A-Za-z0-9 _!,.-]~', '_', $name);
        }
        return $result;
    }
}

/**
 * Portfolio class for exporting a single page.
 */
class ouwiki_page_portfolio_caller extends ouwiki_portfolio_caller_base {
    protected $pageid;

    // Pageversion: data object with fields from ouwiki_pages and _versions
    private $pageversion;

    public static function expected_callbackargs() {
        return array(
                'pageid' => true,
                'withannotations' => false
        );
    }

    public function load_data() {
        global $DB;

        // Load basic data
        $page = $DB->get_record('ouwiki_pages', array('id' => $this->pageid), '*', MUST_EXIST);
        $this->load_base_data($page->subwikiid);

        // Load page version
        $this->pageversion = ouwiki_get_current_page($this->subwiki, $page->title);

        // Add files from page
        $this->add_files(array($this->pageversion));
    }

    public function get_return_url() {
        $params['id'] = $this->cm->id;
        if (!empty($this->pageversion->title)) {
            $params['page'] = $this->pageversion->title;
        }
        return new moodle_url('/mod/ouwiki/view.php', $params);
    }

    public function get_navigation() {
        global $CFG;

        $title = format_string($this->pageversion->title);
        $name = $title === '' ? get_string('startpage', 'ouwiki') : $title;

        $navlinks[] = array(
            'name' => $name,
            'link' => $CFG->wwwroot . '/mod/ouwiki/view.php?id=' . $this->cm->id . '&page=' .
                $this->pageversion->title,
            'type' => 'title'
        );
        return array($navlinks, $this->cm);
    }

    /**
     * a page with or without attachment
     *
     * @global object
     * @global object
     * @uses PORTFOLIO_FORMAT_RICH
     * @return mixed
     */
    public function prepare_package() {
        global $CFG;

        $pagehtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' .
                html_writer::start_tag('html', array('xmlns' => 'http://www.w3.org/1999/xhtml'));
        $pagehtml .= html_writer::tag('head',
                html_writer::empty_tag('meta',
                    array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8')) .
                html_writer::tag('title', get_string('export', 'forumngfeature_export')));
        $pagehtml .= html_writer::start_tag('body') . "\n";

        $pagehtml .= $this->prepare_page($this->pageversion);

        $pagehtml .= html_writer::end_tag('body') . html_writer::end_tag('html');

        $content = $pagehtml;
        $name = $this->make_filename_safe($this->pageversion->title === '' ?
                get_string('startpage', 'ouwiki') : $this->pageversion->title) . '.html';
        $manifest = ($this->exporter->get('format') instanceof PORTFOLIO_FORMAT_RICH);

        $this->copy_files($this->multifiles);
        $this->get('exporter')->write_new_file($content, $name, $manifest);
    }

    /**
     * @param object $pageversion Pageversion object
     * @return string Page html
     */
    private function prepare_page($pageversion) {
        return ouwiki_portfolio_caller_base::get_page_html($pageversion, $this->attachments,
                $this->modcontext, $this->ouwiki,
                $this->subwiki, $this->get('course'), $this->withannotations,
                $this->get('exporter')->get('format'),
                $this->get('exporter')->get('instance')->get('plugin'));
    }

    public function get_sha1() {
        $filesha = '';
        if (!empty($this->multifiles)) {
            $filesha = $this->get_sha1_file();
        }

        return sha1($filesha . ',' . $this->pageversion->title . ',' . $this->pageversion->xhtml);
    }
}

/**
 * Portfolio class for exporting the entire subwiki contents (all pages).
 */
class ouwiki_all_portfolio_caller extends ouwiki_portfolio_caller_base {
    protected $subwikiid;

    // Pageversions: array of data objects with fields from ouwiki_pages and _versions
    private $pageversions;

    public static function expected_callbackargs() {
        return array(
            'subwikiid' => true,
            'withannotations' => false
        );
    }

    public function load_data() {
        global $DB, $COURSE;

        // Load base data
        $this->load_base_data($this->subwikiid);

        // Load all page-versions
        $this->pageversions = ouwiki_get_subwiki_allpages($this->subwiki);

        // Get all files used in subwiki.
        $this->add_files($this->pageversions);
    }

    public function get_return_url() {
        return new moodle_url('/mod/ouwiki/wikiindex.php', array('id' => $this->cm->id));
    }

    public function prepare_package() {
        global $CFG;

        $pagehtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ' .
                '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' .
                html_writer::start_tag('html', array('xmlns' => 'http://www.w3.org/1999/xhtml'));
        $pagehtml .= html_writer::tag('head',
                html_writer::empty_tag('meta',
                    array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8')) .
                html_writer::tag('title', get_string('export', 'forumngfeature_export')));
        $pagehtml .= html_writer::start_tag('body') . "\n";
        $pagehtml .= html_writer::tag('h1', s($this->ouwiki->name));

        foreach ($this->pageversions as $pageversion) {
            $pagehtml .= $this->prepare_page($pageversion);
        }

        $pagehtml .= html_writer::end_tag('body') . html_writer::end_tag('html');

        $content = $pagehtml;
        $name = $this->make_filename_safe($this->ouwiki->name) . '.html';
        $manifest = ($this->exporter->get('format') instanceof PORTFOLIO_FORMAT_RICH);

        $this->copy_files($this->multifiles);
        $this->get('exporter')->write_new_file($content, $name, $manifest);
    }

    /**
     * @param object $pageversion Pageversion object
     * @return string Page html
     */
    private function prepare_page($pageversion) {
        return ouwiki_portfolio_caller_base::get_page_html($pageversion,
                $this->attachments, $this->modcontext, $this->ouwiki,
                $this->subwiki, $this->get('course'), $this->withannotations,
                $this->get('exporter')->get('format'),
                $this->get('exporter')->get('instance')->get('plugin'));
    }

    public function get_sha1() {
        $filesha = '';
        if (!empty($this->multifiles)) {
            $filesha = $this->get_sha1_file();
        }
        $bigstring = $filesha;
        foreach ($this->pageversions as $pageversion) {
            $bigstring .= ',' . $pageversion->title . ',' . $pageversion->xhtml;
        }
        return sha1($bigstring);
    }
}
