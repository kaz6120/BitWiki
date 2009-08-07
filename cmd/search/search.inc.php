<?php
/* 
 * $Id: search.inc.php,v 1.1.1.1 2005/06/12 15:37:46 youka Exp $
 */



class Command_search extends Command
{
	function do_url()
	{
		if(isset(Vars::$get['keyword'])){
			$ret = $this->wordsearch();
		}
		else if(isset(Vars::$get['FromDay'])){
			$ret = $this->timesearch();
		}
		else{
			$ret = $this->showform();
		}
		return $ret;
	}
	
	
	protected function showform()
	{
		$ret['title'] = '検索';
		$smarty = $this->getSmarty();
		$ret['body'] = $smarty->fetch('search.tpl.htm');
		return $ret;
	}
	
	
	protected function wordsearch()
	{
		if(!isset(Vars::$get['andor']) || !isset(Vars::$get['type'])){
			throw new CommandException('パラメータが足りません。', $this);
		}
		
		$word = array();
		foreach(mb_split('[\s　]+', Vars::$get['keyword']) as $w){
			if($w != ''){
				$word[] = $w;
			}
		}
		if($word == array()){
			return $this->showform();
		}
		
		$search = Search::getinstance();
		$andsearch = Vars::$get['andor'] == 'and';
		switch(Vars::$get['type']){
			case 'fuzzy':
				$list = $search->fuzzysearch($word, $andsearch);
				break;
			case 'ereg':
				$list = $search->eregsearch($word, $andsearch);
				break;
			default:
				$list = $search->normalsearch($word, $andsearch);
				break;
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('word', join(' ', $word));
		$smarty->assign('type', Vars::$get['type']);
		$smarty->assign('list', $list);
		$ret['title'] = '検索結果';
		$ret['body'] = $smarty->fetch('wordsearchresult.tpl.htm');
		return $ret;
	}
	
	
	protected function timesearch()
	{
		if(!keys_exists(Vars::$get, 'FromYear', 'FromMonth', 'FromDay', 'ToYear', 'ToMonth', 'ToDay')){
			throw new CommandException('パラメータが足りません。', $this);
		}
		
		$from = mktime(0, 0, 0, Vars::$get['FromMonth'], Vars::$get['FromDay'], Vars::$get['FromYear']);
		$to = mktime(23, 59, 59, Vars::$get['ToMonth'], Vars::$get['ToDay'], Vars::$get['ToYear']);
		
		$search = Search::getinstance();
		$smarty = $this->getSmarty();
		$smarty->assign('fromdate', $from);
		$smarty->assign('todate', $to);
		$smarty->assign('list', $search->timesearch($from, $to));
		$ret['title'] = '検索結果';
		$ret['body'] = $smarty->fetch('timesearchresult.tpl.htm');
		return $ret;
	}
}
?>