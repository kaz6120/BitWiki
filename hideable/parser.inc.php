<?php
/* 
 * $Id: parser.inc.php,v 1.11 2005/11/29 08:41:31 youka Exp $
 */




/**
 * Pageの内容ををhtmlに変換する。
 * 
 * @param	Page	$page
 * @return	string	HTML形式の文字列。
 */
function convert_Page($page)
{
	return HTMLConverter::visit(parse_Page($page));
}


/**
 * Wikiのソースをブロック要素としてhtmlに変換する。
 * 
 * @param	string	$source	ソース
 * @param	string	$pagename	ページ名を$pagenameとして変換する。
 * @return	string	HTML形式の文字列。
 */
function convert_block($source, $pagename)
{
	return HTMLConverter::visit(parse_block($source, $pagename));
}


/**
 * Wikiのソースをインライン要素としてhtmlに変換する。
 * 
 * @param	string	$source	ソース
 * @param	string	$pagename	ページ名を$pagenameとして変換する。
 * @return	string	HTML形式の文字列。
 */
function convert_inline($source, $pagename)
{
	return HTMLConverter::visit(parse_inline($source, $pagename));
}


/**
 * Pageの内容をを内部表現形式に変換する。
 * 
 * @param	Page	$page
 * @return	T_Body
 */
function parse_Page($page)
{
	$_source = mb_ereg_replace('\r?\n', "\n", $page->getsource());
	return T_Body::parse($_source, new Context($page->getpagename()));
}


/**
 * Wikiのソースをブロック要素として内部表現形式に変換する。
 * 
 * @param	string	$source	ソース
 * @param	string	$pagename	ページ名を$pagenameとして変換する。
 * @return	T_Body
 */
function parse_block($source, $pagename)
{
	$_source = mb_ereg_replace('\r?\n', "\n", $source);
	return T_Body::parse($_source, new Context($pagename));
}


/**
 * Wikiのソースをインライン要素として内部表現形式に変換する。
 * 
 * @param	string	$source	ソース
 * @param	string	$pagename	ページ名を$pagenameとして変換する。
 * @return	string	T_Line
 */
function parse_inline($source, $pagename)
{
	$_source = mb_ereg_replace('\r?\n', "\n", $source);
	return T_Line::parse($_source, new Context($pagename));
}



/**
 * 文脈としての情報を保持する構造体。
 */
class Context
{
	public $pagename;	//起点となるページの名前。
	
	
	function __construct($pagename)
	{
		$this->pagename = resolvepath($pagename);
	}
}



/**
 * 内部表現の要素を表すクラス。
 */
abstract class T_Element
{
	protected $source;	//パース元ソース
	protected $context;	//文脈情報
	protected $elements = array();	//内包する要素
	
	protected $parent = null;	//上の要素への参照
	protected $prev = null;	//前の要素への参照
	protected $next = null;	//次の要素への参照
	
	function getsource(){ return $this->source; }
	function getcontext(){ return $this->context; }
	function getelements(){ return $this->elements; }
	function getelem(){ return $this->elements[0]; }
	function getparent(){ return $this->parent; }
	function getprev(){ return $this->prev; }
	function getnext(){ return $this->next; }
	
	
	/**
	 * 内包する要素を保存する。
	 * 
	 * @param	T_Element	$elem
	 */
	protected function addelement($elem)
	{
		$elem->parent = $this;
		$this->elements[] = $elem;
		$last = count($this->elements) - 1;
		if($last != 0){
			$this->elements[$last-1]->next = $this->elements[$last];
			$this->elements[$last]->prev = $this->elements[$last-1];
		}
	}
	
	
	/**
	 * Converterに要素を実行させる。
	 * 
	 * @param	Converter	$v
	 */
	function accept($v)
	{
		$call = 'visit' . get_class($this);
		return $v->$call($this);
	}
	
	
	/**
	 * パースしたものを元に戻す。
	 * 
	 * @param	string	&$source	パースした部分を戻される。
	 */
	function undo(&$source)
	{
		$source = $this->source . $source;
	}
	
	
	protected function __construct($source, $context)
	{
		$this->source = $source;
		$this->context = $context;
	}
}



