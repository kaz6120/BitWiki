<?php
/* 
 * $Id: renderer.inc.php,v 1.3 2005/07/13 09:48:51 youka Exp $
 */



/**
 * ページ表示用クラス。シングルトン。
 */
class Renderer
{
	/**
	 * @var MySmarty
	 */
	protected $smarty;

	/**
	 * 使用するテーマ
	 */
	protected $theme = THEME;

	/**
	 * @var array(string)
	 */
	protected $option = array();

	/**
	 * @var array(string)
	 */
	protected $headeroption = array();

	
	/**
	 * 使用するテーマを取得する。
	 */
	function gettheme()
	{
		return $this->theme;
	}
	
	
	/**
	 * 使用するテーマを設定する。
	 */
	function settheme($theme)
	{
		$this->theme = $theme;
	}
	
	
	/**
	 * オプション出力を設定する。
	 */
	function setoption($name, $html)
	{
		$this->option[$name] = $html;
	}
	
	
	/**
	 * ヘッダーのオプション出力を設定する。
	 */
	function setheaderoption($name, $html)
	{
		$this->headeroption[$name] = $html;
	}
	
	
	/**
	 * インスタンスを取得する。
	 */
	static function getinstance()
	{
		static $ins;
		
		if(empty($ins)){
			$ins = new self;
		}
		return $ins;
	}
	
	
	protected function __construct()
	{
		$this->smarty = new MySmarty(SKIN_DIR);
	}
	
	
	/**
	 * ページを表示する。
	 *
	 * @param	array(string => string)	$value	スキンに渡す値。bodyとtitleは必須。
	 */
	function render($value)
	{
		$command = array();
		foreach(Command::getCommands() as $c){
			$html = $c->getbody();
			if($html != ''){
				$command[substr(get_class($c), 8)] = $html;
			}
		}
		
		$plugin = array();
		foreach(Plugin::getPlugins() as $c){
			$html = $c->getbody();
			if($html != ''){
				$plugin[substr(get_class($c), 7)] = $html;
			}
		}
		
		$this->smarty->assign('command', $command);
		$this->smarty->assign('plugin', $plugin);
		$this->smarty->assign('option', $this->option);
		$this->smarty->assign('headeroption', $this->headeroption);
		$this->smarty->assign('theme', $this->theme);
		$this->smarty->assign($value);
		header('Content-Type: text/html; charset=UTF-8');
		$this->smarty->assign('runningtime', sprintf('%.3f', mtime() - STARTTIME));
		$this->smarty->display(SKINFILE);
	}
}

