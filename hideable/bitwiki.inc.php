<?php
/* 
 * BitWiki
 * 
 * BitWiki is based on kinowiki.inc.php,v 1.6 2005/09/06 01:14:55
 *
 * @since   9.8.11
 * @version 9.8.11
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

//require_once('errorhandler.inc.php');
require_once('exception.inc.php');
require_once('func.inc.php');
require_once('database.inc.php');
require_once('notifier.inc.php');
require_once('attach.inc.php');
require_once('autolink.inc.php');
require_once('backlink.inc.php');
require_once('charentityref.inc.php');
require_once('controller.inc.php');
require_once('command.inc.php');
require_once('diff.inc.php');
require_once('fuzzyfunc.inc.php');
require_once('fuzzylink.inc.php');
require_once('htmlconverter.inc.php');
require_once('itaimoji.inc.php');
require_once('mail.inc.php');
require_once('page.inc.php');
require_once('parser.inc.php');
require_once('plugin.inc.php');
require_once('renderer.inc.php');
require_once('search.inc.php');
require_once('smarty.inc.php');
require_once('vars.inc.php');
require_once('version.inc.php');


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
    function __construct()
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
    function run()
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

