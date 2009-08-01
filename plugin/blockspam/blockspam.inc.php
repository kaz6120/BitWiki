<?php
/**
 * Block Spam plugin for KinoWiki-Rev
 * 
 * @copyright Loggix Project
 * @license   New BSD Lisence
 * @since     9.8.1
 * @version   9.8.1
 */



class Plugin_blockspam extends Plugin
{
	function doing()
	{
        $keywords = 'penis|buy|vimax|sonia|online|cheap|lady|a href|'
                  . 'sex|viagra';

        if (preg_match('/.*('. $keywords . ')/i', trim(Vars::$post['text']))) {
            redirect(Page::getinstance(Vars::$post['pagename']));
            //$this->getSmarty()->display('spam.tpl.html');
            exit;

        }

	}
	
	
	function do_url()
	{

    }
}
