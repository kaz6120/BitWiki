<?php
/* 
 * $Id: cat.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_cat extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$text = explode("\n", linetrim($param2));
		$p = explode(',', $param1);
		$num = isset($p[0]) && trim($p[0]) != '' ? trim($p[0]) : strlen(count($text));	//桁数が指定なしの時は自動調整する
		$add = isset($p[2]) ? (int)trim($p[2]) : 1;	//増分
		$start = isset($p[1]) ? (int)trim($p[1]) : $add;	//初期値が指定なしの時は増分と同値
		
		//先頭に'0'がついている場合の書式設定
		$zero = $num{0} == 0 ? '0' : '';
		$_num = (int)$num;
		$format = "%${zero}${_num}d";
		for($i = 0; $i < count($text); $i++){
			$text[$i] = sprintf($format, $start + $add*$i) . ': ' . $text[$i];
		}
		return '<pre>' . htmlspecialchars(join("\n", $text)) . "</pre>\n";
	}
}

?>