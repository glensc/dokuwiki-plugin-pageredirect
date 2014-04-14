<?php
/**
 * DokuWiki Plugin pageredirect (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Elan Ruusamäe <glen@delfi.ee>
 * @author  David Lorentsen <zyberdog@quakenet.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_pageredirect extends DokuWiki_Action_Plugin {
	/**
	 * Registers a callback function for a given event
	 *
	 * @param Doku_Event_Handler $controller DokuWiki's event controller object
	 * @return void
	 */
	public function register(Doku_Event_Handler $controller) {
		/* @see action_plugin_pageredirect::handle_dokuwiki_started() */
		$controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_dokuwiki_started');
		/* @see action_plugin_pageredirect::handle_parser_metadata_render() */
		$controller->register_hook('PARSER_METADATA_RENDER','BEFORE', $this, 'handle_parser_metadata_render');

		// This plugin goes first
		// After PR#555
		/* @see action_plugin_pageredirect::handle_tpl_act_render() */
		$controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_tpl_act_render', null, -PHP_INT_MAX);

		// Before PR#555, i.e 2013-12-08 release
		if (isset($controller->_hook)) {
			$hooks =& $controller->_hooks[TPL_ACT_RENDER_BEFORE];
			if ($hooks[0][0] != $this) {
				array_unshift($hooks, array_pop($hooks));
			}
		}
	}

	public function handle_dokuwiki_started(&$event, $param) {
		global $ID, $ACT, $REV;

		if (($ACT != 'show' && $ACT != '') || $REV) {
			return;
		}

		$page = p_get_metadata($ID,'relation isreplacedby');

		// return if no redirection data
		if (empty($page)) {
			return;
		}

		if (isset($_GET['redirect'])) {
			// return if redirection is temporarily disabled,
			// or we have been redirected 5 times in a row
			if ($_GET['redirect'] == 'no' || $_GET['redirect'] > 4) {
				return;
			} elseif ($_GET['redirect'] > 0) {
				$redirect = $_GET['redirect'] + 1;
			} else {
				$redirect = 1;
			}
		} else {
			$redirect = 1;
		}

		// verify metadata currency
		if (@filemtime(metaFN($ID,'.meta')) < @filemtime(wikiFN($ID))) {
			return;
		}

		// preserve #section from $page
		list($page, $section) = explode('#', $page, 2);
		if (isset($section)) {
			$section = '#' . $section;
		} else {
			$section = '';
		}

		// prepare link for internal redirects, keep external targets
		if (!preg_match('#^https?://#i', $page)) {
			$page = wl($page, array('redirect' => $redirect), TRUE, '&');

			if (!headers_sent() && $this->getConf('show_note')) {
				// remember to show note about being redirected from another page
				session_start();
				$_SESSION[DOKU_COOKIE]['redirect'] = $ID;
			}
		}

		// redirect
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: ".$page.$section);
		exit();
	}

	public function handle_tpl_act_render(&$event, $param) {
		global $ID, $ACT;

		if ($ACT != 'show' && $ACT != '') {
			return;
		}

		if (!$this->getConf('show_note')) {
			return;
		}

		if (isset($_GET['redirect']) && $_GET['redirect'] > 0 && $_GET['redirect'] < 6) {
			if (isset($_SESSION[DOKU_COOKIE]['redirect']) && $_SESSION[DOKU_COOKIE]['redirect'] != '') {
				// we were redirected from another page, show it!
				$page  = cleanID($_SESSION[DOKU_COOKIE]['redirect']);
				$title = hsc(useHeading('navigation') ? p_get_first_heading($page) : $page);
				echo '<div class="noteredirect">'.sprintf($this->getLang('redirected_from'), '<a href="'.wl(':'.$page, array('redirect' => 'no'), TRUE, '&').'" class="wikilink1" title="'.$page.'">'.$title.'</a>').'</div><br/>';
				unset($_SESSION[DOKU_COOKIE]['redirect']);

				return true;
			}
		}

		return true;
	}

	public function handle_parser_metadata_render(&$event, $param) {
		if (isset($event->data->meta['relation'])) {
			unset($event->data->meta['relation']['isreplacedby']);
		}
	}
}
