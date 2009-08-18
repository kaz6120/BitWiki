<?php
/*
 * Mail
 *
 * mail.inc.php,v 1.6 2005/12/01 06:48:14 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.12.1
 * @version 9.8.11
 */


/**
 * ページ更新時にメールを送るクラス。
 * 
 * シングルトン。
 */
class Mail implements MyObserver
{
	protected $sending = MAIL_USE;
	
	
	function getsending(){ return (bool)$this->sending; }
	function setsending($bool){ $old = $this->sending; if(MAIL_USE){ $this->sending = (bool)$bool; } return $old; }
	
	
	static function getinstance()
	{
		static $ins;
		
		if(empty($ins)){
			$ins = new self();
		}
		return $ins;
	}
	
	
	protected function __construct()
	{
		//do nothing
	}
	
	
	/**
	 * 本体実行前にクラスを初期化する
	 */
	static function init()
	{
		$ins = self::getinstance();
		Page::attach($ins);
		Attach::attach($ins);
		AttachedFile::attach($ins);
	}

	
	/**
	 * ページ更新と同時にメールを送る。
	 */
	function update($obj, $arg)
	{
		if(!$this->sending){
			return;
		}
		
		if(is_a($obj, 'Page')){
			$this->change_page($obj);
		}
		else if(is_a($obj, 'Attach')){
			$this->change_attach($obj, $arg);
		}
		else if(is_a($obj, 'AttachedFile')){
			$this->change_attachedfile($obj, $arg);
		}
	}
	
	
	protected function change_page($page)
	{
		$pagename = $page->getpagename();
		if(!$page->isexist()){
			$head = "「{$pagename}」が削除されました。";
		}
		else if(!$page->isexist(1)){
			$head = "「{$pagename}」が作成されました。";
		}
		else{
			$head = "「{$pagename}」が変更されました。";
		}
		
		$subject = '[' . SITENAME . "] $pagename";
		$text[] = $head;
		$text[] = $this->geturl($page);
		$text[] = '----------------------------------------------------------------------';
		$text[] = diff($page->getsource(1), $page->getsource(0), MAIL_DIFF);
		sendmail($subject, join("\n", $text));
	}
	
	
	protected function change_attach($attach, $arg)
	{
		if($arg[0] == 'rename'){
			$head = '添付ファイルの名前が変更されました。';
			$body[] = '旧ファイル名：' . $arg[1];
			$body[] = '新ファイル名：' . $arg[2];
		}
		if($arg[0] == 'move'){
			$from = Attach::getinstance(Page::getinstance($arg[1]))->getlist();
			$to = Attach::getinstance(Page::getinstance($arg[2]))->getlist();
			if($from == array() && $to == array()){	//添付ファイルがない場合は何もしない
				return;
			}
			$head = '添付ファイルの添付先が変更されました。';
			$body[] = '旧ページ名：' . $arg[1];
			$body[] = '新ページ名：' . $arg[2];
			$body[] = '';
			$body[] = '以下のファイルが新ページに添付されています。';
			$body[] = join("\n", $to);
			$body[] = '以下のファイルが旧ページ添付されています。';
			$body[] = join("\n", $from);
		}
		else{
			return;
		}
		
		$subject = '[' . SITENAME . '] ' . $attach->getpage()->getpagename();
		$text[] = $head;
		$text[] = $this->geturl($attach->getpage());
		$text[] = '----------------------------------------------------------------------';
		$text[] = join("\n", $body);
		sendmail($subject, join("\n", $text));
	}
	
	
	protected function change_attachedfile($file, $arg)
	{
		if($arg[0] == 'attach'){
			$head = 'ファイルが添付されました。';
			$body[] = 'ファイル名：' . $file->getfilename();
			$body[] = 'サイズ：' . $file->getsize() . 'Byte';
		}
		else if($arg[0] == 'delete'){
			$head = 'ファイルが削除されました。';
			$body[] = 'ファイル名：' . $file->getfilename();
			$body[] = 'ダウンロード数：' . $arg[1];
		}
		else{
			return;
		}
		
		$subject = '[' . SITENAME . '] ' . $file->getpage()->getpagename();
		$text[] = $head;
		$text[] = $this->geturl($file->getpage());
		$text[] = '----------------------------------------------------------------------';
		$text[] = join("\n", $body);
		sendmail($subject, join("\n", $text));
	}
	
	
	private function geturl($page)
	{
		$ret = gettinyURL($page);
		if($ret != false){
			return $ret;
		}
		return getURL($page);
	}
}
