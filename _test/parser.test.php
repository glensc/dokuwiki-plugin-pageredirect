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
    public function test_parser($text, $page) {
        $id = __CLASS__ . __FUNCTION__;
        saveWikiText($id, $text, "edited $id");

        $metadata = p_get_metadata($id);
        $this->assertEquals($metadata['relation']['isreplacedby'], $page);
    }

    public function data() {
        return array(
            0 => array('~~REDIRECT>http://google.com~~', 'http://google.com'),
        );
    }
}
