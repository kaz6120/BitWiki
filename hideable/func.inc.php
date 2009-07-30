<?php
/* 
 * $Id: func.inc.php,v 1.8 2005/09/04 18:11:29 youka Exp $
 */

/** URLの正規表現 */
define('EXP_URL', '(?:s?https?|ftp|file):\/\/[-a-zA-Z0-9_:@&?=+,.!\/~*%$\';#]+');	//()は意図的に含めない。
/** メールアドレスの正規表現 */
define('EXP_MAIL', '[a-zA-Z0-9_][-.a-zA-Z0-9_]*\@[-a-zA-Z0-9]+(?:\.[-a-zA-Z0-9_]+)+');


/**
 * ページ名中のディレクトリ名の部分を返す。
 * 
 * @param	string	$pagename	ページ名
 * @return	string	ディレクトリ部分がない場合は空文字列。
 */
function getdirname($pagename)
{
	$path = explode('/', resolvepath($pagename));
	array_pop($path);
	return join('/', $path);
}


/**
 * 経過時間を取得する。
 * 
 * @param	int	$timestamp
 * @return	string	経過時間を表す文字列。
 */
function getold($timestamp)
{
	$life = time() - $timestamp;
	if($life < 60){
		return $life . 's';
	}
	if($life < 60*60){
		return ((int)floor($life/60)) . 'm';
	}
	if($life < 60*60*24){
		return ((int)floor($life/(60*60))) . 'h';
	}
	return ((int)floor($life/(60*60*24))) . 'd';
}


/**
 * 短いURLを取得する。
 * 
 * @param	mixed	$page	ページ。Pageまたはstring。
 * @return	mixed	URL(string)。ページが存在しない場合はfalseを返す。
 */
function gettinyURL($page)
{
	if(is_string($page)){
		$page = Page::getinstance($page);
	}
	$num = $page->getnum();
	if($num === null){
		return false;
	}
	return SCRIPTURL . "?n=$num";
}


/**
 * URLを取得する。
 * 
 * @param	mixed	$page	ページ。Pageまたはstring。
 * @return	string	URL
 */
function getURL($page)
{
	if(is_string($page)){
		$page = Page::getinstance($page);
	}
	$encoded = preg_replace('/%2F/', '/', rawurlencode($page->getpagename()));
	return SCRIPTURL . '/' . $encoded;
}


/**
 * ファイルパス風ワイルドカードパターン（'?'と'*'）を正規表現に変換する。
 * 
 * @param	string	$str	ワイルドカードパターン
 * @return	string	正規表現
 */
function glob2ereg($str)
{
	$s = mb_ereg_quote($str);
	$s = mb_ereg_replace('\\?', '.', $s);
	$s = mb_ereg_replace('\\*', '.*', $s);
	return $s;
}

/**
 * 配列にキーがそれぞれ存在する事を確認する。
 * 
 * @param	array	$array	配列
 * @param	mixed	$key	確認するキー（可変長引数）
 * @return	bool	全てのキーが存在すればtrue
 */
function keys_exists(/* $array, $key, .... */)
{
	assert(func_num_args() > 2);
	$arg = func_get_args();
	$array = array_shift($arg);
	$keys = $arg;
	
	foreach($keys as $k){
		if(!isset($array[$k])){
			return false;
		}
	}
	return true;
}


/**
 * 前後の空白行を切り取る。
 * 
 * @param	string	$str
 * @return	string
 */
function linetrim($str)
{
	$text = explode("\n", $str);
	while($text != array() && trim($text[0]) == ''){
		array_shift($text);
	}
	while($text != array() && trim($text[count($text)-1]) == ''){
		array_pop($text);
	}
	return join("\n", $text);
}


/**
 * InterWikiからリンクを作る。
 * 
 * @param	string	$interwikiname
 * @param	string	$str	リンク先に渡す文字列。
 * @param	string	$alias	aタグで囲まれる文字列。
 * @return	string	aタグ
 */
