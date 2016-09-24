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
     */
    public function register(Doku_Event_Handler $controller) {
        /* @see action_plugin_pageredirect::handle_dokuwiki_started() */
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_dokuwiki_started');
        /* @see action_plugin_pageredirect::handle_parser_metadata_render() */
        $controller->register_hook('PARSER_METADATA_RENDER', 'BEFORE', $this, 'handle_parser_metadata_render');

        // This plugin goes first, PR#555, requires dokuwiki 2014-05-05 (Ponder Stibbons)
        /* @see action_plugin_pageredirect::handle_tpl_act_render() */
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_tpl_act_render', null, PHP_INT_MIN);
    }

    public function handle_dokuwiki_started(&$event, $param) {
        global $ID, $ACT, $REV;

        // skip when looking page history or action is not 'show'
        if(($ACT != 'show' && $ACT != '') || $REV) {
            return;
        }

        $metadata = $this->get_metadata($ID);

        // return if no redirection data
        if(!$metadata) {
            return;
        }
        list($page, $is_external) = $metadata;

        global $INPUT;
        $redirect = $INPUT->get->str('redirect', '0');

        // return if redirection is temporarily disabled,
        // or we have been redirected 5 times in a row
        if($redirect == 'no' || $redirect > 4) {
            return;
        }
        $redirect = (int)$redirect+1;

        // verify metadata currency
        // FIXME: why
        if(@filemtime(metaFN($ID, '.meta')) < @filemtime(wikiFN($ID))) {
            throw new Exception('should not get here');
            return;
        }

        // preserve #section from $page
        list($page, $section) = explode('#', $page, 2);
        if(isset($section)) {
            $section = '#' . $section;
        } else {
            $section = '';
        }

        // prepare link for internal redirects, keep external targets
        if(!$is_external) {
            $page = wl($page, array('redirect' => $redirect), true, '&');

            if($this->getConf('show_note')) {
                $this->flash_message($ID);
            }

            // add anchor if not external redirect
            $page .= $section;
        }

        $this->redirect($page);
    }

    public function handle_tpl_act_render(&$event, $param) {
        global $ACT;

        // handle on do=show
        if($ACT != 'show' && $ACT != '') {
            return true;
        }

        if($this->getConf('show_note')) {
            $this->render_flash();
        }

        return true;
    }

    public function handle_parser_metadata_render(&$event, $param) {
        if(isset($event->data->meta['relation'])) {
            // FIXME: why is this needed here?!
            unset($event->data->meta['relation']['isreplacedby']);
        }
    }

    /**
     * remember to show note about being redirected from another page
     * @param string $ID page id from where the redirect originated
     */
    private function flash_message($ID) {
        if(headers_sent()) {
            // too late to do start session
            // and following code requires session
            return;
        }

        session_start();
        $_SESSION[DOKU_COOKIE]['redirect'] = $ID;
    }

    /**
     * show note about being redirected from another page
     */
    private function render_flash() {
        global $INPUT;

        $redirect = $INPUT->get->str('redirect');

        // loop counter
        if($redirect <= 0 || $redirect > 5) {
            return;
        }

        $ID = isset($_SESSION[DOKU_COOKIE]['redirect']) ? $_SESSION[DOKU_COOKIE]['redirect'] : null;
        if(!$ID) {
            return;
        }
        unset($_SESSION[DOKU_COOKIE]['redirect']);

        $page        = cleanID($ID);
        $use_heading = useHeading('navigation') && p_get_first_heading($page);
        $title       = hsc($use_heading ? p_get_first_heading($page) : $page);

        $url  = wl($page, array('redirect' => 'no'), true, '&');
        $link = '<a href="' . $url . '" class="wikilink1" title="' . $page . '">' . $title . '</a>';
        echo '<div class="noteredirect">' . sprintf($this->getLang('redirected_from'), $link) . '</div><br/>';
    }

    private function get_metadata($ID) {
        $metadata = p_get_metadata($ID, 'relation isreplacedby');

        // legacy compat
        if(is_string($metadata)) {
            $metadata = array($metadata);
        }

        return $metadata;
    }

    /**
     * Redirect to url.
     * @param string $url
     */
    private function redirect($url) {
        header("HTTP/1.1 301 Moved Permanently");
        send_redirect($url);
    }
}