/**
 * ブロック型要素を表すクラス。
 */
abstract class T_BlockElement extends T_Element
{
	/**
	 * パースを行う。
	 * 
	 * @param	string	&$source	パースできた部分は削除される。
	 * @param	Context	$context
	 * @return	Element	パースエラーの場合はnullを返す。
	 */
	//public static abstract function parse(&$source, $context);
}



/**
 * インライン型要素を表すクラス。
 */
abstract class T_InlineElement extends T_Element
{
	/**
	 * パースを行う。
	 * 
	 * @param	string	&$source	パースできた部分は削除される。
	 * @param	Context	$context
	 * @return	Element	パースエラーの場合はnullを返す。
	 */
	//public static abstract function parse(&$source, $context);
}



/**
 * ブロック要素の集合を表す。
 */
class T_Body extends T_BlockElement
{
	public static function parse(&$source, $context)
	{
		static $classlist = array('T_Empty', 'T_Block', 'T_Paragraph');
		
		$_source = $source;
		$elements = array();
		while($source != ''){
			foreach($classlist as $class){
				$ret = eval("return $class::parse(\$source, \$context);");
				if($ret != null){
					$elements[] = $ret;
					break;
				}
			}
		}
		return new T_Body($_source, $context, $elements);
	}
	
	
	protected function __construct($source, $context, $elements)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($elements as $e){
			$this->addelement($e);
		}
	}
}



/**
 * 空行を表す。
 */
class T_Empty extends T_BlockElement
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^[\t 　]*(?:\n|$)', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			return new T_Empty($m[0], $context);
		}
		return null;
	}
}



/**
 * ブロック要素１つを表す。
 */
abstract class T_Block extends T_BlockElement
{
	public static function parse(&$source, $context)
	{
		static $classlist = array('T_Heading', 'T_Horizon', 'T_Pre', 'T_BlockQuote', 'T_UL', 'T_OL', 'T_DL', 'T_Table', 'T_BlockPlugin', 'T_BlockTag', 'T_Comment');
		
		foreach($classlist as $class){
			$ret = eval("return $class::parse(\$source, \$context);");
			if($ret != null){
				return $ret;
			}
		}
		return null;
	}
}



/**
 * 見出しを表す。
 */
class T_Heading extends T_Block
{
	protected $level;
	
	function getlevel(){ return $this->level; }
	function getstring(){ return $this->elements[0]->getsource(); }
	
	
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^([*＊]{1,4})[\t 　]*(.+?)[\t 　]*(?:\n|$)', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			return new T_Heading($m[0], $context, mb_strlen($m[1]), T_Line::parse($m[2], $context));
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $level, $subject)
	{
		$this->source = $source;
		$this->context = $context;
		$this->level = $level;
		$this->addelement($subject);
	}
}



/**
 * 水平線を表す。
 */
class T_Horizon extends T_Block
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^[-－ー―]{4,}[\t 　]*(?:\n|$)', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			return new T_Horizon($m[0], $context);
		}
		return null;
	}
}



/**
 * 整形済みテキストを表す。
 */
class T_Pre extends T_Block
{
	protected $text;	//array
	
	function gettext(){ return $this->text; }
	
	
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^(?:[ \t].*?(?:\n|$))+', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			$text = mb_ereg_replace('^[ \t](.*?(?:\n|$))', '\1', $m[0], 'm');
			return new T_Pre($m[0], $context, $text);
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $text)
	{
		$this->source = $source;
		$this->context = $context;
		$this->text = $text;
	}
}



/**
 * 引用を表す。
 */
class T_BlockQuote extends T_Block
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^(?:[>＞].*?(?:\n|$))+', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			$text = mb_ereg_replace('^[>＞](.*?(?:\n|$))', '\1', $m[0], 'm');
			return new T_BlockQuote($m[0], $context, T_Body::parse($text, $context));
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $body)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($body);
	}
}



/**
 * 箇条書きを表す。
 */
