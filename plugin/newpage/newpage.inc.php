<?php
/* 
 * $Id: newpage.inc.php,v 1.1 2005/06/14 09:40:39 youka Exp $
 */

class Plugin_newpage extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$smarty = $this->getSmarty();
		$smarty->assign('default', trim($param1));
		return $smarty->fetch('newpage.tpl.htm');
	}
}

?>