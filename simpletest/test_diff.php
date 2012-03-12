<?php
global $CFG;

require_once($CFG->libdir. '/simpletestlib/unit_tester.php');
require_once($CFG->dirroot. '/admin/tool/unittest/simpletestlib.php');

require_once(dirname(__FILE__).'/../difflib.php');

class test_diff extends UnitTestCase {

    var $html1 = '
<p>This is a long paragraph
split over several lines
and including <b>bold</b> and
<i>italic</i> and <span class="frog">span</span> tags.</p>
<p>This is a second paragraph.</p>
<div>This div contain\'s some greengrocer\'s apostrophe\'s.</div>
<ul>
<li>A list</li>
<li>With multiple
items</li>
<li>Some of them have

multiple


line breaks</li>
</ul>', $html2= '
<div><!-- Extra structure, to be ignored -->
<p>This is a long paragraph
split over several lines
and including <b>bold</b> and
<i>italic</i> and <span class="frog">span</span> tags.</p>
</div>
<p>This is a second paragraph which I have added some text to.</p>
<div>This div contain\'s <span class="added html tags">some</span> <b>greengrocer\'s</b> <img src="notthere.jpg" /> apostrophe\'s.</div>
<ul>
<li>A</li><!-- Deleted word -->
<!-- Deleted entire line -->
<li>Some of them have

multiple


line breaks</li>
</ul>';


    function setUp() {
    }

    function tearDown() {
    }

    function test_add_markers() {
        $html='01frog67890zombie789';
        $words=array();
        $words[]=new ouwiki_word('frog',2);
        $words[]=new ouwiki_word('zombie',11);
        $result=ouwiki_diff_add_markers($html,$words,'ouw_marker','!!','??');
        $this->assertEqual($result,
            '01!!<span class="ouw_marker">frog</span>??67890!!<span class="ouw_marker">zombie</span>??789');
    }

    function test_diff_words() {
        $lines1=ouwiki_diff_html_to_lines($this->html1);
        $lines2=ouwiki_diff_html_to_lines($this->html2);
        list($deleted,$added)=ouwiki_diff_words($lines1,$lines2);

        $delarray=array();
        foreach($deleted as $word) {
            $delarray[]=$word->word;
        }
        sort($delarray);
        $addarray=array();
        foreach($added as $word) {
            $addarray[]=$word->word;
        }
        sort($addarray);

        $this->assertEqual($delarray,array('With','items','list','multiple','paragraph.'));
        $this->assertEqual($addarray,array('I','added','have','paragraph','some','text','to.','which'));
/*
        $result1=ouwiki_diff_add_markers($this->html1,$deleted,'ouw_deleted');
        $result2=ouwiki_diff_add_markers($this->html2,$added,'ouw_added');
        print '<div style="float:left;width:48%"><h1>Before</h1>';
//        print_object($deleted);
        print $result1;
        print '</div><div style="float:right;width:48%"><h1>After</h1>';
//        print_object($added);
        print $result2;
        print '</div>';
        exit;*/
    }

