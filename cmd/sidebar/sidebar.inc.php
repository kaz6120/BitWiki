<?php
/* 
 * $Id: sidebar.inc.php,v 1.1.1.1 2005/06/12 15:37:46 youka Exp $
 */



class Command_sidebar extends Command 
{
	function done()
	{
		$this->setbody(convert_Page(Page::getinstance('SideBar')));
	}
}


?>