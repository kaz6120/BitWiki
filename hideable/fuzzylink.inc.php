<?php
/*
 * $Id: fuzzylink.inc.php,v 1.10 2005/12/25 17:54:21 youka Exp $
 */



/**
 * あいまいリンクのための正規表現を管理するクラス。シングルトン。
 */
class FuzzyLink
{
	/** あいまいリンクの正規表現 */
	protected $expression;
	/** あいまいリンク対象外のページ */
	protected $ignorelist;
	/** あいまいリンク対象外のページ名の正規表現 */
	protected $ignoreexp;
	/** あいまいリンク対象外を列挙するページの名前 */
	const ignorelistpage = ':config/FuzzyLink/ignore';
	
	
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
		$source  = Page::getinstance(self::ignorelistpage)->getsource();
		$source .= "\n" . Page::getinstance(AutoLink::ignorelistpage)->getsource();
		$lines = explode("\n", $source);
		$this->ignorelist = array();
		$this->ignoreexp = array();
		foreach($lines as $str){
			if(mb_ereg('^-\[\[(.+)\]\]', $str, $m)){
				$this->ignorelist[] = $m[1];
			}
			else if(mb_ereg('^-\/(.+)\/', $str, $m)){
				$this->ignoreexp[] = $m[1];
			}
		}
		$this->ignorelist = array_unique($this->ignorelist);
		$this->ignoreexp = array_unique($this->ignoreexp);
	}
	
	
	/**
	 * あいまいリンク用正規表現を取得する。
	 * 
	 * @return	string	正規表現。
	 */
	function getexpression()
	{
		if(empty($this->expression)){
			$db = DataBase::getinstance();
			
			$result = $db->query("SELECT data FROM cache WHERE key = 'fuzzylink_exp'");
			$row = $db->fetch($result);
			if($row == false){
				$list = $this->listup();
				$exp = $list == array() ? '' : '(?:' . join('|', $list) . ')';
				$_exp = $db->escape($exp);
				$db->query("INSERT INTO cache VALUES('fuzzylink_exp', '$_exp')");
				$this->expression = $exp;
			}
			else{
				$this->expression = $row['data'];
			}
		}
		return $this->expression;
	}
	
	
	/**
	 * あいまいリンク用正規表現を列挙する。
	 * 
	 * @return	array(string)	相対パス。
	 */
	protected function listup()
	{
		$db = DataBase::getinstance();
		$result = $db->query("SELECT DISTINCT exp FROM fuzzylink_list");
		$list = array();
		while($row = $db->fetch($result)){
			$list[] = $row['exp'];
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
		if(in_array($pagename, $this->ignorelist)){
			return true;
		}
		foreach($this->ignoreexp as $exp){
			if(mb_ereg($exp, $pagename)){
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * キーワードからページを取得する。
	 * 
	 * @param	string	$word	キーワード
	 * @return	array(Page)
	 */
	function getpagelist($word)
	{
		$db = DataBase::getinstance();
		$list = array();
		$_word = $db->escape($word);
        $result = $db->query("SELECT DISTINCT pagename FROM fuzzylink_list WHERE php('mb_ereg', '(?:^|/)' || exp || '$', '$_word') ORDER BY pagename");
        //$result = $db->query("SELECT DISTINCT pagename FROM fuzzylink_list ORDER BY pagename");
		while($row = $db->fetch($result)){
			$list[] = $row['pagename'];
		}
		
		$ret = array();
		foreach(array_unique($list) as $pagename){
			$ret[] = Page::getinstance($pagename);
		}
		return $ret;
	}
	
	
	/**
	 * ページ更新と同時にあいまいリンク用正規表現を更新する。
	 */
	function update($page, $arg)
	{
		if($page->getpagename() == self::ignorelistpage || $page->getpagename() == AutoLink::ignorelistpage){
			$this->makeignorelist();
			$this->restruct();
		}
		else if($page->isexist() && !$page->isexist(1)){
			$this->addpage($page);
		}
		else if(!$page->isexist() && $page->isexist(1)){
			$this->delpage($page);
		}
		
		self::getinstance()->refresh();
	}
	
	
	/**
	 * あいまいリンク用正規表現を作り直す。
	 */
	protected function refresh()
	{
		$db = DataBase::getinstance();
		$db->query("DELETE FROM cache WHERE key = 'fuzzylink_exp'");
		$this->expression = array();
	}
	
	
	/**
	 * あいまいリンク用ページリストを作り直す。
	 */
	function restruct()
	{
		$db = DataBase::getinstance();
		$db->begin();
		
		$db->query('DROP TABLE fuzzylink_list');
		$db->exec(file_get_contents(HIDEABLE_DIR . 'sql/fuzzylink.sql'));
		$result = $db->query('SELECT pagename FROM page');
		while($row = $db->fetch($result)){
			$this->addpage(Page::getinstance($row['pagename']));
		}
		$this->refresh();
		
		$db->commit();
	}
	
	
	/**
	 * あいまいリンク用リストにページを加える。
	 */
	protected function addpage($page)
	{
		if($this->isignored($page->getpagename()) || $page->ishidden()){
			return;
		}
		
		if(mb_ereg('/([^/]+?)$', $page->getpagename(), $m)){
			$name = $m[1];
		}
		else{
			$name = $page->getpagename();
		}
		
		$db = DataBase::getinstance();
		$_pagename = $db->escape($page->getpagename());
		$db->begin();
		foreach(FuzzyFunc::makefuzzyexplist($name) as $exp){
			$_exp = $db->escape($exp);
			$db->query("INSERT INTO fuzzylink_list VALUES('$_exp', '$_pagename')");
		}
		$db->commit();
	}
	
	
	/**
	 * あいまいリンク用リストからページを削除する。
	 */
	protected function delpage($page)
	{
		$db = DataBase::getinstance();
		$_pagename = $db->escape($page->getpagename());
		$db->query("DELETE FROM fuzzylink_list WHERE pagename = '$_pagename'");
	}
}

?>
