<?php
/* 
 * BitWiki
 * 
 * Lightweight PHP and SQLite Wiki Engine
 *
 * BitWiki is based on kinowiki.inc.php,v 1.6 2005/09/06 01:14:55
 *
 * @package   BitWiki
 * @author    youka
 * @author    kaz <kaz6120@gmail.com>
 * @copyright 2009 BitWiki Project
 * @link      http://github.com/kaz6120/BitWiki/tree/master
 * @license   http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @since     5.9.6
 * @version   9.8.18 
 */

/**
 * Fetch current time
 * 
 * @return double current time
 */
function mtime()
{
    $t = gettimeofday();
    return (double)($t['sec'].'.'.sprintf("%06d", $t['usec']));
}

// Set start time
define('STARTTIME', mtime());

ini_set('include_path', 'library/' . PATH_SEPARATOR . ini_get('include_path'));
ini_set('include_path', 'library/pear/' . PATH_SEPARATOR . ini_get('include_path'));

// Classes
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Exception.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/DataBase.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Notifier.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Attach.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/AutoLink.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/BackLink.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Controller.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Command.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/DiffRenderer.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/FuzzyFunc.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/FuzzyLink.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/HtmlConverter.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Mail.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Page.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Plugin.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Renderer.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Search.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/MySmarty.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Vars.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/Context.php';

// Functions
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/itaimoji.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/functions.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/entityReferences.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'BitWiki/version.php';


class BitWiki
{
    /**
     * 
     * @var    Page
     */
    protected $page;
    
    /**
     *
     * @var    Controller
     */
    protected $controller;
    
    /**
     * 
     * @return Page
     */
    function getPage() { return $this->page; }
    
    /**
     * 
     * @param Controller
     */
    function getController() { return $this->controller; }

    
    /**
     * 
     * @return BitWiki instance
     */
    static function getInstance()
    {
        static $ins;
        
        if (empty($ins)) {
            $ins = new self();
        }
        return $ins;
    }
    
    
    /**
     * Launch application
     */
    static function main()
    {
        try {
            self::init();
            $ins = self::getInstance();
            $ins->run();
        } catch (FatalException $exc) {
            saveexceptiondump($exc);
            echo $exc->getMessage();
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }
    
    
    /**
     * Initialize objects except BitWiki class
     */
    static function init()
    {
        // set internal encodings.
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        
        // set SCRIPTURL
        if ($_SERVER['SERVER_PORT'] == 443) {
            $protocol = 'https';
            $port = '';
        } else {
            $protocol = 'http';
            $port = $_SERVER['SERVER_PORT'] != 80 ? ":{$_SERVER['SERVER_PORT']}" : '';
        }
        define('SCRIPTDIR', 
               $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . 
               mb_substr($_SERVER['SCRIPT_NAME'], 0, mb_strrpos($_SERVER['SCRIPT_NAME'], '/')+1)
              );
        define('SCRIPTURL', 
               $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['SCRIPT_NAME']
              );
        // check if table is already prepared and create when not exists.
        $isinstalled = self::installcheck();
        
        // insntaciate classes
        Vars::init();
        AutoLink::init();
        BackLink::init();
        FuzzyLink::init();
        Mail::init();
        
        // install initialize page when table is not prepared.
        if (!$isinstalled) {
            self::installpage();
        }
    }    
    
    /**
     * Constructor 
     */
    public function __construct()
    {
        // settings of the page being excuted.
        if (empty(Vars::$get['plugin']) && (empty(Vars::$get['cmd']) || mb_strtolower(Vars::$get['cmd']) == 'show'))
        {
            if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != '') {
                $this->page = Page::getInstance(rawurldecode($_SERVER['PATH_INFO']));
            } else if (isset(Vars::$get['page']) && Vars::$get['page'] != '') {
                $this->page = Page::getInstance(Vars::$get['page']);
            } else if (isset(Vars::$get['n']) && Vars::$get['n'] != '') {
                $this->page = Page::getinstancebynum(Vars::$get['n']);
            } else if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
                $this->page = Page::getInstance(rawurldecode($_SERVER['QUERY_STRING']));
            } else {
                $this->page = Page::getInstance(DEFAULTPAGE);
            }
        } else {
            $this->page = Page::getInstance('');
        }
        
        // get a controller to execute
        if (isset(Vars::$get['cmd']) && Vars::$get['cmd'] != '') {
            $this->controller = Command::getCommand(Vars::$get['cmd']);
        } else if (isset(Vars::$get['plugin']) && Vars::$get['plugin'] != '') {
            $this->controller = Plugin::getPlugin(Vars::$get['plugin']);
        } else {
            $this->controller = Command::getCommand('show');
        }

    }
    
    
    /**
     * Run application 
     */
    public function run()
    {
        try {
            // Pre settings before run controller
            foreach(Command::getCommands() as $cmd) {
                $cmd->doing();
            }
            foreach(Plugin::getPlugins() as $plugin) {
                $plugin->doing();
            }
            
            // Run the controller
            $ret = $this->controller->run();
            
            // After run the controller
            foreach(Command::getCommands() as $cmd) {
                $cmd->done();
            }
            foreach(Plugin::getPlugins() as $plugin) {
                $plugin->done();
            }
            
            // Rendering
            Renderer::getInstance()->render($ret);
        } catch(MyException $exc) {
            $text['title'] = 'error';
            $text['body'] = $exc->getMessage();
            Renderer::getInstance()->render($text);
        }
    }
    
    
    /**
     * Check if database table is intalled
     * Returns "true" if it is installed.
     *
     * @return bool
     */
    private static function installcheck()
    {
        $db = DataBase::getInstance();
        if ($db->istable('purepage')) {
            return true;
        } else {
            $db->exec(file_get_contents(HIDEABLE_DIR . 'sql/bitwiki.sql'));
            $dir = opendir(HIDEABLE_DIR . '/sql');
            while(($filename = readdir($dir)) !== false) {
                $path = HIDEABLE_DIR . '/sql/' . $filename;
                if (!is_file($path) || $filename == 'bitwiki.sql') {
                    continue;
                }
                $db->exec(file_get_contents($path));
            }
            return false;
        }
    }
    
    
    /**
     * Install default page into database
     */
    private static function installpage()
    {
        $dir = opendir(HIDEABLE_DIR . '/installpage');
        while(($filename = readdir($dir)) !== false) {
            $path = HIDEABLE_DIR . '/installpage/' . $filename;
            if (!is_file($path) || !preg_match('/^(.+)\.txt$/', $filename, $m)) {
                continue;
            }
            $page = Page::getInstance(rawurldecode($m[1]));
            $page->write(file_get_contents($path));
        }
    }
}

