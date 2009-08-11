<?php
/* 
 * $Id: command.inc.php,v 1.3 2005/09/06 01:14:55 youka Exp $
 *
 * @version 9.8.11
 */


/**
 * コマンドはこのクラスから派生させる。
 * 派生させたコマンドはシングルトンにする。
 */
class Command extends Controller 
{
    private static $commands = array();

    
    function __construct()
    {
        parent::__construct();
    }
    
    
    /**
     * すべてのコマンドを初期化する。
     */
    protected static final function initCommands()
    {
        if (self::$commands == array()) {
            foreach(scandir(COMMAND_DIR) as $name) {
                if ($name != '.' && $name != '..' && $name != 'CVS' && is_dir(COMMAND_DIR . $name)) {
                    $commandname = mb_strtolower($name);
                    $file = COMMAND_DIR . $commandname . '/' . $commandname . '.inc.php';
                    if (!is_file($file)) {
                        throw new FatalException("コマンド用ファイルがありません。($commandname)");
                    }
                    require_once($file);
                    self::$commands[$commandname] =  eval("return new Command_${commandname};");
                }
            }
            foreach(self::$commands as $command) {
                $command->init();
            }
        }
    }
    
    
    /**
     * コマンドのインスタンスを取得する。
     * 
     * @param    string    $cmdname    コマンドの名前
     * @return    Command    コマンドのインスタンス。コマンドがない場合はNoExistCommandExceptionを投げる。
     */
    static final function getCommand($cmdname)
    {
        self::initCommands();
        
        $cmdname = mb_strtolower($cmdname);
        if (isset(self::$commands[$cmdname])) {
            return self::$commands[$cmdname];
        } else {
            throw new NoExistCommandException($cmdname);
        }
    }
    
    
    /**
     * コマンドのインスタンスをすべて取得する。
     * 
     * @return    array(Command)    コマンドのインスタンス。
     */
    static final function getCommands()
    {
        self::initCommands();
        return self::$commands;
    }
    
    
    /**
     * コマンド用のSmartyインスタンスを取得する。
     * @return    MySmarty    Smartyのインスタンス。
     */
    protected function getSmarty()
    {
        $smarty = new MySmarty(COMMAND_DIR . substr(get_class($this), 8) . '/');
        $smarty->compile_id = get_class($this);
        return $smarty;
    }
}



/**
 * コマンド関連の例外クラス。
 */
class CommandException extends MyException 
{
    /**
     * コンストラクタ。
     *
     * @param string    $mes    エラーメッセージ
     * @param mixed    $cmd    コマンド名またはCommand
     */
    public function __construct($mes = '', $cmd)
    {
        $cmdname = is_subclass_of($cmd, 'Command') ? substr(get_class($cmd), 8) : $cmd;
        parent::__construct($mes . "($cmdname)");
    }
}



/**
 * 存在しないコマンドを取得しようとした場合の例外クラス。
 */
class NoExistCommandException extends MyException 
{
    /**
     * コンストラクタ。
     *
     * @param string    $cmd    コマンド名
     */
    public function __construct($cmd)
    {
        parent::__construct("コマンドがありません($cmd)。");
    }
}
