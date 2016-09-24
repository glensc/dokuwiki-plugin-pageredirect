<?php

require_once __DIR__ . '/test_abstract.php';

/**
 * test that page data is parsed into metadata as expected
 *
 * @group plugin_pageredirect
 */
class plugin_pageredirect_test_parser extends plugin_pageredirect_test_abstract {

    /**
     * @dataProvider data
     */
    public function test_parser($id, $text, $page) {
        saveWikiText($id, $text, "edited $id");

        $metadata = p_get_metadata($id);
        $data     = $metadata['relation']['isreplacedby'];
        $this->assertEquals($page, $data[0]);
    }

    public function data() {
        $id = __CLASS__ . __FUNCTION__;
        return array(
            0 => array($id, '~~REDIRECT>http://google.com~~', 'http://google.com'),
            1 => array($id, "\n~~REDIRECT>probability_basic_definitions#bayes_theorem~~", 'probability_basic_definitions#bayes_theorem'),
            2 => array($id, '#redirect start', 'start'),
            3 => array($id, "\n#redirect start\n", 'start'),
            4 => array($id, "#redirect .:vort2d_kalman_filter:start", 'vort2d_kalman_filter:start'),
            5 => array('projects:vort2d_kalman_filter', "#redirect .:vort2d_kalman_filter:start", 'projects:vort2d_kalman_filter:start'),
        );
    }
}
