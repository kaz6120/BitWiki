<?php
/* 
 * $Id: navi.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */


class Plugin_navi extends Plugin
{
	protected $prevmes = '前へ';
	protected $nextmes = '次へ';
	protected $homemes = '目次へ';
	
	
	function do_block($page, $param1, $param2)
	{
		$home = trim($param1) != '' ? Page::getinstance(trim($param1)) : Page::getinstance(dirname($page->getpagename()));
		if($home->isnull()){
			throw new PluginException('パラメータが正しくありません', $this);
		}
		
		$db = DataBase::getinstance();
		$_home = $db->escape($home->getpagename());
		$query  = "SELECT pagename FROM page";
		$query .= " WHERE pagename like '{$_home}/%'";
		$query .= " ORDER BY pagename ASC";
		$result = $db->query($query);
		
		$list = array();
		while($row = $db->fetch($result)){
			$list[] = $row['pagename'];
		}
		if($list == array()){
			return '';
		}
		natsort($list);
		
		$here = array_search($page->getpagename(), $list);
		if($here === false){
			throw new PluginException('現在のページが見つかりません', $this);
		}
		$prev = $here > 0 ? $list[$here-1] : null;
		$next = isset($list[$here+1]) ? $list[$here+1] : null;
		
		$str[] = '<div class="plugin_navi">';
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

?>