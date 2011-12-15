<?php

function xmldb_ouwiki_upgrade($oldversion=0) {

    global $CFG, $DB;

    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes

    if ($oldversion < 2008100600) {

        /// Define field deletedat to be added to ouwiki_versions
        $table = new xmldb_table('ouwiki_versions');
        $field = new xmldb_field('deletedat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'changeprevsize');
        /// Launch add field deletedat (provided field does not already exist)
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2008100600, 'ouwiki');
    }

    if ($oldversion < 2009120801) {
        /// Launch create table for ouwiki_annotations - if it does not already exist (extra precaution)
        $table = new xmldb_table('ouwiki_annotations');
        if(!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file($CFG->dirroot.'/mod/ouwiki/db/install.xml', 'ouwiki_annotations');
        }

        /// Define field locked to be added to ouwiki_pages
        $table = new xmldb_table('ouwiki_pages');
        $field = new xmldb_field('locked');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'currentversionid');
        /// Launch add field locked - if it does not already exist (extra precaution)
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2009120801, 'ouwiki');
    }

    if ($oldversion < 2010022300) {

        /// Define field locked to be added to ouwiki_pages
        $table = new xmldb_table('ouwiki');
        $field = new xmldb_field('commenting');
        $field->set_attributes(XMLDB_TYPE_CHAR, '20', null, null, null, 'default', 'completionedits');
        /// Launch add field locked - if it does not already exist (extra precaution)
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2010022300, 'ouwiki');
    }

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

    if ($oldversion < 2011060100) {

        // Define field enablewordcount to be added to ouwiki
        $table = new xmldb_table('ouwiki');
        $field = new xmldb_field('enablewordcount', XMLDB_TYPE_INTEGER, '1',
            XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field wordcount to be added to ouwiki_versions
        $table = new xmldb_table('ouwiki_versions');
        $field = new xmldb_field('wordcount', XMLDB_TYPE_INTEGER, '10',
            XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'xhtmlformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // we need to update all ouwiki_versions wordcounts
        $count = $DB->count_records_sql('SELECT COUNT(*) FROM {ouwiki_versions}');
        $rs = $DB->get_recordset_sql('SELECT id, xhtml FROM {ouwiki_versions} ORDER BY id');
        if ($rs->valid()) {
            $pbar = new progress_bar('countwordsouwikiversionsxhtml', 500, true);

            $i = 0;
            foreach ($rs as $entry) {
                $i++;
                upgrade_set_timeout(60); // set up timeout, may also abort execution
                $pbar->update($i, $count, "Counting words of ouwiki version entries - $i/$count.");

                // retrieve wordcount
                require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');
                $wordcount = ouwiki_count_words($entry->xhtml);
                $entry->wordcount = $wordcount;
                $DB->update_record('ouwiki_versions', $entry);
            }
        }
        $rs->close();

        upgrade_mod_savepoint(true, 2011060100, 'ouwiki');
    }

    if ($oldversion < 2011071300) {

        // Define field grade to be added to ouwiki
        $table = new xmldb_table('ouwiki');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10',
            XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'enablewordcount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // we need to update all ouwiki_versions scales
        $rs = $DB->get_recordset_sql('SELECT id FROM {ouwiki} ORDER BY id');
        if ($rs->valid()) {
            foreach ($rs as $entry) {
                $entry->grade = 0;
                $DB->update_record('ouwiki', $entry);
            }
        }
        $rs->close();

        upgrade_mod_savepoint(true, 2011071300, 'ouwiki');
    }

    if ($oldversion < 2011072000) {

        // Define field firstversionid to be added to ouwiki_pages
        $table = new xmldb_table('ouwiki_pages');
        $field = new xmldb_field('firstversionid', XMLDB_TYPE_INTEGER, '10',
            XMLDB_UNSIGNED, null, null, '0', 'locked');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field previousversionid to be added to ouwiki_versions
        $table = new xmldb_table('ouwiki_versions');
        $field = new xmldb_field('previousversionid', XMLDB_TYPE_INTEGER, '10',
            XMLDB_UNSIGNED, null, null, '0', 'wordcount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // update all ouwiki_versions previousversionids and ouwiki_pages firstversionids
        // firstversionid
        $sql = 'SELECT v.pageid,
                    (SELECT MIN(id)
                        FROM {ouwiki_versions} v3
                        WHERE v3.pageid = p.id AND v3.deletedat IS NULL)
                    AS firstversionid
                        FROM {ouwiki_pages} p
                        JOIN {ouwiki_versions} v ON v.pageid = p.id
                    GROUP BY v.pageid, p.id ORDER BY v.pageid';
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            foreach ($rs as $entry) {
                if (isset($entry->firstversionid)) {
                    $DB->set_field('ouwiki_pages', 'firstversionid', $entry->firstversionid,
                        array('id' => $entry->pageid));
                }
            }
        }
        $rs->close();

        // previousversionid
        $count = $DB->count_records_sql('SELECT COUNT(*) FROM {ouwiki_versions}');
        $sql = 'SELECT v.id AS versionid,
                    (SELECT MAX(v2.id)
                        FROM {ouwiki_versions} v2
                        WHERE v2.pageid = p.id AND v2.id < v.id)
                    AS previousversionid
                        FROM {ouwiki_pages} p
                        JOIN {ouwiki_versions} v ON v.pageid = p.id';
        $rs = $DB->get_recordset_sql($sql);
        if ($rs->valid()) {
            $pbar = new progress_bar('ouwikifirstandpreviousversions', 500, true);

            $i = 0;
            foreach ($rs as $entry) {
                $i++;
                upgrade_set_timeout(60); // set up timeout, may also abort execution
                $pbar->update($i, $count, "Updating wiki metadata - $i/$count.");

                if (isset($entry->previousversionid)) {
                    $DB->set_field('ouwiki_versions', 'previousversionid',
                        $entry->previousversionid, array('id' => $entry->versionid));
                }
            }
        }
        $rs->close();

        upgrade_mod_savepoint(true, 2011072000, 'ouwiki');
    }

    if ($oldversion < 2011102802) {

        // Delete any duplicate null values (these aren't caught by index)
        // Done in two stages in case mysql is a piece of ****.
        $rs = $DB->get_recordset_sql("
SELECT
    p.id
FROM
   {ouwiki_pages} p
   LEFT JOIN {ouwiki_pages} p2 ON p2.subwikiid = p.subwikiid AND p2.title IS NULL
       AND p.title IS NULL AND p2.id < p.id
WHERE
   p2.id IS NOT NULL");
        $ids = array();
        foreach ($rs as $rec) {
            $ids[] = $rec->id;
        }
        $rs->close();
        if ($ids) {
            list($sql, $params) = $DB->get_in_or_equal($ids);
            $DB->execute("DELETE FROM {ouwiki_pages} WHERE id $sql", $params);
        }

        // Set all the null values to blank
        $DB->execute("UPDATE {ouwiki_pages} SET title='' WHERE title IS NULL");

        // Also in ousearch table if installed
        $table = new xmldb_table('local_ousearch_documents');
        if ($dbman->table_exists($table)) {
            $DB->execute("UPDATE {local_ousearch_documents} SET stringref='' WHERE stringref IS NULL AND plugin='mod_ouwiki'");
        }

        // Changing nullability of field title on table ouwiki_pages to not null
        $table = new xmldb_table('ouwiki_pages');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'subwikiid');

        // Launch change of nullability for field title
        $dbman->change_field_notnull($table, $field);

        // Define index subwikiid-title (unique) to be added to ouwiki_pages
        $index = new xmldb_index('subwikiid-title', XMLDB_INDEX_UNIQUE, array('subwikiid', 'title'));

        // Conditionally launch add index subwikiid-title
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // ouwiki savepoint reached
        upgrade_mod_savepoint(true, 2011102802, 'ouwiki');
    }

    // Must always return true from these functions
    return true;
}
