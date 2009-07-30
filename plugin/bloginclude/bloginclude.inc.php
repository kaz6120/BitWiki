<?php
/*
 * $Id: bloginclude.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_bloginclude extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$a = array_map('trim', explode("\n", $param2));
		foreach($a as $item){
			if($item != ''){
				$ret[] = $this->includepage($item);
			}
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('body', join("\n", $ret));
		return $smarty->fetch('bloginclude.tpl.htm');
	}
	
	
	protected function includepage($pagename)
	{
		$source = mb_ereg_replace('#blognavi', '', Page::getinstance($pagename)->getsource());
		$smarty = $this->getSmarty();
		$smarty->assign('title', $pagename);
		$smarty->assign('body', convert_block($source, $pagename));
		return $smarty->fetch('include.tpl.htm');
	}
}


?>