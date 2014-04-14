<?php

class plugin_pageredirect_abstract_test extends DokuWikiTest {
	public function setUp() {
		$this->pluginsEnabled[] = 'pageredirect';
		parent::setUp();
	}
}