class T_UL extends T_Block
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^(?:[-－・].*?(?:\n|$))+', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			$text = mb_ereg_replace('^[-－・](.*?(?:\n|$))', '\1', $m[0], 'm');
			return new T_UL($m[0], $context, T_List::parse($text, $context));
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $list)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($list);
	}
}



/**
 * 数字付き箇条書きを表す。
 */
class T_OL extends T_Block
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^(?:[+＋].*?(?:\n|$))+', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			$text = mb_ereg_replace('^[+＋](.*?(?:\n|$))', '\1', $m[0], 'm');
			return new T_OL($m[0], $context, T_List::parse($text, $context));
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $list)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($list);
	}
}



/**
 * 箇条書き要素を表す。
 */
class T_List extends T_Block
{
	public static function parse(&$source, $context)
	{
		static $classlist = array('T_UL', 'T_OL', 'T_LI');
		
		$_source = $source;
		$list = array();
		while($source != ''){
			foreach($classlist as $class){
				$ret = eval("return $class::parse(\$source, \$context);");
				if($ret != null){
					$list[] = $ret;
					break;
				}
			}
			if($ret == null){
				//ここに来てはまずい（無限ループ防止）
				throw new FatalException('プログラムにバグがあります(T_List)');
			}
		}
		return new T_List($_source, $context, $list);
	}
	
	
	protected function __construct($source, $context, $list)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($list as $l){
			$this->addelement($l);
		}
	}
}



/**
 * 箇条書きの１項目を表す。
 */
class T_LI extends T_Block
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^(.*?)(?:\n|$)', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			return new T_LI($m[0], $context, T_Line::parse($m[1], $context));
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $line)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($line);
	}
}



/**
 * 定義リストを表す。
 */
class T_DL extends T_Block
{
	public static function parse(&$source, $context)
	{
		static $classlist = array('T_DT', 'T_DD');
		
		$_source = $source;
		
		$elem[] = T_DT::parse($source, $context);
		if($elem[0] != null){
			while($source != ''){
				foreach($classlist as $class){
					$ret = eval("return $class::parse(\$source, \$context);");
					if($ret != null){
						$elem[] = $ret;
						break;
					}
				}
				if($ret == null){
					break;
				}
			}
			return new T_DL(_substr($_source, 0, strlen($_source) - strlen($source)), $context, $elem);
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $elements)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($elements as $e){
			$this->addelement($e);
		}
	}
}



/**
 * 定義リストの単語を表す。
 */
class T_DT extends T_DL
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^([:：])([^\n]+?\1.*?(?:\n|$))', $source, $m)){
			$mark = $m[1];
			$term = T_Line::parse($m[2], $context, $mark);
			if(mb_ereg("^{$mark}[\t 　]*\n?", $m[2], $n)){
				$src = $mark . $term->getsource() . $n[0];
				$source = _substr($source, strlen($src));
				return new T_DT($src, $context, $term);
			}
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $term)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($term);
	}
}



/**
 * 定義リストの説明文を表す。
 */
class T_DD extends T_DL
{
	public static function parse(&$source, $context)
	{
		$_source = $source;
		
		$ret = T_DT::parse($source, $context);
		if($ret != null){
			$ret->undo($source);
			return null;
		}
		$ret = T_Empty::parse($source, $context);
		if($ret != null){
			$ret->undo($source);
			return null;
		}
		
		$ret = T_Block::parse($source, $context);
		if($ret != null){
			return new T_DD(_substr($_source, 0, strlen($_source) - strlen($source)), $context, $ret);
		}
		if(mb_ereg('^(.*?)(?:\n|$)', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			$ret = T_Line::parse($m[1], $context);
			return new T_DD(_substr($_source, 0, strlen($_source) - strlen($source)), $context, $ret);
		}
		//ここに来てはまずい（無限ループ防止）
		throw new FatalException('プログラムにバグがあります(T_DD)');
	}
	
	
	protected function __construct($source, $context, $elem)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($elem);
	}
}



/**
 * テーブルを表す。
 */
