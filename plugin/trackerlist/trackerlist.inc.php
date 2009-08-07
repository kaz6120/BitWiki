<?php
/* 
 * $Id: trackerlist.inc.php,v 1.1 2005/09/26 08:30:34 youka Exp $
 */


class Plugin_trackerlist extends Plugin 
{
	protected $sortkey;
	protected $selectorder;
	protected $type2name;	//項目名->見出し
	
	function do_block($page, $param1, $param2)
	{
		$arg = array_map('trim', explode(',', $param1));
		$base = isset($arg[0]) && $arg[0] != '' ? $arg[0] : $page->getpagename();
		$config = isset($arg[1]) && $arg[1] != '' ? $arg[1] : 'default';
		$this->sortkey = isset($arg[2]) ? array_slice($arg, 2) : array();
		
		$configpagename = ':config/plugin/tracker/' . $config;
		$configdata = Plugin_tracker::Page2data(Page::getinstance($configpagename));
		$this->type2name = array();
		foreach($configdata['form'] as $name => $line){
			if(mb_ereg('\[(.+?)\]', $line[0], $m)){
				$this->type2name[$m[1]] = $name;
			}
		}
		$this->selectorder = $this->getselectorderlist($configdata);
		$bgcolorlist = $this->getbgcolorlist($configdata);
		
		$list = array();
		$db = DataBase::getinstance();
		$query  = 'SELECT pagename, source FROM page';
		$query .= ' WHERE pagename like \'' . $db->escape($base) . '%\'';
		$query .= ' ORDER BY timestamp DESC';
		$result = $db->query($query);
		while($row = $db->fetch($result)){
			if(!mb_ereg('^' . mb_ereg_quote($base) . '/(\d+)/(.+)$', $row[0], $m)){
				continue;
			}
			$item = array();
			$item['var']['_page'] = $row[0];
			$item['var']['_num'] = $m[1];
			$item['var']['_title'] = $m[2];
			foreach(explode("\n", $row[1]) as $line){
				if(mb_ereg('^[-ー・](.+?)[\t 　]*[:：][\t 　]*(.+?)[\t 　]*$', $line, $m)){
					if(!isset($item[$m[1]])){
						$item['var'][$m[1]] = $m[2];
						$item['bgcolor'][$m[1]] = isset($bgcolorlist[$m[1]][$m[2]]) ? $bgcolorlist[$m[1]][$m[2]] : null;
					}
				}
			}
			$list[] = $item;
		}
		
		usort($list, array($this, 'cmp'));
		$smarty = $this->getSmarty();
		$smarty->assign('list', $list);
		if(trim($param2) == ''){
			return '<p class="warning">表示項目を指定してください</p>';
		}
		$smarty->assign('varname', array_map('trim', explode(',', $param2)));
		return $smarty->fetch('trackerlist.tpl.htm');
	}
	
	
	function cmp($a, $b)
	{
		foreach($this->sortkey as $key){
			$k = array_map('trim', explode(' ', $key));
			if(!isset($k[0]) || $k[0] == ''){
				continue;
			}
			$item = $k[0];
			$order = isset($k[1]) ? mb_strtolower($k[1]) : 'asc';
			
			if(isset($a['var'][$item]) && isset($b['var'][$item])){
				if(isset($this->selectorder[$item])){
					$_a = isset($this->selectorder[$item][$a['var'][$item]]) ? $this->selectorder[$item][$a['var'][$item]] : count($this->selectorder[$item]);
					$_b = isset($this->selectorder[$item][$b['var'][$item]]) ? $this->selectorder[$item][$b['var'][$item]] : count($this->selectorder[$item]);
					$ret = $_a - $_b;
				}
				else{
					$ret = mb_strnatcasecmp($a['var'][$item], $b['var'][$item]);
				}
			}
			else{
				if(isset($a['var'][$item])){
					$ret = -1;
				}
				else if(isset($b['var'][$item])){
					$ret = 1;
				}
				else{
					$ret = 0;
				}
			}
			
			if($order == 'desc'){
				$ret *= -1;
			}
			
			if($ret != 0){
				return $ret;
			}
		}
		return 0;
	}
	
	
	private function getselectorderlist($data)
	{
		$ret = array();
		foreach($data as $key => $val){
			if(isset($this->type2name[$key])){
				$ret[$this->type2name[$key]] = array_flip(array_keys($val));
			}
		}
		return $ret;
	}
	
	
	private function getbgcolorlist($data)
	{
		$ret = array();
		foreach($data as $type => $typedata){
			foreach($typedata as $item => $var){
				if(mb_eregi('BGCOLOR\((.+)\)', $var[0], $m)){
					$ret[$this->type2name[$type]][$item] = $m[1];
				}
			}
		}
		return $ret;
	}
}


?>