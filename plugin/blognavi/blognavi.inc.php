<?php
/*
 * $Id: blognavi.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */


class Plugin_blognavi extends Plugin
{
	protected $prevmes = '前へ';
	protected $nextmes = '次へ';
	protected $homemes = '目次へ';
	
	
	function do_block($page, $param1, $param2)
	{
		if(!mb_ereg('^(.+?)/\d{4}-\d{2}-\d{2}', $page->getpagename(), $m)){
			throw new PluginException('このページからは呼び出せません(1)', $this);
		}
		$home = $m[1];
		$datelist = Plugin_blognavi_DateList::getinstance($home);
		$collist = Plugin_blognavi_ColumnList::getinstance($datelist);
		if($datelist->isdatepage($page->getpagename())){
			$prev = $datelist->getprev($page->getpagename());
			$next = $datelist->getnext($page->getpagename());
		}
		else if($collist->iscolumnpage($page->getpagename())){
			$prev = $collist->getprev($page->getpagename());
			$next = $collist->getnext($page->getpagename());
		}
		else{
			throw new PluginException('このページからは呼び出せません(2)', $this);
		}
		
		$str[] = '<div class="plugin_blognavi">';
		if($prev != null){
			$str[] = '[' . makelink($prev, $this->prevmes) . ']';
		}
		else{
			$str[] = '[' . htmlspecialchars($this->prevmes) . ']';
		}
		if($next != null){
			$str[] = '[' . makelink($next, $this->nextmes) . ']';
		}
		else{
			$str[] = '[' . htmlspecialchars($this->nextmes) . ']';
		}
		$str[] = '[' . makelink($home, $this->homemes) . ']';
		$str[] = '</div>';
		return join("\n", $str);
	}
}



class Plugin_blognavi_DateList
{
	protected $datepage = array();
	protected $home;
	
	
	function getinstance($home)
	{
		static $ins = array();
		if(!isset($ins[$home])){
			$ins[$home] = new Plugin_blognavi_DateList($home);
		}
		return $ins[$home];
	}
	
	
	protected function __construct($home)
	{
		$this->home = $home;
		
		$db = DataBase::getinstance();
		$_pattern = $db->escape('^' . mb_ereg_quote($home) . '/\d{4}-\d{2}-\d{2}$');
		$query  = "SELECT pagename FROM page";
		$query .= " WHERE php('mb_ereg', '$_pattern', pagename)";
		$result = $db->query($query);
		$this->datepage = array();
		while($row = $db->fetch($result)){
			$this->datepage[] = $row['pagename'];
		}
		sort($this->datepage);
	}
	
	
	function isdatepage($str)
	{
		return in_array($str, $this->datepage);
	}
	
	
	function getprev($datepage)
	{
		$p = array_search($datepage, $this->datepage);
		if($p === false){
			return false;
		}
		
		return $p != 0 ? $this->datepage[$p-1] : null;
	}
	
	
	function getnext($datepage)
	{
		$p = array_search($datepage, $this->datepage);
		if($p === false){
			return false;
		}
		
		return $p != count($this->datepage)-1 ? $this->datepage[$p+1] : null;
	}
	
	
	function getlast()
	{
		if($this->datepage == array()){
			return null;
		}
		return $this->datepage[count($this->datepage)-1];
	}
	
	
	function gethome()
	{
		return $this->home;
	}
}



class Plugin_blognavi_ColumnList
{
	protected $datepagelist;
	protected $date_col = array();
	protected $col_date = array();
	
	protected $pattern;
	
	
	function getinstance($datepagelist)
	{
		static $ins = array();
		if(!isset($ins[$datepagelist->gethome()])){
			$ins[$datepagelist->gethome()] = new Plugin_blognavi_ColumnList($datepagelist);
		}
		return $ins[$datepagelist->gethome()];
	}
	
	
	protected function __construct($datepagelist)
	{
		$this->datepagelist = $datepagelist;
		$this->pattern = '^(' . mb_ereg_quote($datepagelist->gethome()) . '/\d{4}-\d{2}-\d{2})/.+$';
	}
	
	
	function iscolumnpage($str)
	{
		if(!mb_ereg($this->pattern, $str, $m) || !$this->datepagelist->isdatepage($m[1])){
			return false;
		}
		
		if(!isset($this->date_col[$m[1]])){
			$this->read($m[1]);
		}
		return isset($this->col_date[$str]);
	}
	
	
	function read($datepage)
	{
		$source = Page::getinstance($datepage)->getsource();
		$this->date_col[$datepage] = array();
		if(mb_ereg('<bloginclude>(.*)</bloginclude>', $source, $m)){
			$a = array_map('trim', explode("\n", $m[1]));
			foreach($a as $item){
				if($item != ''){
					$this->date_col[$datepage][] = $item;
					$this->col_date[$item] = $datepage;
				}
			}
		}
		$this->date_col[$datepage] = array_reverse($this->date_col[$datepage]);
	}
	
	
	function getprev($columnpage)
	{
		if(!$this->iscolumnpage($columnpage)){
			return false;
		}
		
		$datepage = $this->col_date[$columnpage];
		$p = array_search($columnpage, $this->date_col[$datepage]);
		if($p != 0){
			return $this->date_col[$datepage][$p-1];
		}
		else{
			$d = $this->datepagelist->getprev($datepage);
			if($d == null){
				return null;
			}
			if(!isset($this->date_col[$d])){
				$this->read($d);
			}
			return $this->date_col[$d][count($this->date_col[$d])-1];
		}
	}
	
	
	function getnext($columnpage)
	{
		if(!$this->iscolumnpage($columnpage)){
			return false;
		}
		
		$datepage = $this->col_date[$columnpage];
		$p = array_search($columnpage, $this->date_col[$datepage]);
		if($p != count($this->date_col[$datepage])-1){
			return $this->date_col[$datepage][$p+1];
		}
		else{
			$d = $this->datepagelist->getnext($datepage);
			if($d == null){
				return null;
			}
			if(!isset($this->date_col[$d])){
				$this->read($d);
			}
			return $this->date_col[$d][0];
		}
	}
	
	
	function getlast()
	{
		$datepage = $this->datepagelist->getlast();
		if($datepage == null){
			return null;
		}
		
		if(!isset($this->date_col[$datepage])){
			$this->read($datepage);
		}
		return $this->date_col[$datepage][count($this->date_col[$datepage])-1];
	}
	
	
	function getlist($datepage)
	{
		if(!$this->datepagelist->isdatepage($datepage)){
			return false;
		}
		
		if(!isset($this->date_col[$datepage])){
			$this->read($datepage);
		}
		return $this->date_col[$datepage];
	}
}
?>