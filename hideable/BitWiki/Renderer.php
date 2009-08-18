<?php
/* 
 * Renderer
 *
 * based on renderer.inc.php,v 1.3 2005/07/13 09:48:51 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.7.13
 * @version 9.8.18
 */



/**
 * Page renderer class
 */
class Renderer
{
    /**
     * @var MySmarty
     */
    protected $smarty;

    /**
     * @ver theme
     */
    protected $theme = THEME;

    /**
     * @var array(string)
     */
    protected $option = array();

    /**
     * @var array(string)
     */
    protected $headeroption = array();

    
    /**
     * Retrieve theme to use
     */
    public function gettheme()
    {
        return $this->theme;
    }
    
    
    /**
     * Set theme to use 
     */
    public function settheme($theme)
    {
        $this->theme = $theme;
    }
    
    
    /**
     * Set optional output
     */
    public function setoption($name, $html)
    {
        $this->option[$name] = $html;
    }
    
    
    /**
     * Set optional header output
     */
    public function setheaderoption($name, $html)
    {
        $this->headeroption[$name] = $html;
    }
    
    
    /**
     * Get instance
     */
    static function getinstance()
    {
        static $ins;
        
        if (empty($ins)) {
            $ins = new self;
        }
        return $ins;
    }
    
    
    protected function __construct()
    {
        $this->smarty = new MySmarty(SKIN_DIR);
    }
    
    
    /**
     * Display page
     *
     * @param    array(string => string)    $value
     */
    public function render($value)
    {
        $command = array();
        foreach (Command::getCommands() as $c) {
            $html = $c->getbody();
            if ($html != '') {
                $command[substr(get_class($c), 8)] = $html;
            }
        }
        
        $plugin = array();
        foreach (Plugin::getPlugins() as $c) {
            $html = $c->getbody();
            if ($html != '') {
                $plugin[substr(get_class($c), 7)] = $html;
            }
        }
        
        $this->smarty->assign('command', $command);
        $this->smarty->assign('plugin', $plugin);
        $this->smarty->assign('option', $this->option);
        $this->smarty->assign('headeroption', $this->headeroption);
        $this->smarty->assign('theme', $this->theme);
        $this->smarty->assign($value);
        header('Content-Type: text/html; charset=UTF-8');
        $this->smarty->assign('runningtime', sprintf('%.3f', mtime() - STARTTIME));
        $this->smarty->display(SKINFILE);
    }
}