class T_Table extends T_Block
{
	public static function parse(&$source, $context)
	{
		$_source = $source;
		$elem[] = T_TR::parse($source, $context);
		if($elem[0] != null){
			while($source != ''){
				$e = T_TR::parse($source, $context);
				if($e == null){
					break;
				}
				if($e->getcols() != $elem[0]->getcols()){
					$e->undo($source);
					break;
				}
				$elem[] = $e;
			}
			return new T_Table(_substr($_source, 0, strlen($_source) - strlen($source)), $context, $elem);
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $elements)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($elements as $e){
			$this->addelement($e);
		}
	}
}



/**
 * テーブルの１行を表す。
 */
class T_TR extends T_Table
{
	protected $cols;
	
	function getcols(){ return $this->cols; }
	
	
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^\|((?:[^\n]*?\|)+)([HhLlCcRr]*)[\t 　]*(?:\n|$)', $source, $m)){
			while($m[1] != ''){
				$elem[] = T_TD::parse($m[1], $context, preg_split('//', $m[2], -1, PREG_SPLIT_NO_EMPTY));
			}
			$source = _substr($source, strlen($m[0]));
			return new T_TR($m[0], $context, $elem);
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $elements)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($elements as $e){
			$this->addelement($e);
		}
		$this->cols = count($elements);
	}
}



/**
 * テーブルの１マスを表す。
 */
class T_TD extends T_TR
{
	protected $align = null;
	protected $isheader = false;
	protected $bgcolor = null;
	
	function getalign(){ return $this->align; }
	function isheader(){ return $this->isheader; }
	function getbgcolor(){ return $this->bgcolor; }
	
	
	public static function parse(&$source, $context, $option = array())
	{
		$_source = $source;
		
		$pattern = '(?:LEFT|CENTER|RIGHT|BGCOLOR\(.+?\))';
		if(mb_eregi("^({$pattern}(?:,{$pattern})*):", $source, $m)){
			$option = array_merge($option, explode(',', $m[1]));
			$source = _substr($source, strlen($m[0]));
		}
		
		$e = T_Line::parse($source, $context, '\|');
		if($e == null || !mb_ereg('^\|', $source)){
			$source = $_source;
			return null;
		}
		$source = _substr($source, 1);
		return new T_TD(_substr($_source, strlen($_source) - strlen($source)), $context, $e, $option);
	}
	
	
	protected function __construct($source, $context, $elem, $option)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($elem);
		$this->setoption($option);
	}
	
	
	private function setoption($option)
	{
		foreach($option as $opt){
			switch(strtoupper($opt)){
				case 'H':
					$this->isheader = true;
					break;
				case 'L':
				case 'LEFT':
					$this->align = 'left';
					break;
				case 'C':
				case 'CENTER':
					$this->align = 'center';
					break;
				case 'R':
				case 'RIGHT':
					$this->align = 'right';
					break;
				default:
					if(mb_eregi('^BGCOLOR\((.+?)\)$', $opt, $m)){
						$this->bgcolor = $m[1];
					}
			}
		}
	}
}



/**
 * ブロック型プラグインを表す。
 */
class T_BlockPlugin extends T_Block
{
	protected $pluginname;
	protected $param1;
	protected $param2;
	
	function getpluginname(){ return $this->pluginname; }
	function getparam1(){ return $this->param1; }
	function getparam2(){ return $this->param2; }
	
	
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^#([a-zA-Z0-9_]+)(?:[\t 　]*\(([^\n]*?)\))?[\t 　]*\n?', $source, $m)){
			$pluginname = $m[1];
			$param1 = $m[2];
			$src = $m[0];
			$source = _substr($source, strlen($src));
			
			$param2 = '';
			if(mb_ereg('^{', $source)){
				$s = $_s = _substr($source, 1);
				while($s != ''){
					static $classlist = array('T_Empty', 'T_Block');
					foreach($classlist as $class){
						$ret = eval("return $class::parse(\$s, \$context);");
						if($ret != null){
							break;
						}
					}
					if($ret == null){
						T_Line::parse($s, $context, '[}\n]');
						if(mb_ereg('^}[\t 　]*\n?', $s, $m)){
							$len = strlen($source) - strlen($s) + strlen($m[0]);
							$src .= _substr($source, 0, $len);
							$source = _substr($source, $len);
							$param2 = _substr($_s, 0, strlen($_s) - strlen($s));
							break;
						}
					}
				}
			}
			
