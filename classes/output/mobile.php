<?php
namespace mod_ouwiki\output;
 
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/mod/ouwiki/mobilelib.php');

use context_module;
/**
 * The mod_ouwiki mobile app compatibility.
 *
 * @package	mod_ouwiki
 * @copyright  2018 GetSmarter {@link http://www.getsmarter.co.za}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the hsuforum discussion view for a given forum.
     * Note use as much logic and functions from view.php as possible (view.php uses renderer.php and lib.php to build view)
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     */
    public static function all_wikis_view($args) {
        global $OUTPUT, $USER, $DB, $PAGE, $CFG, $ouwiki_nologin;

        $args   = (object) $args;
        $course = $DB->get_record_select('course',
            'id = (SELECT course FROM {course_modules} WHERE id = ?)', array($args->courseid),
            '*', MUST_EXIST);

        $modinfo = get_fast_modinfo($course);
        $cm      = $modinfo->get_cm($args->cmid);
        $context = context_module::instance($cm->id);

    /// Getting ouwiki for the module - logic from basicpage.php
        $ouwiki = false;
        try {
            $ouwiki = $DB->get_record('ouwiki', array('id' => $cm->instance));
            // $ouwiki = $DB->get_record('ouwiki', array('id' => 2));
        } catch (Exception $e) {
            // @TODO See how to redirect or throw friendly errors in app (popups)
            print_r('Handle moodle app errors here');
        }

    /// Basic Validation checks
        /** Checks for valid course module
         * @TODO
         * See how to redirect or throw friendly errors in app (popups) when below fails
         * Check for group id
         */
        $groupid = 0;
        if (empty($ouwiki_nologin)) {
            // Make sure they're logged in and check they have permission to view
            require_course_login($course, true, $cm);
            require_capability('mod/ouwiki:view', $context);
        }

    /// Get subwiki, creating it if necessary
        $subwiki = ouwiki_get_subwiki($course, $ouwiki, $cm, $context, $groupid, $args->userid, true);

    /// Handle annotations
        // Get annotations - only if using annotation system. Prevents unnecessary db access.
        if ($subwiki->annotation) {
            $annotations = ouwiki_get_annotations($pageversion);
        } else {
            $annotations = '';
        }
        // Setup annotations according to the page we are on.
        if ($page == 'view') {
            if ($subwiki->annotation && count($annotations)) {
                $pageversion->xhtml =
                        ouwiki_highlight_existing_annotations($pageversion->xhtml, $annotations, 'view');
            }
        }

    /// Setting up ouwiki variables - own logic composition from locallib.php and mobilelib.php
        $ouwikioutput = $PAGE->get_renderer('mod_ouwiki');
        $pagename == '';
        $pageversion = ouwiki_get_current_page($subwiki, $pagename);
        $locked = ($pageversion) ? $pageversion->locked : false;
        $hideannotations = get_user_preferences(OUWIKI_PREF_HIDEANNOTATIONS, 0);
        $modcontext = context_module::instance($cm->id);
        $pagetitle = $pageversion->title === '' ? get_string('startpage', 'ouwiki') :
                htmlspecialchars($pageversion->title);
        $nowikipage = false;

        // Must rewrite plugin urls AFTER doing annotations because they depend on byte position.
        $pageversion->xhtml = file_rewrite_pluginfile_urls($pageversion->xhtml, 'pluginfile.php',
                $modcontext->id, 'mod_ouwiki', 'content', $pageversion->versionid);
        $pageversion->xhtml = ouwiki_convert_content($pageversion->xhtml, $subwiki, $cm, null,
                $pageversion->xhtmlformat);

    /// Handle file uploads
        // @TODO properly test and speck out requirements for file uploads in mobile context
        require_once($CFG->libdir . '/filelib.php');
        $fs = get_file_storage();
        $files = $fs->get_area_files($modcontext->id, 'mod_ouwiki', 'attachment',
                $pageversion->versionid, "timemodified", false);


    /// Rendering HTML parts to be output on the mobile template
        // Get header html
        $headercontent  = '';
        $headercontent .= get_topheading_section($pagetitle, true, $pageversion, $annotations, $files, $cm, $subwiki);
        // Get recent edits html
        $recentchangescontent  = '';
        $recentchangescontent .= strip_single_tag(get_recentchanges_section($pagetitle, true, $pageversion, $cm), 'a');
        // Get wiki sections html
        $knownsections = false;
        $knownsectionscount = 0;
        $wikisections = [];
        $knownsections = ouwiki_find_sections($pageversion->xhtml);

        if ($knownsections) {
            $knownsectionscount = count($knownsections);
            $wikisections = get_wiki_sections($knownsections, $pageversion->xhtml);
        }


    /// Build data array to output in the template
        $data = array(
            'pagetitle' => $pagetitle,
            'pagelocked' => $locked,
            'knownsectionscount' => $knownsectionscount,
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_ouwiki/mobile_all_wikis_view', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => array(
                'ouwiki' => json_encode($ouwiki),
                'fullpagecontent' => $pageversion->xhtml,
                'headercontent' => $headercontent,
                'recentchangescontent' => $recentchangescontent,
                'wikisections' => json_encode($wikisections),
            ),
            'files' => '',
        );
    }


    /**
     * Returns the edit wikipage view for a given wiki.
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     * @TODO finish below function
     */
    public static function mobile_edit_wikipage($args) {
        global $OUTPUT, $USER, $DB, $PAGE, $CFG;
        /// Build data array to output in the template
        $data = array(
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_ouwiki/mobile_edit_wikipage_view', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => array(
                'fullpagecontent' => $args['fullpagecontent'],
            ),
            'files' => '',
        );
    }


    /**
     * Returns the edit section view for a given wiki.
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     * @TODO finish below function
     */
    public static function mobile_edit_section($args) {
        global $OUTPUT, $USER, $DB, $PAGE, $CFG;

        /// Build data array to output in the template
        $data = array(
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_ouwiki/mobile_edit_section_view', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => array(
                'sectioncontent' => $args['sectioncontent'],
            ),
            'files' => '',
        );
    }
}
