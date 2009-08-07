<?php
/* 
 * $Id: block.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */



class Plugin_block extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		return convert_block($param2, $page->getpagename());
	}
}

?>