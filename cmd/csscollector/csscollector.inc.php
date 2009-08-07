<?php
/* 
 * $Id: csscollector.inc.php,v 1.1.1.1 2005/06/12 15:37:45 youka Exp $
 */



class Command_csscollector extends Command
{
	function do_url()
	{
		$css = array();
		$lastmodtime = 0;
		
		foreach(scandir(PLUGIN_DIR) as $name){
			if($name != '.' && $name != '..' && is_dir(PLUGIN_DIR . $name)){
				$pluginname = mb_strtolower($name);
				$file = PLUGIN_DIR . $pluginname . '/' . $pluginname . '.css';
				if(is_file($file)){
					$css[] = file_get_contents($file);
					$lastmodtime = max($lastmodtime, filemtime($file));
				}
			}
		}
		foreach(scandir(COMMAND_DIR) as $name){
			if($name != '.' && $name != '..' && is_dir(COMMAND_DIR . $name)){
				$cmdname = mb_strtolower($name);
				$file = COMMAND_DIR . $cmdname . '/' . $cmdname . '.css';
				if(is_file($file)){
					$css[] = file_get_contents($file);
					$lastmodtime = max($lastmodtime, filemtime($file));
				}
			}
		}
		
		header('Content-Type: text/css; charset=UTF-8');
		header('Last-Modified: ' . date('r', $lastmodtime));
		echo join("\n\n", $css);
		exit();
	}
}

?>