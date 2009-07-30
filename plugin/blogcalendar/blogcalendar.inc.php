<?php
/*
 * $Id: blogcalendar.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_blogcalendar extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$prefix = trim($param1);
		
		$pattern = '^' . mb_ereg_quote($prefix) . '/(\d{1,4})-(\d{2})';
		if(mb_ereg($pattern, $page->getpagename(), $m)){
			$year = $m[1];
			$month = $m[2];
		}
		else if(isset(Vars::$get['year']) && isset(Vars::$get['month'])){
			$year = Vars::$get['year'];
			$month = Vars::$get['month'];
		}
		else{
			$year = date('Y');
			$month = date('n');
		}
		
		$smarty = $this->getSmarty();
		$day = 1;
		$last = date('t', mktime(0, 0, 0, $month, 1, $year));
		while($day <= $last){
			$line = array_fill(0, 7, '&nbsp;');
			$d = date('w', mktime(0, 0, 0, $month, $day, $year));
			for(; $d < 7 && $day <= $last; $d++, $day++){
				$p = Page::getinstance(sprintf('%s/%04d-%02d-%02d', $prefix, $year, $month, $day));
				$line[$d] = $p->isexist() ? makelink($p, $day) : $day;
			}
			$table[] = $line;
		}
		$smarty->assign('table', $table);
		$smarty->assign('year', $year);
		$smarty->assign('month', $month);
		$smarty->assign('prefix', $prefix);
		$smarty->assign('page', $page->getpagename());
		return $smarty->fetch('blogcalendar.tpl.htm');
	}
}

?>