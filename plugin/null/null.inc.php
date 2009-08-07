<?php
/* 
 * $Id: null.inc.php,v 1.1 2005/06/14 09:28:09 youka Exp $
 */

class Plugin_null extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		return '';
	}
	
	
	function do_inline($page, $param1, $param2)
	{
		return '';
	}
}

?>