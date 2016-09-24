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

        if(($ACT != 'show' && $ACT != '') || $REV) {
            return;
        }

        $page = p_get_metadata($ID, 'relation isreplacedby');

        // return if no redirection data
        if(!$page) {
            return;
        }

        if(isset($_GET['redirect'])) {
            // return if redirection is temporarily disabled,
            // or we have been redirected 5 times in a row
            if($_GET['redirect'] == 'no' || $_GET['redirect'] > 4) {
                return;
            } elseif($_GET['redirect'] > 0) {
                $redirect = $_GET['redirect'] + 1;
            } else {
                $redirect = 1;
            }
        } else {
            $redirect = 1;
        }

        // verify metadata currency
        if(@filemtime(metaFN($ID, '.meta')) < @filemtime(wikiFN($ID))) {
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
        $is_external = preg_match('#^https?://#i', $page);
        if(!$is_external) {
            $page = wl($page, array('redirect' => $redirect), true, '&');

            if($this->getConf('show_note')) {
                $this->flash_message($ID);
            }
        }

        // add anchor if not external redirect
        if(!$is_external) {
            $page .= $section;
        }

        $this->redirect($page);
    }

    public function handle_tpl_act_render(&$event, $param) {
        global $ID, $ACT;

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
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : null;

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

        $url  = wl(':' . $page, array('redirect' => 'no'), true, '&');
        $link = '<a href="' . $url . '" class="wikilink1" title="' . $page . '">' . $title . '</a>';
        echo '<div class="noteredirect">' . sprintf($this->getLang('redirected_from'), $link) . '</div><br/>';
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