    function test_diff_changes() {
        // Initial file for comparison (same for all examples)
        $file1=array(1=>'a','b','c','d','e','f','g');

        // Add text at beginning
        $file2=array(1=>'0','1','a','b','c','d','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual($result->changes,array());
        $this->assertEqual($result->adds,array(1,2));

        // Add text at end
        $file2=array(1=>'a','b','c','d','e','f','g','0','1');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual($result->changes,array());
        $this->assertEqual($result->adds,array(8,9));

        // Add text in middle
        $file2=array(1=>'a','b','c','0','1','d','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual($result->changes,array());
        $this->assertEqual($result->adds,array(4,5));

        // Delete text at beginning
        $file2=array(1=>'c','d','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array(1,2));
        $this->assertEqual($result->changes,array());
        $this->assertEqual($result->adds,array());

        // Delete text at end
        $file2=array(1=>'a','b','c','d','e');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array(6,7));
        $this->assertEqual($result->changes,array());
        $this->assertEqual($result->adds,array());

        // Delete text in middle
        $file2=array(1=>'a','b','c','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array(4,5));
        $this->assertEqual($result->changes,array());
        $this->assertEqual($result->adds,array());

        // Change text in middle (one line)
        $file2=array(1=>'a','b','frog','d','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual(count($result->changes),1);
        $this->assertEqual(array_values((array)$result->changes[0]),array(3,1,3,1));
        $this->assertEqual($result->adds,array());

        // Change text in middle (two lines)
        $file2=array(1=>'a','b','frog','toad','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual(count($result->changes),1);
        $this->assertEqual(array_values((array)$result->changes[0]),array(3,2,3,2));
        $this->assertEqual($result->adds,array());

        // Change text in middle (one line -> two)
        $file2=array(1=>'a','b','frog','toad','d','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual(count($result->changes),1);
        $this->assertEqual(array_values((array)$result->changes[0]),array(3,1,3,2));
        $this->assertEqual($result->adds,array());

        // Change text in middle (two lines -> one)
        $file2=array(1=>'a','b','frog','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual(count($result->changes),1);
        $this->assertEqual(array_values((array)$result->changes[0]),array(3,2,3,1));
        $this->assertEqual($result->adds,array());

        // Two changes
        $file2=array(1=>'a','frog','toad','c','d','zombie','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual(count($result->changes),2);
        $this->assertEqual(array_values((array)$result->changes[0]),array(2,1,2,2));
        $this->assertEqual(array_values((array)$result->changes[1]),array(5,2,6,1));
        $this->assertEqual($result->adds,array());

        // Changes at ends
        $file2=array(1=>'ant','frog','toad','c','d','zombie');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array());
        $this->assertEqual(count($result->changes),2);
        $this->assertEqual(array_values((array)$result->changes[0]),array(1,2,1,3));
        $this->assertEqual(array_values((array)$result->changes[1]),array(5,3,6,1));
        $this->assertEqual($result->adds,array());

        // A change, a delete, an add
        $file2=array(1=>'ant','b','d','zombie','e','f','g');
        $result=ouwiki_diff($file1,$file2);
        $this->assertEqual($result->deletes,array(3));
        $this->assertEqual(count($result->changes),1);
        $this->assertEqual(array_values((array)$result->changes[0]),array(1,1,1,1));
        $this->assertEqual($result->adds,array(4));
    }

    function test_splitter() {
        $lines=ouwiki_diff_html_to_lines($this->html1);
        $this->assertEqual(ouwiki_line::get_as_strings($lines),array(
            1=>"This is a long paragraph split over several lines and including bold and italic and span tags.",
            2=>"This is a second paragraph.",
            3=>"This div contain's some greengrocer's apostrophe's.",
            4=>"A list",
            5=>"With multiple items",
            6=>"Some of them have multiple line breaks"
        ));
        $lines=ouwiki_diff_html_to_lines($this->html2);
        $this->assertEqual(ouwiki_line::get_as_strings($lines),array(
            1=>"This is a long paragraph split over several lines and including bold and italic and span tags.",
            2=>"This is a second paragraph which I have added some text to.",
            3=>"This div contain's some greengrocer's apostrophe's.",
            4=>"A",
            5=>"Some of them have multiple line breaks"
        ));
    }

    function test_basic_diff() {
        // Example from paper
        $file1=array(1=>'a','b','c','d','e','f','g');
        $file2=array(1=>'w','a','b','x','y','z','e');
        $this->assertEqual(ouwiki_diff_internal($file1,$file2),array(1=>2,2=>3,3=>0,4=>0,5=>7,6=>0,7=>0));
        $this->assertEqual(ouwiki_diff_internal($file2,$file1),array(1=>0,2=>1,3=>2,4=>0,5=>0,6=>0,7=>5));

        // Add text at beginning
        $file2=array(1=>'0','1','a','b','c','d','e','f','g');
        $this->assertEqual(ouwiki_diff_internal($file1,$file2),array(1=>3,2=>4,3=>5,4=>6,5=>7,6=>8,7=>9));

        // Add text at end
        $file2=array(1=>'a','b','c','d','e','f','g','0','1');
        $this->assertEqual(ouwiki_diff_internal($file1,$file2),array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7));

        // Add text in middle
        $file2=array(1=>'a','b','c','0','1','d','e','f','g');
        $this->assertEqual(ouwiki_diff_internal($file1,$file2),array(1=>1,2=>2,3=>3,4=>6,5=>7,6=>8,7=>9));

        // Delete text at beginning
        $file2=array(1=>'c','d','e','f','g');
        $this->assertEqual(ouwiki_diff_internal($file1,$file2),array(1=>0,2=>0,3=>1,4=>2,5=>3,6=>4,7=>5));

        // Delete text at end
        $file2=array(1=>'a','b','c','d','e');
        $this->assertEqual(ouwiki_diff_internal($file1,$file2),array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>0,7=>0));

        // Delete text in middle
        $file2=array(1=>'a','b','c','f','g');
        $this->assertEqual(ouwiki_diff_internal($file1,$file2),array(1=>1,2=>2,3=>3,4=>0,5=>0,6=>4,7=>5));
    }

}
