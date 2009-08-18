<?php
/**
 * Plugin
 *
 * based on plugin.inc.php,v 1.6 2005/09/06 01:14:55 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.9.6
 * @version 9.8.18
 */


/**
 * All plugins must extend this class.
 */
class Plugin extends Controller 
{
    /**
     * Retain all plugins
     *
     * @var array(Plugin)
     */
    private static $plugins = array();
    
    
    protected function __construct()
    {
        parent::__construct();
    }
    
    
    /**
     * Initialize all plugins 
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
     * Get instances of plugins.
     * 
     * @param    string    $pluginname
     * @return   Plugin  
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
     * Get all plugin instances.
     * 
     * @return    array(Plugin)
     */
    static final function getPlugins()
    {
        self::initPlugins();
        return self::$plugins;
    }
    
    
    /**
     * Get smarty instance
     * @return    MySmarty 
     */
    protected function getSmarty()
    {
        $smarty = new MySmarty(PLUGIN_DIR . substr(get_class($this), 7) . '/');
        $smarty->compile_id = get_class($this);
        return $smarty;
    }
    
    
    /**
     * Execute as a block plugin
     * 
     * @param    Page    $page     Page which plugin is executed.
     * @param    string  $param1   Strings in ().
     * @param    string  $param2   Strings in {}.
     * @return   string  html
     */
    public function do_block($page, $param1, $param2)
    {
        throw new PluginException('このプラグインはブロック型ではありません。', $this);
    }
    
    
    /**
     * Execute as an inline plugin
     * 
     * @param    Page    $page     Page which plugin is executed.
     * @param    string  $param1   Strings in ().
     * @param    string  $param2   Strings in {}. 
     * @return   string  html
     */
    public function do_inline($page, $param1, $param2)
    {
        throw new PluginException('このプラグインはインライン型ではありません。', $this);
    }
    
    
    /**
     * Execute as a tag plugin 
     * 
     * @param    Page    $page     Page which plugin is executed.
     * @param    string  $param1   Strings in ().
     * @param    string  $param2   Strings in {}. 
     * @return   string  html
     */
    public function do_blocktag($page, $param1, $param2)
    {
        return $this->do_block($page, $param1, $param2);
    }
    
    
    /**
     * Execute as a tag-style inline plugin    
     *
     * @param    Page    $page     Page which plugin is executed.  
     * @param    string  $param1   Strings in ().
     * @param    string  $param2   Strings in {}. 
     * @return   string  html
     */
    public function do_inlinetag($page, $param1, $param2)
    {
        return $this->do_inline($page, $param1, $param2);
    }
}



/**
 * Plugin Exception class 
 */
class PluginException extends MyException 
{
    /**
     * Constructor 
     *
     * @param string    $mes
     * @param mixed     $pluginname
     */
    public function __construct($mes = '', $plugin)
    {
        $pluginname = is_string($plugin) ? $plugin : substr(get_class($plugin), 7);
        parent::__construct($mes . "($pluginname)");
    }
}



/**
 * Exception when try to fetch plugin which is not exist.
 */
class NoExistPluginException extends MyException 
{
    /**
     * Constructor 
     *
     * @param string    $plugin
     */
    public function __construct($plugin)
    {
        parent::__construct("プラグインがありません($plugin)。");
    }
}