			return new T_BlockPlugin($src, $context, $pluginname, $param1, $param2);
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $pluginname, $param1, $param2)
	{
		$this->source = $source;
		$this->context = $context;
		$this->pluginname = $pluginname;
		$this->param1 = $param1;
		$this->param2 = $param2;
	}
}



/**
 * タグ型ブロックプラグインを表す。
 */
class T_BlockTag extends T_BlockPlugin
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^<([a-zA-Z0-9_]+)(?:[\t 　]+([^\n]*?))?[\t 　]*(/?)>[\t 　]*\n?', $source, $m)){
			$pluginname = $m[1];
			$param1 = $m[2];
			$src = $m[0];
			$source = _substr($source, strlen($src));
			
			$param2 = '';
			if($m[3] != '/'){
				$s = $_s = $source;
				while($s != ''){
					static $classlist = array('T_Empty', 'T_Block');
					foreach($classlist as $class){
						$ret = eval("return $class::parse(\$s, \$context);");
						if($ret != null){
							break;
						}
					}
					if($ret == null){
						T_Line::parse($s, $context, "(?:</$pluginname>|\n)");
						if(mb_ereg("^</$pluginname>[\t 　]*\n?", $s, $m)){
							$len = strlen($source) - strlen($s) + strlen($m[0]);
							$src .= _substr($source, 0, $len);
							$source = _substr($source, $len);
							$param2 = _substr($_s, 0, strlen($_s) - strlen($s));
							break;
						}
					}
				}
			}
			
			return new T_BlockTag($src, $context, $pluginname, $param1, $param2);
		}
		return null;
	}
}



/**
 * コメント文を表す。
 */
class T_Comment extends T_Block
{
	public static function parse(&$source, $context)
	{
		if(mb_ereg('^//.*?(?:\n|$)', $source, $m)){
			$source = _substr($source, strlen($m[0]));
			return new T_Comment($m[0], $context);
		}
		return null;
	}
}



/**
 * 意味段落を表す。
 */
class T_Paragraph extends T_Block
{
	public static function parse(&$source, $context)
	{
		static $classlist = array('T_Empty', 'T_Block');
		
		$_source = $source;
		
		$line = array();
		while($source != ''){
			foreach($classlist as $class){
				$ret = eval("return $class::parse(\$source, \$context);");
				if($ret != null){
					$ret->undo($source);
					break 2;
				}
			}
			mb_ereg('^.*?(?:\n|$)', $source, $m);
			$source = _substr($source, strlen($m[0]));
			$str = mb_ereg_replace('^　', '', $m[0]);
			$line[] = T_Line::parse($str, $context);
		}
		return new T_Paragraph(_substr($_source, 0, strlen($_source) - strlen($source)), $context, $line);
	}
	
	
	public function __construct($source, $context, $lines)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($lines as $l){
			$this->addelement($l);
		}
	}
}



/**
 * インライン要素の集合を表す。
 */
class T_Line extends T_InlineElement
{
	public static function parse(&$str, $context, $terminator = '')
	{
		static $classlist = array('T_URL', 'T_Mail', 'T_BlacketName', 'T_InlinePlugin', 'T_InlineTag', 'T_Footnote', 'T_Strong');
		
		$backup = $str;
		$elements = array();
		while($str != ''){
			$len = strlen($str);
			foreach($classlist as $class){
				$ret = eval("return $class::getdistance(\$str, \$context);");
				if($ret == 0){
					$elements[] = eval("return $class::parse(\$str, \$context);");
					$len = 0;
					break;
				}
				if($ret < $len){
					$len = $ret;
				}
			}
			if($len > 0){
				$text = _substr($str, 0, $len);
				if($terminator != '' && mb_ereg("^(.*?)$terminator", $text, $m)){
					$str = _substr($str, strlen($m[1]));
					$elements[] = T_Text::parse($m[1], $context);
					break;
				}
				else{
					$str = _substr($str, $len);
					$elements[] = T_Text::parse($text, $context);
				}
			}
		}
		return new T_Line(_substr($backup, 0, strlen($backup) - strlen($str)), $context, $elements);
	}
	
	
	public function __construct($source, $context, $elements)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($elements as $elem){
			$this->addelement($elem);
		}
	}
}



