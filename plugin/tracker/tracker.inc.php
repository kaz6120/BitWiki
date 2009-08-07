<?php
/* 
 * $Id: tracker.inc.php,v 1.1 2005/09/26 08:30:34 youka Exp $
 */



class Plugin_tracker extends Plugin 
{
	protected static $sqlite_pattern;

	
	function do_block($page, $param1, $param2)
	{
		$arg = array_map('trim', explode(',', $param1));
		$config = isset($arg[0]) && $arg[0] != '' ? $arg[0] : 'default';
		$configpagename = ':config/plugin/tracker/' . $config;
		$base = isset($arg[1]) && $arg[1] != '' ? $arg[1] : $page->getpagename();
		
		$data = self::Page2data(Page::getinstance($configpagename));
		if(!isset($data['field'])){
			return '<p class="warning">設定ページが正しくありません</p>';
		}
		
		foreach($data['field'] as $name => $type){
			try{
				$field[$name] = Plugin_tracker_field::getinstance($name, $type, $data)->gethtml();
			}
			catch(Exception $exc){
				throw new PluginException($exc->getMessage(), $this);
			}
		}
		
		$smarty = $this->getSmarty();
		foreach($data['form'] as $title => $data){
			$array['title'] = $title;
			$_data = htmlspecialchars($data[0]);
			foreach($field as $fieldname => $html){
				$_data = mb_ereg_replace('\[' . $fieldname . '\]', $html, $_data);
			}
			$array['data'] = $_data;
			$smarty->append('table', $array);
		}
		$smarty->assign('config', $config);
		$smarty->assign('base', $base);
		return $smarty->fetch('form.tpl.htm');
	}
	
	
	function do_url()
	{
		if(!keys_exists(Vars::$post, 'base', 'config')){
			throw new PluginException('パラメータが足りません', $this);
		}
		
		$source = Page::getinstance(':config/plugin/tracker/' . Vars::$post['config'] . '/page')->getsource();
		foreach(Vars::$post as $key => $data){
			if(mb_strpos($key, 'param_') === 0){
				$name = '[' . mb_substr($key, 6) . ']';
				$_data = trim($data);
				$_data = mb_ereg_replace('\[', '&#x5b;', $_data, 'm');
				$_data = mb_ereg_replace('\]', '&#x5d;', $_data, 'm');
				$replace[$name] = $_data;
			}
		}
		$replace['[_date]'] = date('Y-m-d H:i:s');
		$replace['[_base]'] = Vars::$post['base'];

		foreach($replace as $name => $data){
			$source = mb_ereg_replace(mb_ereg_quote($name), $data, $source, 'm');
		}
		
		$db = DataBase::getinstance();
		self::$sqlite_pattern = '^' . mb_ereg_quote(Vars::$post['base']) . '/(\d+)';
		$db->create_aggregate('plugin_tracker_maxnum', array('Plugin_tracker', 'sqlite_maxnum'), array('Plugin_tracker', 'sqlite_maxnum_finalize'), 1);
		$row = $db->fetch($db->query("SELECT plugin_tracker_maxnum(pagename) FROM page"));
		$num = $row[0] + 1;
		
		$title = isset(Vars::$post['param_title']) && trim(Vars::$post['param_title']) != '' ? '/' . trim(Vars::$post['param_title']) : '';
		$page = Page::getinstance(Vars::$post['base'] . '/' . $num . $title);
		$page->write($source);
		redirect($page);
	}
	
	
	static function sqlite_maxnum(&$context, $string)
	{
		if(mb_ereg(self::$sqlite_pattern, $string, $m)){
			if($m[1] > $context){
				$context = $m[1];
			}
		}
	}
	
	
	static function sqlite_maxnum_finalize(&$context)
	{
		return (int)$context;
	}
	
	

	/**
	 * ページの内容を元にデータを読み出す。
	 *
	 * @return	array(array(array(mixed)))	読み込んだデータ
	 */
	static function Page2data($page)
	{
		$ret = array();
		$name = '';
		foreach(explode("\n", $page->getsource()) as $line){
			if(mb_ereg('^[*＊]+[\t 　]*(.+)[\t 　]*$', $line, $m)){
				$name = $m[1];
			}
			else if(mb_ereg('^\|(.+)\|[\t 　]*$', $line, $m)){
				$array = array_map('trim', explode('|', $m[1]));
				$ret[$name][$array[0]] = array_slice($array, 1);
			}
		}
		return $ret;
	}
}




abstract class Plugin_tracker_field
{
	protected $name;
	protected $default;
	
	static function getinstance($name, $type, $data)
	{
		$class = 'Plugin_tracker_field_' . $type[0];
		if(!class_exists($class)){
			throw new MyException('形式指定が不正です');
		}
		return new $class($name, $type[1], $type[2], $data);
	}
	
	abstract function gethtml();
}



class Plugin_tracker_field_text extends Plugin_tracker_field 
{
	protected $size;
	
	function __construct($name, $option, $default, $data)
	{
		$this->name = $name;
		$this->default = $default;
		$this->size = (int)$option > 0 ? (int)$option : 30;
	}
	
	
	function gethtml()
	{
		$_name = htmlspecialchars($this->name);
		$_size = $this->size;
		$_default = htmlspecialchars($this->default);
		return "<input type=\"text\" name=\"param_{$_name}\" size=\"{$_size}\" value=\"{$_default}\">";
	}
}



class Plugin_tracker_field_textarea extends Plugin_tracker_field 
{
	protected $width;
	protected $height;
	
	function __construct($name, $option, $default, $data)
	{
		$this->name = $name;
		$this->default = $default;
		$arg = array_map('trim', explode(',', $option));
		$this->width = isset($arg[0]) && (int)$arg[0] > 0 ? (int)$arg[0] : 60;
		$this->height = isset($arg[1]) && (int)$arg[1] > 0 ? (int)$arg[1] : 5;
	}
	
	
	function gethtml()
	{
		$_name = htmlspecialchars($this->name);
		$_default = htmlspecialchars($this->default);
		$_width = $this->width;
		$_height = $this->height;
		return "<textarea name=\"param_{$_name}\" rows=\"{$_height}\" cols=\"{$_width}\">{$_default}</textarea>";
	}
}



class Plugin_tracker_field_select extends Plugin_tracker_field 
{
	protected $items;
	
	function __construct($name, $option, $default, $data)
	{
		$this->name = $name;
		$this->default = $default;
		$this->items = array_keys($data[$name]);
	}
	
	
	function gethtml()
	{
		$_name = htmlspecialchars($this->name);
		$_default = htmlspecialchars($this->default);
		$option = array();
		foreach($this->items as $val){
			if($val == $this->default){
				$option[] = '<option selected="selected">' . htmlspecialchars($val) . '</option>';
			}
			else{
				$option[] = '<option>' . htmlspecialchars($val) . '</option>';
			}
		}
		return "<select name=\"param_{$_name}\">" . join("\n", $option) . '</select>';
	}
}

?>