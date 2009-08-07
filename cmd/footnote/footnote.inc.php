<?php
/* 
 * $Id: footnote.inc.php,v 1.1.1.1 2005/06/12 15:37:46 youka Exp $
 */



class Command_footnote extends Command 
{
	function getbody()
	{
		return Footnote::getinstance()->getnote();
	}
}

?>