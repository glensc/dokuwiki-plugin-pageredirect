<?php
/**
 * DokuWiki Plugin pageredirect (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Elan Ruusamäe <glen@delfi.ee>
 * @author  David Lorentsen <zyberdog@quakenet.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_pageredirect extends DokuWiki_Syntax_Plugin {
    /**
     * @return string Syntax mode type
     */
    public function getType() { return 'substition'; }

    /**
     * @return string Paragraph type
     */
    public function getPType() { return 'block'; }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() { return 1; }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
		$this->Lexer->addSpecialPattern('(?:~~REDIRECT>.+?~~|^#(?i:redirect) [^\r\n]+)', $mode, 'plugin_pageredirect');
	}

	/**
	 * Handler to prepare matched data for the rendering process
	 *
	 * This function can only pass data to render() via its return value - render()
	 * may be not be run during the object's current life.
	 *
	 * Usually you should only need the $match param.
	 *
	 * @param   string       $match   The text matched by the patterns
	 * @param   int          $state   The lexer state for the match
	 * @param   int          $pos     The character position of the matched text
	 * @param   Doku_Handler $handler Reference to the Doku_Handler object
	 * @return  array Return an array with all data you want to use in render
	 */
    public function handle($match, $state, $pos, Doku_Handler $handler){
	    // extract target page from match pattern
		if ($match[0] == '#') {
			# #REDIRECT PAGE
			$page = substr($match, 10);
		} else {
			# ~~REDIRECT>PAGE~~
			$page = substr($match, 11, -2);
		}
		$page=trim($page);

		if (!preg_match('#^(https?)://#i', $page)) {
			$link = html_wikilink($page);
		} else {
			$link = '<a href="'.hsc($page).'" class="urlextern">'.hsc($page).'</a>';
		}

		// prepare message here instead of in render
		$message = '<div class="noteredirect">'.sprintf($this->getLang('redirect_to'), $link).'</div>';

		return array($page, $message);
	}

	/**
	 * Handles the actual output creation.
	 *
	 * The function must not assume any other of the classes methods have been run
	 * during the object's current life. The only reliable data it receives are its
	 * parameters.
	 *
	 * The function should always check for the given output format and return false
	 * when a format isn't supported.
	 *
	 * $renderer contains a reference to the renderer object which is
	 * currently handling the rendering. You need to use it for writing
	 * the output. How this is done depends on the renderer used (specified
	 * by $format
	 *
	 * The contents of the $data array depends on what the handler() function above
	 * created
	 *
	 * @param   $format   string        output format being rendered
	 * @param   $renderer Doku_Renderer reference to the current renderer object
	 * @param   $data     array         data created by handler()
	 * @return  boolean                 rendered correctly?
	 */
	public function render($format, Doku_Renderer $renderer, $data) {
	    if ($format == 'xhtml') {
			// add prepared note about redirection
			$renderer->doc .= $data[1];

			// hook into the post render event to allow the page to be redirected
			global $EVENT_HANDLER;
			$action =& plugin_load('action','pageredirect');
			$EVENT_HANDLER->register_hook('TPL_ACT_RENDER','AFTER', $action, 'handle_pageredirect_redirect');

			return true;
		}
	   
		if ($format == 'metadata') {
			// add redirection to metadata
			$renderer->meta['relation']['isreplacedby'] = $data[0];

			return true;
		}

		return false;
	}
}
