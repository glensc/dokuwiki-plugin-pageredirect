<?php

require_once __DIR__ . '/test_abstract.php';

/**
 * test that metadata is correctly added to the index to support backlinks
 *
 * @group plugin_pageredirect
 */
class plugin_pageredirect_test_backlinks extends plugin_pageredirect_test_abstract {

    public function test_simple_redirect() {
        $link_from = 'link_from';
        $orig_target = 'link_target';
        $redirect_target = 'redirect_target';

        saveWikiText($orig_target, '#redirect '.$redirect_target, 'created');
        idx_addPage($orig_target);

        saveWikiText($link_from, 'A link to [[:'.$orig_target.']].', 'created');
        idx_addPage($link_from);


        $this->assertEquals(array($link_from), ft_backlinks($orig_target));
        $this->assertEquals(array($link_from), ft_backlinks($redirect_target));
    }
}
