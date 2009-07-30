<?php
/* 
 * $Id: noindex.inc.php,v 1.1 2005/07/13 09:49:45 youka Exp $
 */



class Command_noindex extends Command
{
	protected $noindex = true;
	
	
	function init()
	{
		Command::getCommand('show')->attach($this);
	}
	
	
	function update($show, $arg)
	{
		if($arg == 'doing'){
			$this->noindex = false;
		}
	}
	

	function done()
	{
		if($this->noindex){
			Renderer::getinstance()->setheaderoption('command_noindex', '<meta name="robots" content="noindex">');
		}
	}
	
	
	/**
	 * 検索エンジンにページを登録させるかどうかを設定する。
	 *
	 * @param bool	$noindex	trueの時、ページを登録させない。falseのとき、ページを登録させる。
	 * @return bool	設定前の値を返す。
	 */
	function setnoindexflag($noindex)
	{
		$ret = $this->noindex;
		$this->noindex = $noindex;
		return $ret;
	}
}


?>
