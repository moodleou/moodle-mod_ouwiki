<?php

require_once($CFG->dirroot . '/mod/ouwiki/backup/moodle2/backup_ouwiki_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/ouwiki/backup/moodle2/backup_ouwiki_settingslib.php'); // Because it exists (optional)

/**
 * ouwiki backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_ouwiki_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Wiki only has one structure step
        $this->add_step(new backup_ouwiki_activity_structure_step('ouwiki_structure', 'ouwiki.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of wikis
        $search = "/(" . $base . "\/mod\/ouwiki\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OUWIKIINDEX*$2@$', $content);

        // Link to wiki view by moduleid
        $search = "/(" . $base . "\/mod\/ouwiki\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OUWIKIVIEWBYID*$2@$', $content);

        return $content;
    }

}
