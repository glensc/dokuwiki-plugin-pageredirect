<?php

/**
 * Base class for pageredirect plugin tests
 */
abstract class plugin_pageredirect_test_abstract extends DokuWikiTest {
    public function setUp() {
        $this->pluginsEnabled[] = 'pageredirect';
        parent::setUp();
    }
}
