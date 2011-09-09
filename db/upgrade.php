<?php

function xmldb_ouwiki_upgrade($oldversion=0) {

    global $CFG, $DB;

    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes

    if ($oldversion < 2010122001) {

        // Drop the old comments table
        $table = new xmldb_table('ouwiki_comments');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Drop the old commenting field in ouwiki
        $table2 = new xmldb_table('ouwiki');
        $field = new xmldb_field('commenting');

        if ($dbman->field_exists($table2, $field)) {
            // We need to know about any wikis which are currently
            // using annotation or both systems before the upgrade
            // we need these before we drop the commenting field
            $rs = $DB->get_records_sql("SELECT DISTINCT w.id ".
                                            "FROM {ouwiki} w ".
                                            "WHERE w.commenting = 'annotations' OR w.commenting = 'both'");

            $dbman->drop_field($table2, $field);
        }

        // Define field annotation to be added to ouwiki
        $field2 = new xmldb_field('annotation');
        $field2->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'completionedits');

        if (!$dbman->field_exists($table2, $field2)) {
            $dbman->add_field($table2, $field2);
        }

        // update the existing wikis to have annotation turned on
        // where they did before or had the BOTH commenting option
        if (!empty($rs)) {
            $ids = array_keys($rs);
            list($usql, $params) = $DB->get_in_or_equal($ids);
            $update_sql = 'UPDATE {ouwiki} SET annotation = 1 WHERE id '.$usql;
            $DB->execute($update_sql, $params);
        }

        // retrieve new summary field
        $field3 = new xmldb_field('summary', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'template');

        // Launch rename field summary
        if ($dbman->field_exists($table2, $field3)) {
            $dbman->rename_field($table2, $field3, 'intro');
        }

        // Define field introformat to be added to ouwiki
        $field4 = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'annotation');

        // Launch add field introformat
        if (!$dbman->field_exists($table2, $field4)) {
            $dbman->add_field($table2, $field4);
        }

        // Define field introformat to be added to ouwiki
        $table3 = new xmldb_table('ouwiki_versions');
        $field5 = new xmldb_field('xhtmlformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'deletedat');

        // Launch add field introformat
        if (!$dbman->field_exists($table3, $field5)) {
            $dbman->add_field($table3, $field5);
        }

        // conditionally migrate to html format in intro
        if ($CFG->texteditors !== 'textarea') {
            // introformat
            $rs = $DB->get_recordset('ouwiki', array('introformat' => FORMAT_MOODLE), '', 'id,intro,introformat');
            foreach ($rs as $r) {
                $r->intro       = text_to_html($r->intro, false, false, true);
                $r->introformat = FORMAT_HTML;
                $DB->update_record('ouwiki', $r);
                upgrade_set_timeout();
            }
            $rs->close();

            // xhtmlformat
            $rs = $DB->get_recordset('ouwiki_versions', array('xhtmlformat' => FORMAT_MOODLE), '', 'id,xhtml,xhtmlformat');
            foreach ($rs as $r) {
                $r->xhtml       = text_to_html($r->xhtml, false, false, true);
                $r->xhtmlformat = FORMAT_MOODLE;
                $DB->update_record('ouwiki_versions', $r);
                upgrade_set_timeout();
            }
            $rs->close();
        }

        upgrade_mod_savepoint(true, 2010122001, 'ouwiki');
    }
 
    if ($oldversion < 2011031800) {
        upgrade_mod_savepoint(true, 2011031800, 'ouwiki');
    }
}
