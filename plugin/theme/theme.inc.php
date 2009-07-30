<?php
/*
 * $Id: theme.inc.php,v 1.1.1.1 2005/06/12 15:38:47 youka Exp $
 */


class Plugin_theme extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$theme = htmlspecialchars(trim($param1));
		Renderer::getinstance()->settheme($theme);
		return '';
	}
}

?>