function makeinterwikilink($interwikiname, $str, $alias = '')
{
	$alias = htmlspecialchars($alias == '' ? "$interwikiname:$str" : $alias);
	
	foreach(explode("\n", Page::getinstance('InterWikiName')->getsource()) as $s){
		if(mb_ereg("^-\[{$interwikiname}[\t 　]+(.+?)(?:[\t 　]+(\S+))?\]", $s, $m)){
			if($m[2] != ''){
				$str = mb_convert_encoding($str, $m[2], 'UTF-8');
			}
			$encoded = rawurlencode($str);
			$url = htmlspecialchars(mb_ereg_replace('\$1', $encoded, $m[1]));
			if($url{0} == '?'){
				$url = SCRIPTURL . $url;
			}
			return "<a class=\"interwiki\" href=\"$url\">$alias</a>";
		}
	}
	
	$ret = '<span class="nointerwikiname" title="InterWikiNameが定義されていません">';
	$ret .= $alias;
	$ret .= '</span>';
	return $ret;
}


/**
 * WikiName(BracketName)用リンクを作る。
 * 
 * @param	mixed	$page	ページ。Pageまたはstring
 * @param	string	$alias	aタグで囲まれる文字列。
 * @return	string	aタグ
 */
function makelink($page, $alias = '')
{
	if(is_string($page)){
		$page = Page::getinstance($page);
	}
	$url = getURL($page);
	$str = htmlspecialchars($alias == '' ? $page->getpagename() : $alias);
	if($page->isexist()){
		$title = htmlspecialchars($page->getpagename());
		return "<a href=\"$url\" title=\"$title\">$str</a>";
	}
	else{
		$title = '存在しないページ';
		return "<a class=\"noexistpage\" href=\"$url\" title=\"$title\">${str}</a>";
	}
}


/**
 * 配列要素にヒットする正規表現を生成する。
 * 
 * @param	array(string)	&$pagelist	この関数の実行後、$pagelistの中身は保証されない。
 * @return	string	正規表現
 */
function makelinkexp(&$pagelist)
{
	if(count($pagelist) <= 1){
		return count($pagelist) == 0 ? '' : mb_ereg_quote($pagelist[0]);
	}
	
	$emptyflag = false;
	$bin = array();
	while($pagelist != array()){
		$pagename = array_pop($pagelist);
		if($pagename != ''){
			$bin[mb_substr($pagename, 0, 1)][] = mb_substr($pagename, 1);
		}
		else{
			$emptyflag = true;
		}
	}
	
	$key = array_keys($bin);
	foreach($key as $k){
		$ret[] = mb_ereg_quote($k) . makelinkexp($bin[$k]);
	}
	
	if(count($ret) == 1){
		return $emptyflag ? '(?:'.$ret[0].')?' : $ret[0];
	}
	else{
		return '(?:' . join('|', $ret) . ')' . ($emptyflag ? '?' : '');
	}
}


/**
 * 入れ子配列対応array_map()。
 * 
 * @param	string	$func	配列要素を受け取る関数の名前。
 * @param	array	$var	処理対象の配列。
 * @return	array	$funcにかけられた配列。
 */
function map($func, $var)
 {
	if(is_array($var)){
		$ret = array();
		foreach($var as $k => $v){
			$ret[$k] = map($func, $v);
		}
		return $ret;
	}
	else{
		return $func($var);
	}
}


/**
 * mb_ereg()の正規表現文字をクオートする。
 * 
 * @param	string	$str	クオートしたい文字列。
 * @return	string	クオートされた文字列。
 */
function mb_ereg_quote($str)
{
	return mb_ereg_replace('([.\\\\+*?\[^\]\$(){}=!<>|:])', '\\\1', $str);
}


/**
 * マルチバイト文字を大きいとみなすnatcasesort()。
 */
function mb_natcasesort(&$array)
{
	usort($array, 'mb_strnatcasecmp');
}

/**
 * 第２引数に文字列長以上の値を与えると空文字列を返すmb_substr()。
 * 
 * mb_substr()は第２引数に文字列長以上の値を与えるとfalseを返してくるように修正されると思われる。
 * @see http://bugs.php.net/bug.php?id=28899
 */
function _mb_substr($string, $start, $length = null)
{
	if($start >= mb_strlen($string)){
		return '';
	}
	
	if($length === null){
		return mb_substr($string, $start);
	}
	else{
		return mb_substr($string, $start, $length);
	}
}


/**
 * マルチバイト文字を大きいとみなすstrnatcasecmp()。
 */
