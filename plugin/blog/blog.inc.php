<?php
/*
 * $Id: blog.inc.php,v 1.3 2005/12/24 00:14:03 youka Exp $
 *
 * @version: 9.8.6
 */

class Plugin_blog extends Plugin
{
	//カテゴリ別の目次を格納するディレクトリ
	protected $category_prefix = 'カテゴリ';
	
	//blog別のパスワード。このリストにない場合はKinoWiki本体の管理者パスワードを使う。
	//例：protected $passwordlist = array('blogname' => 'password');
	protected $passwordlist = array();
	
	//クッキーを使ってパスワードを覚えさせるときは有効期限（秒数）を、そうでないときは0を。
	protected $cookie = 2592000;	//60*60*24*30
	
	
	protected $blogname;
	protected $categorypagename;
	protected $password;
	protected $subject = '';
	protected $text = '';
	protected $categories = array();
	protected $continuefrom = '';
	protected $date = '';
	protected $pagename = '';
	protected $datepagename = '';
	protected $sendingtrackback = false;
	
	
	function do_block($page, $param1, $param2)
	{
		if (trim($param1) == '') {
			throw new PluginException('引数がありません。', $this);
		}
		
		$this->blogname = trim($param1);
		$this->categorypagename = $this->blogname . '/' . $this->category_prefix;
		$this->password = isset(Vars::$cookie['plugin_blog']) ? Vars::$cookie['plugin_blog'] : '';
		return $this->getform();
	}
	
	
	function do_url()
	{
		if (isset(Vars::$get['continue'])) {
			if (mb_ereg('^(.+?)/\d{4}-\d{2}-\d{2}/.+$', Vars::$get['continue'], $m)) {
				$this->blogname = $m[1];
				$this->categorypagename = $this->blogname . '/' . $this->category_prefix;
				$this->password = isset(Vars::$cookie['plugin_blog']) ? Vars::$cookie['plugin_blog'] : '';
				$this->continuefrom = Vars::$get['continue'];
				$ret['title'] = $this->blogname . ' への新規エントリー';
				$ret['body'] = $this->getform();
				return $ret;
			} else {
				throw new PluginException('つづき元のページ名が正しくありません。', $this);
			}
		} else{
			if (isset(Vars::$post['blogname']) && Vars::$post['blogname'] != '') {
				$this->blogname = Vars::$post['blogname'];
				$this->categorypagename = $this->blogname . '/' . $this->category_prefix;
				$this->password = isset(Vars::$cookie['plugin_blog']) ? Vars::$cookie['plugin_blog'] : '';
				return $this->post();
			}
			else{
				throw new PluginException('パラメータが足りません。', $this);
			}
		}
	}
	
	
	/**
	 * 投稿用フォームを取得する。
	 */
	protected function getform()
	{
		$db = DataBase::getinstance();
		$_categorypagename = $db->escape($this->categorypagename);
		$query  = "SELECT pagename FROM page";
		$query .= " WHERE pagename like '{$_categorypagename}/%'";
		$query .= " ORDER BY pagename ASC";
		$result = $db->query($query);
		$categorybutton = array();
		$prefix = mb_ereg_quote($this->categorypagename);
		$exp = "^{$prefix}/(([^/]+).*)$";
		while($row = $db->fetch($result)) {
			mb_ereg($exp, $row['pagename'], $m);
			$categorybutton[$m[2]][] = $m[1];
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('blogname', $this->blogname);
		$smarty->assign('continue', $this->continuefrom);
		$smarty->assign('text', $this->text);
		$smarty->assign('date', $this->date);
		$smarty->assign('subject', $this->subject);
		$smarty->assign('category', join('|', $this->categories));
		$smarty->assign('password', $this->password);
		$smarty->assign('categorybutton', $categorybutton);
		return $smarty->fetch('form.tpl.htm');
	}
	
	
	/**
	 * ポストされたデータを処理する。
	 */
	protected function post()
	{
		$error = $this->checkpostdata();
		if ($error != array()) {
			$mes = '<p class="warning">' . join("<br />\n", $error) . "</p>\n";
			$ret['title'] = $this->blogname . ' への追加';
			$ret['body'] = $mes . $this->getform();
			return $ret;
		}
		
		$this->write();
		if ($this->sendingtrackback) {
			$errormes = $this->sendtrackback();
			if ($errormes != array()) {
				$ret['title'] = $this->blogname . ' への追加';
				$smarty = $this->getSmarty();
				$smarty->assign('errormes', $errormes);
				$smarty->assign('pagename', $this->pagename);
				$ret['body'] = $smarty->fetch('trackbackerror.tpl.htm');
				return $ret;
			}
		}
		redirect(Page::getinstance($this->pagename));
	}
	
	
	protected function checkpostdata()
	{
		$error = array();
		
		//タイトルの入力チェック
		if (!isset(Vars::$post['subject']) || trim(Vars::$post['subject']) == '') {
			$error[] = 'タイトルがありません。';
		}
		$this->subject = Vars::$post['subject'];
		
		//本文の入力チェック
		if (!isset(Vars::$post['text']) || trim(Vars::$post['text']) == '') {
			$error[] = '本文がありません。';
		}
		$this->text = Vars::$post['text'];
		
		//カテゴリの入力チェック
		if (!isset(Vars::$post['category']) || !mb_ereg("[^　\s|]", Vars::$post['category'])) {
			$error[] = 'カテゴリがありません。';
		}
		$this->categories = array_unique(array_map('trim', explode('|', Vars::$post['category'])));
		$i = array_search('', $this->categories);
		if ($i !== false) {
			unset($this->categories[$i]);
		}	//array_unique()により空文字列の要素は１つだけしか存在しないので、１つ削除すればOK
		
		//「続き」の元記事のチェック
		if (!isset(Vars::$post['continue']) || 
            (trim(Vars::$post['continue']) != '' && !Page::getinstance(trim(Vars::$post['continue']))->isexist())
          ) {
			$error[] = 'つづきの元のページがありません。';
		}
		$this->continuefrom = resolvepath(Vars::$post['continue']);
		
		//入力された日付のチェック
		if (!isset(Vars::$post['date']) || trim(Vars::$post['date']) == '') {
			$error[] = '日付がありません。';
		} else {
			if (!mb_ereg('^\s*(\d{4})[-/](\d{1,2})[-/](\d{1,2})\s*$', Vars::$post['date'], $m)) {
				$error []= '日付の書式が正しくありません。';
			} else {
				if (!checkdate($m[2], $m[3], $m[1])) {
					$error[] = '日付が正しくありません。';
				} else {
					$this->date = sprintf('%4d-%02d-%02d', $m[1], $m[2], $m[3]);
				}
			}
		}
		
		//パスワードのチェック
		if (!isset(Vars::$post['password'])) {
			$error[] = 'パスワードがありません。';
			setcookie('plugin_blog', '', -3600);	//パスワードが無いときはクッキーを削除
		} else {
			$pass = isset($this->passwordlist[$this->blogname]) ? md5($this->passwordlist[$this->blogname]) : ADMINPASS;
			if (md5(Vars::$post['password']) != $pass) {
				$error[] = 'パスワードが正しくありません。';
			} else {
				$this->password = Vars::$post['password'];
				if ($this->cookie > 0) {
					setcookie('plugin_blog', Vars::$post['password'], time() + $this->cookie);
				}
			}
		}
		
		//TrackBack送信可否のチェック
		$this->sendingtrackback = (isset(Vars::$post['sendingtrackback']) && Vars::$post['sendingtrackback'] == 'on') ? true : false;
		
		//入力から他変数の組み立て
		if ($this->date != '') {
			$this->datepagename = $this->blogname . '/' . $this->date;
			if ($this->subject != '') {
				$this->pagename = $this->datepagename . '/' . $this->subject;
			}
		}
		
		//すでに存在するページに書くのはNG
		if (Page::getinstance($this->pagename)->isexist()) {
			$error[] = 'ページがすでに存在します。タイトルを変更してください。';
		}
		
		return $error;
	}
	
	
	
	/**
	 * 投稿記事の保存。
	 */
	protected function write()
	{
		if (mb_ereg('^' . mb_ereg_quote($this->blogname) . '/.+?/(.+)$', $this->continuefrom, $m)) {
			$continuefrom = "[[{$m[1]}>{$this->continuefrom}]]";
		} else {
			$continuefrom = '';
		}
		
		$catlist = '';
		foreach($this->categories as $c) {
			$catlist .= "&#x5b;[[$c>{$this->categorypagename}/$c]]&#x5d;";
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('subject', $this->subject);
		$smarty->assign('text', $this->text);
		$smarty->assign('categorylist', $catlist);
		$smarty->assign('timestamp', time());
		$smarty->assign('continuefrom', $continuefrom);
		$smarty->assign('pagename', $this->pagename);
		$source = $smarty->fetch('blog.tpl');
		
		//autolsプラグインと衝突しないように、書き込み順に注意。
		DataBase::getinstance()->begin();
		$this->_write_datepage();
		Page::getinstance($this->pagename)->write($source);
		$this->_write_category();
		$this->_write_continue();
		DataBase::getinstance()->commit();
	}
	
	
	/**
	 * 日付ページに登録する。
	 */
	protected function _write_datepage()
	{
		$page = Page::getinstance($this->datepagename);
		$old = $page->getsource();
		if ($old == '') {
			$old = "#blognavi\n\n<bloginclude>\n</bloginclude>\n\n#blognavi";
		}
		$old = mb_ereg_replace("<bloginclude>\n", "<bloginclude>\n{$this->pagename}\n", $old);
		$page->write($old, true);
	}
	
	
	/**
	 * カテゴリに登録する。
	 */
	protected function _write_category()
	{
		foreach($this->categories as $c) {
			$list = "-({$this->date})&nbsp;&nbsp;[[{$this->subject}>{$this->pagename}]]\n";
			$page = Page::getinstance($this->categorypagename . '/' . $c);
			$old = $page->getsource();
			$page->write($list . $old, true);
		}
	}
	
	
	/**
	 * つづき元のページに登録する。
	 */
	protected function _write_continue()
	{
		if (!mb_ereg('^.+?/.+?/.+$', $this->continuefrom)) {
			return;
		}
		
		$continueon = "[[{$this->subject}>{$this->pagename}]]";
		$page = Page::getinstance($this->continuefrom);
		$old = $page->getsource();
		$old = mb_ereg_replace("(#right{\n)((?:.|\n)+?}\n----\n)", "\\1&#x5b;{$continueon}&#x5d;につづく\n\\2", $old);
		$page->write($old, true);
	}
	
	
	/**
	 * 本文中のリンク先にTrackBack Pingを送る
	 *
	 * return array('url' => 'error_mes')	url:エラーの発生した送信先URL error_mes:エラー内容
	 */
	private function sendtrackback()
	{
		require_once('Services/Trackback.php');
		
		$data['title'] = $this->subject;
		$data['excerpt'] = mb_strlen($this->text) >= 256 ? mb_substr($this->text, 0, 252) . '...' : $this->text;
		$data['url'] = gettinyURL($this->pagename);
		$data['blog_name'] = SITENAME . ' - ' . $this->blogname;
		$tb = Services_Trackback::create(array('id' => 'dummy'), array('timeout' => 4, 'fetchlines' => 999999));
		if (PEAR::isError($tb)) {
			throw new PluginException('TrackBack送信時にエラーが発生しました(' . $tb->getMessage() . ')。', $this);
		}
		
		$ret = array();
		$count = preg_match_all('/https?:\/\/[-a-zA-Z0-9_:@&?=+,.!\/~*%$\';#]+/u', $this->text, $m);
		if ($count) {
			foreach($m[0] as $url) {
				$tb->set('url', $url);
				$result = $tb->autodiscover();
				if (!PEAR::isError($result)) {
					foreach($data as $key => $val) {
						$tb->set($key, $val);
					}
					$r = $tb->send();
					if (PEAR::isError($r)) {
						$ret[$url] = $r->getMessage();
					}
				} else {
					$ret[$url] = $result->getMessage();
				}
			}
		}
		return $ret;
	}
}

