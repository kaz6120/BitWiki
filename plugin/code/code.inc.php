<?php
/*
 * $Id: code.inc.php,v 1.1 2005/06/15 12:23:57 youka Exp $
 *
 * このプラグインはmuniさんのcodeプラグインを元に作られています。
 */


class Plugin_code extends Plugin
{
	public function do_block($page, $param1, $codeString)
	{
		require_once('Text/Highlighter.php');
		
		$codeType = trim($param1) == '' ? 'php' : trim($param1);
		
		$textHighlighter =& Text_Highlighter::factory($codeType);
		if (PEAR::isError($textHighlighter)) {
			return '<pre>' . htmlspecialchars($codeString) . '</pre>';
		} else {
			return $textHighlighter->highlight($codeString);
		}
	}
}

?>