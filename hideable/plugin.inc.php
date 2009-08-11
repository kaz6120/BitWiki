<?php
/* 
 * $Id: plugin.inc.php,v 1.6 2005/09/06 01:14:55 youka Exp $
 *
 * @version 9.8.11
 */


/**
 * プラグインはこのクラスから派生させる。
 * 派生させたプラグインはシングルトンにする。
 */
class Plugin extends Controller 
{
    /**
     * すべてのプラグインを保持する。
     * @var    array(Plugin)
     */
    private static $plugins = array();
    
    
    protected function __construct()
    {
        parent::__construct();
    }
    
    
    /**
     * すべてのプラグインを初期化する。
     */
    protected static final function initPlugins()
    {
        if(self::$plugins == array()){
            foreach(scandir(PLUGIN_DIR) as $name){
                if($name != '.' && $name != '..' && $name != 'CVS' && is_dir(PLUGIN_DIR . $name)){
                    $pluginname = mb_strtolower($name);
                    $file = PLUGIN_DIR . $pluginname . '/' . $pluginname . '.inc.php';
                    if(!is_file($file)){
                        throw new FatalException("プラグイン用ファイルがありません。($pluginname)");
                    }
                    require_once($file);
                    self::$plugins[$pluginname] =  eval("return new Plugin_${pluginname};");
                }
            }
            foreach(self::$plugins as $plugin){
                $plugin->init();
            }
        }
    }
    
    
    /**
     * プラグインのインスタンスを取得する。
     * 
     * @param    string    $pluginname    プラグインの名前
     * @return    Plugin    プラグインのインスタンス。プラグインがない場合はPluginExceptionを投げる。
     */
    static final function getPlugin($pluginname)
    {
        self::initPlugins();
        
        $pluginname = mb_strtolower($pluginname);
        if(isset(self::$plugins[$pluginname])){
            return self::$plugins[$pluginname];
        }
        else{
            throw new NoExistPluginException($pluginname);
        }
    }
    
    
    /**
     * プラグインのインスタンスをすべて取得する。
     * 
     * @return    array(Plugin)    プラグインのインスタンス。
     */
    static final function getPlugins()
    {
        self::initPlugins();
        return self::$plugins;
    }
    
    
    /**
     * プラグイン用のSmartyインスタンスを取得する。
     * @return    MySmarty    Smartyのインスタンス。
     */
    protected function getSmarty()
    {
        $smarty = new MySmarty(PLUGIN_DIR . substr(get_class($this), 7) . '/');
        $smarty->compile_id = get_class($this);
        return $smarty;
    }
    
    
    /**
     * ブロックプラグインとして動作させる。
     * 
     * @param    Page    $page    プラグインが実行されているページ。
     * @param    string    $param1    ソースで()に囲まれた文字列。
     * @param    string    $param2    ソースで{}に囲まれた文字列。
     * @return    string    html形式。プラグインが無い場合はPluginExceptionを投げる。
     */
    function do_block($page, $param1, $param2)
    {
        throw new PluginException('このプラグインはブロック型ではありません。', $this);
    }
    
    
    /**
     * インラインプラグインとして動作させる。
     * 
     * @param    Page    $page    プラグインが実行されているページ。
     * @param    string    $param1    ソースで()に囲まれた文字列。
     * @param    string    $param2    ソースで{}に囲まれた文字列。
     * @return    string    html形式。プラグインが無い場合はPluginExceptionを投げる。
     */
    function do_inline($page, $param1, $param2)
    {
        throw new PluginException('このプラグインはインライン型ではありません。', $this);
    }
    
    
    /**
     * タグ型ブロックプラグインとして動作させる。
     * 
     * @param    Page    $page    プラグインが実行されているページ。
     * @param    string    $param1    タグの引数文字列。
     * @param    string    $param2    タグに囲まれた文字列。
     * @return    string    html形式。プラグインが無い場合はPluginExceptionを投げる。
     */
    function do_blocktag($page, $param1, $param2)
    {
        return $this->do_block($page, $param1, $param2);
    }
    
    
    /**
     * タグ型インラインプラグインとして動作させる。
     * 
     * @param    Page    $page    プラグインが実行されているページ。
     * @param    string    $param1    タグの引数文字列。
     * @param    string    $param2    タグに囲まれた文字列。
     * @return    string    html形式。プラグインが無い場合はPluginExceptionを投げる。
     */
    function do_inlinetag($page, $param1, $param2)
    {
        return $this->do_inline($page, $param1, $param2);
    }
}



/**
 * プラグイン関連の例外クラス。
 */
class PluginException extends MyException 
{
    /**
     * コンストラクタ。
     *
     * @param string    $mes    エラーメッセージ
     * @param mixed        $pluginname    プラグイン名またはPlugin
     */
    public function __construct($mes = '', $plugin)
    {
        $pluginname = is_string($plugin) ? $plugin : substr(get_class($plugin), 7);
        parent::__construct($mes . "($pluginname)");
    }
}



/**
 * 存在しないプラグインを取得しようとした場合の例外クラス。
 */
class NoExistPluginException extends MyException 
{
    /**
     * コンストラクタ。
     *
     * @param string    $plugin    プラグイン名
     */
    public function __construct($plugin)
    {
        parent::__construct("プラグインがありません($plugin)。");
    }
}
