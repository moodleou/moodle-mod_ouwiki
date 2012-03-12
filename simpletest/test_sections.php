<?php
global $CFG;

require_once($CFG->libdir. '/simpletestlib/unit_tester.php');
require_once($CFG->dirroot. '/admin/tool/unittest/simpletestlib.php');

require_once($CFG->dirroot.'/mod/ouwiki/locallib.php');

class test_sections extends UnitTestCase {

    var $sample='
<h1 id="ouw_s0_0">Start</h1>
x
x<h1>No section ID</h1>x
x<h1 id="ouw_sly">Invalid section ID</h1>x
x<h1 id="ouw_s13_x">Invalid section ID</h1>x
x<h1 id="ouw_s13_13">Non-match heading tags</h2>x
x<h1 id="ouw_s1_666">Valid section heading</h1>x
x<h2 id="ouw_s2_666">Test&amp;</h2>x
x<h3 id="ouw_s3_666">Test<span class="frog">whatever</span></h3>x
x<h4 id="ouw_s4_666">
Test

spacing

and     stuff

</h4>x
x<h5 id="ouw_s5_666">V5</h5>x
x<h6 id="ouw_s6_666">V6</h6>x
x
<h1 id="ouw_s0_1">End</h1>';

    function setUp() {
    }

    function tearDown() {
    }

    function test_find_sections() {
        $sections=ouwiki_find_sections($this->sample);
        $this->assertEqual($sections,array(
            '0_0'=>'Start','1_666'=>'Valid section heading',
            '2_666'=>'Test&','3_666'=>'Testwhatever',
            '4_666'=>'Test spacing and stuff',
            '5_666'=>'V5','6_666'=>'V6','0_1'=>'End'
            ));
    }

}