function mb_strnatcasecmp($a, $b)
{
	$a = mb_convert_kana($a, 'KVCas');
	$b = mb_convert_kana($b, 'KVCas');
	
	while(mb_strlen($a) > 0 && mb_strlen($b) > 0){
		$digit_a = mb_ereg('^(.*?)(\d+(?:\.\d+)?)(.*)$', $a, $ma);
		$digit_b = mb_ereg('^(.*?)(\d+(?:\.\d+)?)(.*)$', $b, $mb);
		if($digit_a && $digit_b){
			$ret = strcasecmp($ma[1] . '0', $mb[1] . '0');	//記号を数字より優先させるために0を付加
			if($ret == 0){
				$ret = strnatcmp($ma[2], $mb[2]);
				if($ret == 0){
					$ret = strcmp($ma[1], $mb[1]);
					if($ret == 0){
						$a = $ma[3];
						$b = $mb[3];
						continue;
					}
					else{
						return $ret;
					}
				}
				else{
					return $ret;
				}
			}
			else{
				return $ret;
			}
		}
		else{
			break;
		}
	}
					
	return strcasecmp($a, $b);	
}


/**
 * mailアドレスにシンプルな保護を施す（aタグ用）
 */
function protectmail_url($address)
{
	$encoded = chunk_split(bin2hex($address), 2, '%'); 
	return '%' . _substr($encoded, 0, strlen($encoded) - 1); 
}


/**
 * mailアドレスにシンプルな保護を施す（html地の文用）
 */
function protectmail_html($address)
{
	$encoded = chunk_split(bin2hex($address), 2, ';&#x'); 
	return '&#x' . _substr($encoded, 0, strlen($encoded) - 3); 
}


/**
 * ページを指定してリダイレクトする。
 * 
 * @param	Page	$page	リダイレクト先のページ名。
 */
function redirect($page)
{
	header('Location: ' . getURL($page));
	exit();
}


/**
 * パスを解決する。
 * 
 * @param	string	$pagename	パス。
 * @param	string	$basepath	相対パスのとき解決の基準となるパス。
 * @return	string	フルパス。
 */
function resolvepath($pagename, $basepath = '')
{
	$pagename = trim($pagename);
	if(mb_ereg('^\.\.?/', $pagename)){
		$path = trim($basepath) . '/./' . $pagename;
	}
	else{
		$path = $pagename;
	}
	
	$path = mb_split('/', $path);
	$ret = array();
	foreach($path as $p){
		if($p == '' || $p == '.'){
			continue;
		}
		if($p == '..'){
			array_pop($ret);
		}
		else{
			array_push($ret, $p);
		}
	}
	return join('/', $ret);
}


/**
 * メールが有効になっている場合に管理者にメールを送る。
 * 
 * @param	string	$subject	メールの題名。UTF-8。
 * @param	string	$text	メール本文。UTF-8。
 */
function sendmail($subject, $text)
{
	if(!MAIL_USE){
		return;
	}
	
	ini_set('SMTP', MAIL_SMTP);
	ini_set('smtp_port', MAIL_SMTP_PORT);
	
	$header[] = 'From: ' . MAIL_FROM;
	$header[] = 'X-Mailer: PHP ' . phpversion() . ' / KinoWiki ' . KINOWIKI_VERSION;
	$info[] = 'WikiID: ' . WIKIID;
	$info[] = date('Y-m-d H:i:s');
	$info[] = 'REMOTE_HOST: ' . gethostbyaddr($_SERVER['REMOTE_ADDR']);
	$info[] = 'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'];
	$info[] = '----------------------------------------------------------------------';
	$info[] = $text;
	mb_language('uni');
	mb_send_mail(MAIL_TO, $subject, join("\r\n", $info), join("\r\n", $header));
}


/**
 * 第２引数に文字列長以上の値を与えると空文字列を返すsubstr()。
 */
function _substr($string, $start, $length = null)
{
	if($start >= strlen($string)){
		return '';
	}
	
	if($length === null){
		return substr($string, $start);
	}
	else{
		return substr($string, $start, $length);
	}
}


/**
 * HTMLタグのパラメータを配列に変換する
 *
 * @param string	$param	「name1="value1" name2="value2" ...」形式の文字列
 * @return array	連想配列
 */
function tagparam2array($param)
{
	$ret = array();
	while($param != '' && mb_ereg('^.*?([^\t 　]+?)=(?:(\'|")(.*?)\2|([^\t 　\'"][^\t 　]*))', $param, $m)){
		$ret[$m[1]] = $m[3] . $m[4];	//$m[3]と$m[4]のどちらかは空文字列なので、これでヒットしたほうを取得できる
		$param = _substr($param, strlen($m[0]));
	}
	return $ret;
}

?>