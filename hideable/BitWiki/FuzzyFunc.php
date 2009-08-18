<?php
/* 
 * FuzzyFunc
 *
 * based onfuzzyfunc.inc.php,v 1.2 2005/06/29 10:54:02 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.6.29
 * @version 9.8.18
 */



/**
 * あいまい処理に関するユーティリティクラス
 */
class FuzzyFunc
{
	/**
	 * あいまい検索用正規表現作成
	 *
	 * @param string	もとになるキーワード
	 * @return	string	あいまい検索用正規表現
	 */
	static function makefuzzyexp($key)
	{
		return '(?:' . join('|', self::makefuzzyexplist($key)) . ')';
	}

	
	/**
	 * あいまい検索用正規表現の集合体を取得する。
	 *
	 * @param string	もとになるキーワード
	 * @return	array(string)	あいまい検索用正規表現を集めた配列
	 */
	static function makefuzzyexplist($key)
	{
		//仮名は全角カタカナに、英数字・空白文字は半角に、 濁点付きの文字を一文字に、記号は全角に。
		$_key = mb_strtolower(mb_convert_kana($key, 'KVCas'));
		foreach(self::$han2zen_mark_table as $han => $zen){
			$_key = mb_ereg_replace(mb_ereg_quote($han), $zen, $_key);
		}
		//２文字以上での表現を１文字にする
		foreach(self::$two2one as $from => $to){
			$_key = mb_ereg_replace($from, $to, $_key);
		}
		//文字により削除（表記ゆれまたはごみ）
		$_key = mb_ereg_replace('[ッー・゛゜、。]', '', $_key);
		
		//異体文字に対応する。
		$char = array();
		$len = mb_strlen($_key);
		for($i = 0; $i < $len; $i++){
			$c = mb_substr($_key, $i, 1);
			$char[] = isset($GLOBALS['itaimojitable'][$c]) ? $GLOBALS['itaimojitable'][$c] : mb_ereg_quote($c);
		}
		//ちょっと違う単語を許すようにする
		$list = self::makeagrepexplist($char);
		//表記ゆれになる文字を挟み込む
		$ret = array();
		foreach($list as $a){
			$ret[] = join('[・ーｰ]?', $a);
		}
		
		return $ret;
	}

	
//	/**
//	 * agrepライクな誤りを許容する正規表現の集合体を取得する。
//	 *
//	 * @param array(string)	$word	元になるキーワードの配列（１要素１文字扱い）
//	 * @param int	$n	許容する誤り文字数。$keyの文字数を超えてはいけない（少し大きな値を指定するだけでものすごい高負荷になるので注意）。
//	 * @return	array(array(string))	誤りを許容する正規表現を集めた配列
//	 */
//	protected static function makeagrepexplist($word, $n)
//	{
//		if($n < 1){
//			return array($word);
//		}
//		
//		for($i = 0; $i < count($word) - ($n-1); $i++){
//			$str = array_slice($word, 0, $i);
//			foreach(self::makeagrepexplist(array_slice($word, $i+1), $n-1) as $str2){
//				$ret[] = array_merge($str, array('.?'), $str2);
//			}
//		}
//		return $ret;
//	}
	/**
	 * agrepライクな誤りを許容する正規表現の集合体を取得する。
	 * FUZZYLINK_SPELLMISSMINSIZE文字以上のとき誤りを１文字まで許す。
	 *
	 * @param array(string)	$word	元になるキーワードの配列（１要素１文字扱い）
	 * @return	array(array(string))	誤りを許容する正規表現を集めた配列
	 */
	protected static function makeagrepexplist($word)
	{
		$ret[] = $word;
		if(count($word) >= FUZZYLINK_SPELLMISSMINSIZE){
			$ret[] = array_slice($word, 1);
			for($i = 1; $i < count($word)-1; $i++){
				$a = $word;
				$a[$i] = '.?';
				$ret[] = $a;
			}
			$ret[] = array_slice($word, 0, count($word)-1);
		}
		return $ret;
	}

	
	//２文字以上での表現を１文字にするテーブル
	static protected $two2one = array(
		'ヴァ' => 'バ',
		'ヴィ' => 'ビ',
		'ヴェ' => 'ベ',
		'ヴォ' => 'ボ'
	);
	
	//半角記号を全角にするテーブル
	static protected $han2zen_mark_table = array(
		'!' => '！',
		'"' => '”',
		'#' => '＃',
		'$' => '＄',
		'%' => '％',
		'&' => '＆',
		"'" => '’',
		'(' => '（',
		')' => '）',
		'*' => '＊',
		'+' => '＋',
		',' => '，',
		'-' => '－',
		'.' => '．',
		'/' => '／',
		':' => '：',
		';' => '；',
		'<' => '＜',
		'=' => '＝',
		'>' => '＞',
		'?' => '？',
		'@' => '＠',
		'[' => '［',
		'\\' => '￥',
		']' => '］',
		'^' => '＾',
		'_' => '＿',
		'`' => '‘',
		'{' => '｛',
		'|' => '｜',
		'}' => '｝',
		'~' => '～'
	);
}


?>