/**
 * URLを表す。
 */
class T_URL extends T_InlineElement
{
	function geturl(){ return $this->source; }
	
	
	public static function getdistance(&$str, $context)
	{
		if(mb_ereg('^(.*?)' . EXP_URL, $str, $m)){
			return strlen($m[1]);
		}
		else{
			return strlen($str);
		}
	}
	
	
	public static function parse(&$str, $context)
	{
		if(mb_ereg('^' . EXP_URL, $str, $m)){
			$str = _substr($str, strlen($m[0]));
			return new T_URL($m[0], $context);
		}
		return null;
	}
}



/**
 * メールアドレスを表す。
 */
class T_Mail extends T_InlineElement
{
	function getaddress(){ return $this->source; }
	
	
	public static function getdistance(&$str, $context)
	{
		if(mb_ereg('^(.*?)' . EXP_MAIL, $str, $m)){
			return strlen($m[1]);
		}
		else{
			return strlen($str);
		}
	}
	
	
	public static function parse(&$str, $context)
	{
		if(mb_ereg('^' . EXP_MAIL, $str, $m)){
			$str = _substr($str, strlen($m[0]));
			return new T_Mail($m[0], $context);
		}
		return null;
	}
}



/**
 * BlacketNameを表す。
 */
class T_BlacketName extends T_InlineElement
{
	protected $pagename;
	protected $alias;
	
	function getpagename(){ return $this->pagename; }
	function getalias(){ return $this->alias; }
	
	
	public static function getdistance(&$str, $context)
	{
		if(mb_ereg('^(.*?)\[\[.+?\]\]', $str, $m)){
			return strlen($m[1]);
		}
		else{
			return strlen($str);
		}
	}
	
	
	public static function parse(&$str, $context)
	{
		if(mb_ereg('^\[\[(.+?)\]\]', $str, $m)){
			$str = _substr($str, strlen($m[0]));
			if(mb_ereg('(.+?)>(.+)', $m[1], $match)){
				return new T_BlacketName($m[0], $context, $match[2], $match[1]);
			}
			else{
				return new T_BlacketName($m[0], $context, $m[1], '');
			}
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $pagename, $alias)
	{
		$this->source = $source;
		$this->context = $context;
		$this->pagename = $pagename;
		$this->alias = $alias;
	}
}



/**
 * インライン型プラグインを表す。
 */
class T_InlinePlugin extends T_InlineElement
{
	protected $pluginname;
	protected $param1;
	protected $param2;
	
