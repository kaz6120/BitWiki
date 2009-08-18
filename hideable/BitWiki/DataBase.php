<?php
/*
 * DataBase class
 * 
 * based on database.inc.php,v 1.5 2005/12/06 09:18:22 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.12.6
 * @version 9.8.18
 */

function _sqlite_php_function()
{
    $arg_list = func_get_args();
    $method_name = array_shift($arg_list);
    return call_user_func_array($method_name, $arg_list);
}

/**
 * Database management class. Behave like singleton (using static method)
 *
 */
class DataBase
{
    //protected $link;             // Link to database
    public static $link;
    protected $transaction = 0;  // Number of transaction nest
    
    
    /**
     * Instantiate database
     *
     * @return  object
     */
    static function getInstance()
    {
        static $ins;
        
        if (empty($ins)) {
            $ins = new self;
        }
        return $ins;
    }
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $file = WIKIID . '.db';
        try {
            $this->link = new PDO('sqlite:' . DATA_DIR . $file, null, null, array(PDO::ATTR_TIMEOUT => 5000));
            $this->create_function('php', '_sqlite_php_function');
        } catch (PDOException $error) {
            clearstatcache();
            if (is_writable(DATA_DIR) == false) {
                throw new FatalException('DATA_DIR is not writable.', $error);
            } else if (is_writable(DATA_DIR . $file) == false) {
                throw new FatalException('DB file is not writable.', $error);
            } else {
                throw new FatalException('Coud not open DB file.', $error);
            }
        }
    }
    
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->transaction > 0) {
            $this->rollBack();
        }
        if ($this->link != null) {
            $this->link = null;
        }
    }
    
    
    /**
     * Execute query 
     * 
     * @param   string $query
     * @return  string $result
     */
    public function query($query)
    {
        $result = $this->link->query($query);
        if ($result === false) {
            throw new DBException('クエリを実行できませんでした。', $query, $this->link);
            //throw new Exception();
        }
        return $result;
    }
    
    
    /**
     * Excecute query returns void 
     *
     * @param   string $query  
     * @return  void
     */
    public function exec($query)
    {
        $result = $this->link->exec($query);
        if ($result === false) {
            throw new DBException('クエリを実行できませんでした。', $query, $this->link);
        }
    }
    
    
    /**
     * クエリパラメータ用に文字列をエスケープする。
     * 
     * @param    string    $str    エスケープしたい文字列。
     * @return    string    エスケープした文字列。
     */
    public function escape($str)
    {
        //空文字列をsqlite_escape_string()に渡すと謎の3バイトが帰ってくる(PHP5.0.0RC2以下)。
        //    http://bugs.php.net/bug.php?id=29339
        //    http://bugs.php.net/bug.php?id=29395
        if ($str == '') {
            return '';
        }
        return substr($this->link->quote($str), 1, -1);
        //return $this->link->quote($str);
    }
    
    
    /**
     * 直前のクエリにより変更されたレコード数を返す。
     * 
     * @return    int
     */
    public function changes()
    {
        //return sqlite_changes($this->link);
        return $this->link;
    }
    
    
    /**
     * "BEGIN TRANSACTION"を発行する。
     */
    public function begin()
    {
        if ($this->transaction == 0) {
            $this->link->beginTransaction();
        }
        $this->transaction++;
    }
    
    
    /**
     * "COMMIT"を発行する。
     */
    public function commit()
    {
        $this->transaction--;
        if ($this->transaction == 0) {
            $this->link->commit();
        }
    }
    
    
    /**
     * そのテーブルが存在するかを確認する。
     * 
     * @param    string    $table    テーブル名
     */
    public function istable($table)
    {
        $_table = $this->escape($table);
        $query = 'SELECT '
               .     'name '
               . 'FROM '
               .     '(SELECT name FROM sqlite_master '
               .           "WHERE type='table' UNION ALL "
               .      "SELECT name FROM sqlite_temp_master "
               .           "WHERE type='table') "
               . "WHERE name = '$_table'";
        return $this->fetch($this->query($query)) !== false;
    }
    
    
    /**
     * ユーザ関数を登録する（sqlite_create_function()ラッパー）。
     */
    public function create_function($function_name, $callback, $num_args = null)
    {
        if ($num_args === null) {
            return $this->link->sqliteCreateFunction($function_name, $callback);
        } else {
            return $this->link->sqliteCreateFunction($function_name, $callback, $num_args);
        }
    }
    
    
    /**
     * 集約UDFを登録する（sqlite_create_aggregate()ラッパー）。
     */
    public function create_aggregate($function_name, $step_func, $finalize_func, $num_args = null)
    {
        if ($num_args === null) {
            return $this->link->sqliteCreateAggregate($function_name, $step_func, $finalize_func);
        } else {
            return $this->link->sqliteCreateAggregate($function_name, $step_func, $finalize_func, $num_args);
        }
    }
    
    
    /**
     * レコードを取得する。
     * 
     * @param Resource    $result    クエリの結果セット。
     * @return    mixed    レコードデータを含む連想配列を返す。レコードが無い場合はfalseを返す。
     */
    public function fetch($result)
    {
        $ret = $result->fetch();
        if (get_magic_quotes_runtime()) {
            return array_map('stripslashes', $ret);
        }
        return $ret;
    }

    
    /**
     * レコードをすべて取得する。
     * 
     * @param Resource    $result    クエリの結果セット。
     * @return    array(array(mixed))
     */
    public function fetchall($result)
    {
        $ret = $result->fetchAll();
        if (get_magic_quotes_runtime()) {
            return map('stripslashes', $ret);
        }
        return $ret;
    }
    
    
    /**
     * レコードの先頭１カラム目をすべて取得する。
     *
     * @param Resource    $result    クエリの結果セット。
     * @return    array(mixed)
     */
    public function fetchsinglearray($result)
    {
        $ret = array();
        while(($str = $result->fetchColumn()) !== false) {
            $ret[] = $str;
        }
        
        if (get_magic_quotes_runtime()) {
            return array_map('stripslashes', $ret);
        }
        return $ret;
    }
}


/**
 * SQLite関連の例外クラス。
 */
class DBException extends FatalException
{
    public function __construct($mes = '', $hiddenmes = '', $dblink)
    {
        clearstatcache();

        if (is_writable(DATA_DIR) == false) {
            $mes = 'DATA_DIR is not writable. Please change DATA_DIR permission writable.' . $mes;
        } else if (is_writable(DATA_DIR . WIKIID . '.db') == false) {
            $mes = 'Database file is not writable. Please change database permssion writable.' . $mes;
        }
        
        $error_infos = $dblink->errorInfo();
        parent::__construct($mes, linetrim($hiddenmes . "\n") . "\n" . $error_infos[2]);
    }
}
