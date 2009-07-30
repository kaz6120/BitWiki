<?php
/* 
 * $Id: blockquote.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_blockquote extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$param = htmlspecialchars($param1);
		$html = convert_block($param2, $page->getpagename());
		return "<blockquote $param>" . $html . '</blockquote>';
	}
}

?>