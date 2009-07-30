<?php
/*
 * $Id: autolink.inc.php,v 1.2 2005/06/27 18:08:07 youka Exp $
 */



/**
 * オートリンクのための正規表現を管理するクラス。シングルトン。
 */
class AutoLink
{
	/** オートリンクの正規表現 */
	protected $expression = array();
	/** オートリンク対象外のページ */
	protected $ignorelist = null;
	/** オートリンク対象外を列挙するページの名前 */
	const ignorelistpage = ':config/AutoLink/ignore';
	
	
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
	
	
	/**
	 * コンストラクタ。
	 */
	protected function __construct()
	{
		//do nothing
	}
	
	
	/**
	 * 本体実行前にクラスを初期化する
	 */
	static function init()
	{
		$ins = self::getinstance();
		$ins->makeignorelist();
		Page::attach($ins);
	}
	
	
	/**
	 * ignoreリストを構築する。
	 */
	protected function makeignorelist()
	{
		$this->ignorelist = array();
		$page = Page::getinstance(self::ignorelistpage);
		$lines = explode("\n", $page->getsource());
		foreach($lines as $str){
			if(mb_ereg('^-\[\[(.+)\]\]', $str, $m)){
				$this->ignorelist[] = $m[1];
			}
		}
	}
	
	
	/**
	 * オートリンク用正規表現を取得する。
	 * 
	 * @param	string	$dir	起点となるディレクトリ名。
	 * @return	string	正規表現。
	 */
	function getexpression($dir = '')
	{
		if(!isset($this->expression[$dir])){
			$db = DataBase::getinstance();
			
			$_dir = $db->escape($dir);
			$result = $db->query("SELECT exp FROM autolink WHERE dir = '$_dir'");
			$row = $db->fetch($result);
			if($row == false){
				$list = $this->listup($dir);
				$exp = makelinkexp($list);
				$_exp = $db->escape($exp);
				$db->query("INSERT INTO autolink (dir, exp) VALUES('$_dir', '$_exp')");
				$this->expression[$dir] = $exp;
			}
			else{
				$this->expression[$dir] = $row['exp'];
			}
		}
		return $this->expression[$dir];
	}
	
	
	/**
	 * オートリンクの対象となるページを列挙する。
	 * 
	 * @param	string	$dir	起点となるディレクトリ名。
	 * @return	array(string)	相対パス。
	 */
	protected function listup($dir)
	{
		$db = DataBase::getinstance();
		
		$query = "SELECT pagename FROM page";
		if($dir != ''){
			$query .= " WHERE pagename like '" . $db->escape($dir) . "/%'";
		}
		
		$result = $db->query($query);
		$list = array();
		if($dir == ''){
			while($row = $db->fetch($result)){
				if(!$this->isignored($row['pagename'])){
					$list[] = $row['pagename'];
				}
			}
		}
		else{
			$len = strlen("{$dir}/");
			while($row = $db->fetch($result)){
				if(!$this->isignored($row['pagename'])){
					$list[] = substr($row['pagename'], $len);
				}
			}
		}
		return $list;
	}
	
	
	/**
	 * 無視ページかどうかを確認する。
	 * 
	 * @param	string	$pagename	ページ名。
	 * @return	bool	無視ページの場合true。
	 */
	protected function isignored($pagename)
	{
		return in_array($pagename, $this->ignorelist);
	}
	
	
	/**
	 * ページ更新と同時にオートリンク用正規表現を更新する。
	 */
	function update($page, $arg)
	{
		if($page->getpagename() == self::ignorelistpage){
			$this->makeignorelist();
		}
		$this->refresh();
	}
	
	
	/**
	 * オートリンク用正規表現を作り直す。
	 */
	function refresh()
	{
		$db = DataBase::getinstance();
		$db->query("DELETE FROM autolink");
		$this->expression = array();
	}
}

?>