	function getpluginname(){ return $this->pluginname; }
	function getparam1(){ return $this->param1; }
	function getparam2(){ return $this->param2; }
	
	
	public static function getdistance(&$str, $context)
	{
		if(mb_ereg('^(.*?)&[a-zA-Z0-9_]+[\t ]*\(.*?\)', $str, $m)){
			return strlen($m[1]);
		}
		else{
			return strlen($str);
		}
	}
	
	
	public static function parse(&$str, $context)
	{
		if(mb_ereg('^&([a-zA-Z0-9_]+)[\t ]*\((.*?)\)', $str, $m)){
			$pluginname = $m[1];
			$param1 = $m[2];
			$src = $m[0];
			$str = _substr($str, strlen($src));
			
			$param2 = '';
			if(mb_ereg('^[\t 　]*{', $str, $m)){
				$s = $_s = _substr($str, strlen($m[0]));
				T_Line::parse($s, $context, '}');
				if(mb_ereg('^}', $s, $m)){
					$len = strlen($str) - strlen($s) + strlen($m[0]);
					$src .= _substr($str, 0, $len);
					$str = _substr($str, $len);
					$param2 = _substr($_s, 0, strlen($_s) - strlen($s));
				}
			}
			
			return new T_InlinePlugin($src, $context, $pluginname, $param1, $param2);
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $pluginname, $param1, $param2)
	{
		$this->source = $source;
		$this->context = $context;
		$this->pluginname = $pluginname;
		$this->param1 = $param1;
		$this->param2 = $param2;
	}
}



/**
 * タグ型インラインプラグインを表す。
 */
class T_InlineTag extends T_InlinePlugin
{
	public static function getdistance(&$str, $context)
	{
		$src = $str;
		while(mb_ereg('^(.*?)<[a-zA-Z0-9_]+(?:[\t 　]+.*?)?[\t 　]*?/?>', $src, $m)){
			$src = _substr($src, strlen($m[1]));
			$ret = self::parse($src, $context);
			if($ret != null){
				$ret->undo($src);
				return strlen($str) - strlen($src);
			}
			$src = _substr($src, strlen($m[0]));
		}
		return strlen($str);
	}
	
	
	public static function parse(&$str, $context)
	{
		if(mb_ereg('^<([a-zA-Z0-9_]+)(?:[\t 　]+(.*?))?[\t 　]*(/?)>', $str, $m)){
			$pluginname = $m[1];
			$param1 = $m[2];
			$tag = $m[0];
			
			if($m[3] != '/'){
				if(!mb_ereg("</$pluginname>", $str)){	//閉じタグがない場合をショートカット
					return null;
				}
				$s = $_s = _substr($str, strlen($tag));
				T_Line::parse($s, $context, "(?:</$pluginname>|\n)");
				if(mb_ereg("^</$pluginname>", $s, $m)){
					$len = strlen($str) - strlen($s) + strlen($m[0]);
					$src = _substr($str, 0, $len);
					$str = _substr($str, $len);
					$param2 = _substr($_s, 0, strlen($_s) - strlen($s));
					return new T_InlineTag($src, $context, $pluginname, $param1, $param2);
				}
				return null;
			}
			return new T_InlineTag($tag, $context, $pluginname, $param1, '');
		}
		return null;
	}
}



/**
 * 脚注を表す。
 */
class T_Footnote extends T_InlineElement
{
	public static function getdistance(&$str, $context)
	{
		$src = $str;
		while(mb_ereg('^(.*?)\(\(|（（', $src, $m)){
			$src = _substr($src, strlen($m[1]));
			$ret = T_Footnote::parse($src, $context);
			if($ret != null){
				$ret->undo($src);
				return strlen($str) - strlen($src);
			}
			$src = _substr($src, strlen($m[0]));
		}
		return strlen($str);
	}
	
	
	public static function parse(&$str, $context)
	{
		if(mb_ereg('^(?:\(\(|（（)', $str, $m)){
			$src = $str;
			$rdelim = $m[0] == '((' ? '\)\)' : '））';
			$str = _substr($str, strlen($m[0]));
			$ret = T_Line::parse($str, $context, $rdelim);
			if(mb_ereg("^{$rdelim}", $str, $m)){
				$str = _substr($str, strlen($m[0]));
				$src = _substr($src, 0, strlen($src) - strlen($str));
				return new T_Footnote($src, $context, $ret);
			}
			$str = $src;
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $line)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($line);
	}
}



/**
 * 強調語を表す。
 */
class T_Strong extends T_InlineElement
{
	protected $str;
	protected $level;
	
