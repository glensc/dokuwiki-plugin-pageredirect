<?php 
/** 
 * Syntax Plugin:   Redirects page requests based on content 
 *  
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) 
 * @author     David Lorentsen <zyberdog@quakenet.org>   
 */ 

if(!defined('DOKU_INC')) die(); 

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/'); 
require_once(DOKU_PLUGIN.'syntax.php'); 

/** 
 * All DokuWiki plugins to extend the parser/rendering mechanism 
 * need to inherit from this class 
 */ 
class syntax_plugin_pageredirect extends DokuWiki_Syntax_Plugin { 
	
	/** 
	 * return some info 
	 */ 
	function getInfo(){ 
		return array( 
			'author' => 'David Lorentsen', 
			'email'  => 'zyberdog@quakenet.org', 
			'date'   => '2007-01-24', 
			'name'   => 'Page Redirect', 
			'desc'   => 'Redirects page requests based on content', 
			'url'    => 'http://wiki.splitbrain.org/plugin:page_redirector', 
		); 
	} 
	
	function getType() { return 'substition'; } 
	function getPType(){ return 'block'; }
	function getSort() { return 1; }
	
	function connectTo($mode) { 
		$this->Lexer->addSpecialPattern('~~REDIRECT>[a-zA-Z0-9_\-:]+~~', $mode, 'plugin_pageredirect');
	}
	
	/** 
	 * Handle the match 
	 */ 
	function handle($match, $state, $pos, &$handler) {
		// extract target page from match pattern
		$page = substr($match,11,-2);
		
		// prepare message here instead of in render
		$message = '<div class="noteredirect">'.sprintf($this->getLang('redirect_to'), html_wikilink($page)).'</div>';
		
		return Array($page, $message);
	}
	
	/**
	 * Create output and metadata entry
	 */
	function render($mode, &$renderer, $data) {
		if ($mode == 'xhtml') {
			// add prepared note about redirection
			$renderer->doc .= $data[1];
			
			// hook into the post render event to allow the page to be redirected
			global $EVENT_HANDLER; 
			$action =& plugin_load('action','pageredirect'); 
			$EVENT_HANDLER->register_hook('TPL_ACT_RENDER','AFTER', $action, 'handle_pageredirect_redirect'); 
			
			return true;
		} elseif ($mode == 'metadata') {
			// add redirection to metadata
			$renderer->meta['relation']['isreplacedby'] = $data[0];
			
			return true;
		}
		
		return false;
	}

}

