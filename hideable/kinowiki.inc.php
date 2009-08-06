<?php
/* 
 * $Id: kinowiki.inc.php,v 1.6 2005/09/06 01:14:55 youka Exp $
 */

/**
 * ミリ秒単位で現在の時間を得る。
 * 
 * @return	double	現在の時間
 */
function mtime()
{
	$t=gettimeofday();
	return (double)($t['sec'].'.'.sprintf("%06d", $t['usec']));
}

//開始時刻の設定
define('STARTTIME', mtime());



ini_set('include_path', 'library/' . PATH_SEPARATOR . ini_get('include_path'));
ini_set('include_path', 'library/pear/' . PATH_SEPARATOR . ini_get('include_path'));

//require_once('errorhandler.inc.php');
require_once('exception.inc.php');
require_once('func.inc.php');
require_once('database.inc.php');
require_once('notifier.inc.php');
require_once('attach.inc.php');
require_once('autolink.inc.php');
require_once('backlink.inc.php');
require_once('charentityref.inc.php');
require_once('controller.inc.php');
require_once('command.inc.php');
require_once('diff.inc.php');
require_once('fuzzyfunc.inc.php');
require_once('fuzzylink.inc.php');
require_once('htmlconverter.inc.php');
require_once('itaimoji.inc.php');
require_once('mail.inc.php');
require_once('page.inc.php');
require_once('parser.inc.php');
require_once('plugin.inc.php');
require_once('renderer.inc.php');
require_once('search.inc.php');
require_once('smarty.inc.php');
require_once('vars.inc.php');
require_once('version.inc.php');


class KinoWiki
{
	/**
	 * 実行中のPage
	 * @var	Page
	 */
	protected $page;
	
	/**
	 * 実行中のController
	 * @var	Controller
	 */
	protected $controller;
	
	/**
	 * 実行中のPageを取得する。
	 * @return Page
	 */
	function getPage() { return $this->page; }
	
	/**
	 * 実行中のControllerを取得する。
	 * @param Controller
	 */
	function getController() { return $this->controller; }

	
	/**
	 * インスタンスを取得する。
	 * @return KinoWiki
	 */
	static function getinstance()
	{
		static $ins;
		
		if (empty($ins)) {
			$ins = new self();
		}
		return $ins;
	}
	
	
	/**
	 * アプリケーションを開始する。
	 */
	static function main()
	{
		try{
			self::init();
			$ins = self::getinstance();
			$ins->run();
		}
		catch(FatalException $exc) {
			saveexceptiondump($exc);
			echo $exc->getMessage();
		}
		catch(Exception $exc) {
			echo $exc->getMessage();
		}
	}
	
	
	/**
	 * KinoWikiクラスのインスタンスとは関係のないものの初期化。
	 */
	static function init()
	{
		//内部エンコードの設定
		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');
		
		//SCRIPTURLの設定
		if ($_SERVER['SERVER_PORT'] == 443) {
			$protocol = 'https';
			$port = '';
		}
		else{
			$protocol = 'http';
			$port = $_SERVER['SERVER_PORT'] != 80 ? ":{$_SERVER['SERVER_PORT']}" : '';
		}
		define('SCRIPTDIR', $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . mb_substr($_SERVER['SCRIPT_NAME'], 0, mb_strrpos($_SERVER['SCRIPT_NAME'], '/')+1));
		define('SCRIPTURL', $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['SCRIPT_NAME']);
		
		//テーブルが用意されているかチェックし、用意されていなければ用意する。
		$isinstalled = self::installcheck();
		//各クラスの初期化
		Vars::init();
		AutoLink::init();
		BackLink::init();
		FuzzyLink::init();
		Mail::init();
		//テーブルが用意されていなかった場合、初期ページを書き込む
		if (!$isinstalled) {
			self::installpage();
		}
	}	
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		//実行中ページの設定
		if (empty(Vars::$get['plugin']) && (empty(Vars::$get['cmd']) || mb_strtolower(Vars::$get['cmd']) == 'show'))
		{
			if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '') {
				$this->page = Page::getinstance(rawurldecode($_SERVER['PATH_INFO']));
			} else if (isset(Vars::$get['page']) && Vars::$get['page'] != '') {
				$this->page = Page::getinstance(Vars::$get['page']);
			} else if (isset(Vars::$get['n']) && Vars::$get['n'] != '') {
				$this->page = Page::getinstancebynum(Vars::$get['n']);
			} else if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
				$this->page = Page::getinstance(rawurldecode($_SERVER['QUERY_STRING']));
			} else{
				$this->page = Page::getinstance(DEFAULTPAGE);
			}
		}
		else{
			$this->page = Page::getinstance('');
		}

		//実行するControllerの取得
		if (isset(Vars::$get['cmd']) && Vars::$get['cmd'] != '') {
			$this->controller = Command::getCommand(Vars::$get['cmd']);
		}
		else if (isset(Vars::$get['plugin']) && Vars::$get['plugin'] != '') {
			$this->controller = Plugin::getPlugin(Vars::$get['plugin']);
		}
		else{
			$this->controller = Command::getCommand('show');
		}

	}
	
	
	/**
	 * アプリケーションの実行。
	 */
	function run()
	{
		try {
			//コントローラの本体処理実行前動作
			foreach(Command::getCommands() as $cmd) {
				$cmd->doing();
			}
			foreach(Plugin::getPlugins() as $plugin) {
				$plugin->doing();
			}
			
			//本体処理実行
			$ret = $this->controller->run();
			
			//コントローラの本体処理実行後動作
			foreach(Command::getCommands() as $cmd) {
				$cmd->done();
			}
			foreach(Plugin::getPlugins() as $plugin) {
				$plugin->done();
			}
			
			//レンダリング
			Renderer::getinstance()->render($ret);
		} catch(MyException $exc) {
			$text['title'] = 'error';
			$text['body'] = $exc->getMessage();
			Renderer::getinstance()->render($text);
		}
	}
	
	
	/**
	 * データベースがインストールされているかチェックし、インストールを行う。
	 * 
	 * @return	bool	インストールされていればtrue。
	 */
	private static function installcheck()
	{
		$db = DataBase::getinstance();
		if ($db->istable('purepage')) {
			return true;
		} else {
			$db->exec(file_get_contents(HIDEABLE_DIR . 'sql/kinowiki.sql'));
			$dir = opendir(HIDEABLE_DIR . '/sql');
			while(($filename = readdir($dir)) !== false) {
				$path = HIDEABLE_DIR . '/sql/' . $filename;
				if (!is_file($path) || $filename == 'kinowiki.sql') {
					continue;
				}
				$db->exec(file_get_contents($path));
			}
			return false;
		}
	}
	
	
	/**
	 * 初期ページを書き込む。
	 */
	private static function installpage()
	{
		$dir = opendir(HIDEABLE_DIR . '/installpage');
		while(($filename = readdir($dir)) !== false) {
			$path = HIDEABLE_DIR . '/installpage/' . $filename;
			if (!is_file($path) || !preg_match('/^(.+)\.txt$/', $filename, $m)) {
				continue;
			}
			$page = Page::getinstance(rawurldecode($m[1]));
			$page->write(file_get_contents($path));
		}
	}
}