	function getstr(){ return $this->str; }
	function getlevel(){ return $this->level; }
	
	
	public static function getdistance(&$str, $context)
	{
		if(mb_ereg('^(.*?)[\t 　]?(\*\*?)[^\t 　]+?\2(?:[\t 　]|$|(?=\n))', $str, $m)){
			return strlen($m[1]);
		}
		else{
			return strlen($str);
		}
	}
	
	
	public static function parse(&$str, $context)
	{
		if(mb_ereg('^[\t 　]?(\*\*?)([^\t 　]+?)\1(?:[\t 　]|$|(?=\n))', $str, $m)){
			$str = _substr($str, strlen($m[0]));
			return new T_Strong($m[0], $context, T_String::parse($m[2], $context), strlen($m[1]));
		}
		return null;
	}
	
	
	protected function __construct($source, $context, $str, $level)
	{
		$this->source = $source;
		$this->context = $context;
		$this->addelement($str);
		$this->level = $level;
	}
}



/**
 * 平文を表す。
 */
class T_Text extends T_InlineElement
{
	public static function getdistance(&$str, $context)
	{
		return 0;
	}
	
	
	public static function parse(&$str, $context)
	{
		$src = $str;
		$elements = array();
		while($str != ''){
			$i = T_AutoLink::getdistance($str, $context);
			if($i == 0){
				$elements[] = T_AutoLink::parse($str, $context);
				continue;
			}
			
			$k = T_FuzzyLink::getdistance($str, $context);
			if($k == 0){
				$elements[] = T_FuzzyLink::parse($str, $context);
				continue;
			}
			
			$n = min($i, $k);
			$s = _substr($str, 0, $n);
			$elements[] = T_String::parse($s, $context);
			$str = _substr($str, $n);
		}
		return new T_Text($src, $context, $elements);
	}
	
	
	protected function __construct($source, $context, $elements)
	{
		$this->source = $source;
		$this->context = $context;
		foreach($elements as $e){
			$this->addelement($e);
		}
	}
}



/**
 * 文字列を表す。
 */
class T_String extends T_InlineElement
{
	function getstring(){ return $this->source; }
	
	
	public static function getdistance(&$str, $context)
	{
		return 0;
	}
	
	
	public static function parse(&$str, $context)
	{
		$ins = new T_String($str, $context);
		$str = '';
		return $ins;
	}
}



/**
 * オートリンクされたページを表す。
 */
class T_AutoLink extends T_InlineElement
{
	protected $pagename;
	
	function getpagename(){ return $this->pagename; }
	function getalias(){ return $this->source; }
	
	
	public static function getdistance(&$str, $context)
	{
		$list[] = $context->pagename;
		$list[] = getdirname($context->pagename);
		$list[] = '';
		
		$ret = strlen($str);
		foreach($list as $dir){
			$exp = AutoLink::getinstance()->getexpression($dir);
			if($exp != '' && mb_ereg("^(.*?)$exp", $str, $m)){
				$ret = min($ret, strlen($m[1]));
			}
		}
		return $ret;
	}
	
	
	public static function parse(&$str, $context)
	{
		$list[] = $context->pagename;
		$list[] = getdirname($context->pagename);
		$list[] = '';
		
		foreach($list as $dir){
			$exp = AutoLink::getinstance()->getexpression($dir);
			if($exp != '' && mb_ereg("^$exp", $str, $m)){
				$str = _substr($str, strlen($m[0]));
				return new T_AutoLink($m[0], $context, $dir . '/' . $m[0]);
			}
		}
		return null;
	}
	
	
	protected function __construct($alias, $context, $pagename)
	{
		$this->source = $alias;
		$this->context = $context;
		$this->pagename = resolvepath($pagename);
	}
}



/**
 * あいまいリンクされたページを表す。
 */
class T_FuzzyLink extends T_InlineElement
{
	function getkey(){ return $this->source; }
	
	
	public static function getdistance(&$str, $context)
	{
		$ret = strlen($str);
		$exp = FuzzyLink::getinstance()->getexpression();
		if($exp != '' && mb_ereg("^(.*?)$exp", $str, $m)){
			$ret = min($ret, strlen($m[1]));
		}
		return $ret;
	}
	
	
	public static function parse(&$str, $context)
	{
		$exp = FuzzyLink::getinstance()->getexpression();
		if($exp != '' && mb_ereg("^$exp", $str, $m)){
			$str = _substr($str, strlen($m[0]));
			return new T_FuzzyLink($m[0], $context);
		}
		return null;
	}
}




?>