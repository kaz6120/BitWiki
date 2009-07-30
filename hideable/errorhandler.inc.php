<?php
/*
 * $id: $
 */


function errorhandler($errno, $errstr, $errfile, $errline, $errcontext)
{
	static $errortype = array(
		1	=>  'Error',
		2	=>  'Warning',
		4	=>  'Parsing Error',
		8	=>  'Notice',
		16  =>  'Core Error',
		32  =>  'Core Warning',
		64  =>  'Compile Error',
		128 =>  'Compile Warning',
		256 =>	'User Error',
		512 =>	'User Warning',
		1024	=>	'User Notice',
		2048	=>	'Strict'
	);

	if($errno == 2048){
		return;
	}
	
	$trace = array();
	$count = 0;
	foreach(debug_backtrace() as $item){
		$trace[] = "#$count:  {$item['function']}() called at [{$item['file']}:{$item['line']}]";
		$count++;
	}
	
	$str[] = date('Y-m-d H:i:s');
	$str[] = $errno . ' ' . isset($errortype[$errno]) ? $errortype[$errno] : 'Unknown Error';
	$str[] = "$errfile($errline)";
	$str[] = $errstr;
	$str[] = join("\n", $trace);
	$str[] = "\n\n";
	
	error_log(join("\n", $str), 3, DATA_DIR . WIKIID . '.error.log');
}

set_error_handler('errorhandler');

?>