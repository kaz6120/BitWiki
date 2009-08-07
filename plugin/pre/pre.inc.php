<?php
/* 
 * $Id: pre.inc.php,v 1.1.1.1 2005/06/12 15:38:47 youka Exp $
 */

class Plugin_pre extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		return '<pre>' . htmlspecialchars($param2) . '</pre>';
	}
	
	
	function do_inline($page, $param1, $param2)
	{
		$str = mb_ereg_replace(' ', '&nbsp;', htmlspecialchars($param2));
		return '<span class="plugin_pre_inline">' . $str . '</span>';
	}
}

?>