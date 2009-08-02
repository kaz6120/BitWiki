<?php
/* 
 * $Id: smarty.inc.php,v 1.6 2005/07/10 12:09:13 youka Exp $
 */
require_once('smarty/Smarty.class.php');


class MySmarty extends Smarty
{
	function __construct($template_dir)
	{
		$this->Smarty();
		
		$this->template_dir = $template_dir;
		$this->compile_dir = COMPILEDTPL_DIR;
		$this->config_dir = './system/smarty/config/';
		$this->cache_dir = './system/smarty/cache/';
		
		$this->assign('script', SCRIPTURL);
		$this->assign('sitename', SITENAME);
		$this->assign('defaultpage', DEFAULTPAGE);
		$this->assign('version', KINOWIKI_VERSION);
		$this->assign('theme_url', dirname(SCRIPTURL) . '/' . THEME_DIR);
		$this->assign('plugin_url', dirname(SCRIPTURL) . '/' . PLUGIN_DIR);
		$this->assign('command_url', dirname(SCRIPTURL) . '/' . COMMAND_DIR);
		$this->register_modifier('makelink', array('MySmarty', 'makelink'));
		$this->register_modifier('decorate_diff', array('MySmarty', 'decorate_diff'));
		$this->register_modifier('time2date', array('MySmarty', 'time2date'));
		$this->register_modifier('old', array('MySmarty', 'old'));
		$this->register_modifier('tinyurl', array('MySmarty', 'tinyurl'));
		$this->register_modifier('topicpath', array('MySmarty', 'topicpath'));
		$this->register_function('includepage', array('MySmarty', 'includepage'));
	}
	
	
	/**
	 * Wikiのページ名を元にリンクを作る。Smartyプラグイン用。
	 */
	function makelink($pagename, $alias = '')
	{
		return makelink(Page::getinstance($pagename), $alias);
	}
	
	
	/**
	 * 差分テキストに色つけタグをつける。Smartyプラグイン用。
	 */
	function decorate_diff($diff)
	{
		$line = explode("\n", $diff);
		for($i = 0; $i < count($line); $i++){
			$line[$i] = mb_ereg_replace('^\+(.+)$', '+<span class="diff_add">\1</span>', $line[$i]);
			$line[$i] = mb_ereg_replace('^-(.+)$', '-<span class="diff_del">\1</span>', $line[$i]);
		}
		return join("\n", $line);
	}


	/**
	 * タイムスタンプを日時に変換する。Smartyプラグイン用。
	 */
	function time2date($timestamp)
	{
		static $daytable = array('日', '月', '火', '水', '木', '金', '土');
		
		$date = date('Y-m-d', $timestamp);
		$day = $daytable[date('w', $timestamp)];
		$time =date('H:i:s', $timestamp);
		return "$date ($day) $time";
	}
	
	
	/**
	 * タイムスタンプを経過時間に返還する。Smartyプラグイン用。
	 */
	function old($timestamp)
	{
		return getold($timestamp);
	}
	
	
	/**
	 * 短いURLを取得する。Smartyプラグイン用。
	 */
	function tinyurl($pagename, $alias = '')
	{
		$url = gettinyURL($pagename);
		if($num === false){
			return '<span class="warning">ページがありません</span>';
		}
		$text = $alias == '' ? $url : $alias;
		return  '<a href="' . $url . '">' . $text . '</a>';
	}

	
	/**
	 * Wikiのページ名を元にパンくずリストを作る。Smartyプラグイン用。
	 */
	function topicpath($pagename)
	{
		$path = explode('/', $pagename);
		$list = array();
		for($i = 0; $i < count($path); $i++){
			$list[] = makelink(join('/', array_slice($path, 0, $i+1)), $path[$i]);
		}
		return join(' &gt; ', $list);
	}
	
	
	/**
	 * 指定したページを挿入する。Smartyプラグイン用。
	 * {includepage page="ページ名"}
	 */
	function includepage($param){
		if(!isset($param['page'])){
			return '';
		}
		return convert_Page(Page::getinstance($param['page']));
	}
}
