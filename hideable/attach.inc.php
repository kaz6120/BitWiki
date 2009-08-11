<?php
/* 
 * $Id: attach.inc.php,v 1.2 2005/06/27 18:24:27 youka Exp $
 *
 * @version 9.8.11
 */


/**
 * 添付ファイルを管理するクラス。
 * 
 * Pageごとにシングルトンのようにふるまう。
 */
class Attach
{
    protected $page;
    protected static $notifier;
    
    /** ページを取得する。 */
    function getpage(){ return $this->page; }
    
    static function attach($obj){ self::initNotifier(); self::$notifier->attach($obj); }
    static function detach($obj){ self::initNotifier(); self::$notifier->detach($obj); }
    protected function notify($arg = null){ self::$notifier->notify($this, $arg); }
    protected static function initNotifier()
    {
        if(empty(self::$notifier)){
            self::$notifier = new NotifierImpl();
        }
    }
    
    
    /**
     * インスタンスを取得する。
     * 
     * @param    Page    $page    添付されているページ。
     */
    static function getinstance($page)
    {
        self::initNotifier();
        return new self($page);
    }
    
    
    /**
     * コンストラクタ。
     */
    protected function __construct($page)
    {
        $this->page = $page;
    }
    
    
    /**
     * ページに添付されているファイルを列挙する。
     * 
     * @return    array(string)    ファイル名の配列。
     */
    function getlist()
    {
        $db = DataBase::getinstance();
        
        $_pagename = $db->escape($this->page->getpagename());
        $query  = "SELECT filename FROM attach WHERE pagename = '$_pagename'";
        $query .= " ORDER BY filename ASC";
        $result = $db->query($query);
        $ret = array();
        while($row = $db->fetch($result)){
            $ret[] = $row['filename'];
        }
        return $ret;
    }
    
    
    /**
     * 添付ファイルのファイル名を変更する。
     * 
     * @param    string    $old    元のファイル名
     * @param    string    $new    新しいファイル名
     * @return    bool    変更が成功すればtrue、失敗すればfalse。
     */
    function rename($old, $new)
    {
        $db = DataBase::getinstance();
        
        $_pagename = $db->escape($this->page->getpagename());
        $_old = $db->escape($old);
        $_new = $db->escape($new);
        $query  = "UPDATE OR IGNORE attach SET filename = '$_new'";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_old')";
        $db->query($query);
        if($db->changes() != 0){
            $this->notify(array('rename', $old, $new));
            return true;
        }
        else{
            return false;
        }
    }
    
    
    /**
     * ファイルが存在するかどうかを確認する。
     * 
     * @param    string    $filename
     * @return    bool
     */
    function isexist($filename)
    {
        $db = DataBase::getinstance();
        
        $_pagename = $db->escape($this->page->getpagename());
        $_filename = $db->escape($filename);
        $query  = "SELECT count(*) FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $row = $db->fetch($db->query($query));
        return $row[0] == 1;
    }
    
    
    /**
     * ファイルを別ページに移動させる。
     * 
     * 移動先ページに同名のファイルが存在するときはDBExpectionを投げる。
     * 
     * @param    Page    $newpage
     */
    function move($newpage)
    {
        $db = DataBase::getinstance();
        
        $from = $this->page->getpagename();
        $to = $newpage->getpagename();
        $_from = $db->escape($from);
        $_to = $db->escape($to);
        $query  = "UPDATE attach SET pagename = '$_to'";
        $query .= " WHERE pagename = '$_from'";
        $db->fetch($db->query($query));
        $this->notify(array('move', $from, $to));
    }
}



/**
 * 添付ファイルを表すクラス。
 * 
 * ファイルごとにシングルトンのようにふるまう。
 */
class AttachedFile
{
    protected $filename;
    protected $page;
    protected static $notifier;
    
    
    function getfilename(){ return $this->filename; }
    function getpage(){ return $this->page; }
    
    static function attach($obj){ self::initNotifier(); self::$notifier->attach($obj); }
    static function detach($obj){ self::initNotifier(); self::$notifier->detach($obj); }
    protected function notify($arg = null){ self::$notifier->notify($this, $arg); }
    protected static function initNotifier()
    {
        if(empty(self::$notifier)){
            self::$notifier = new NotifierImpl();
        }
    }
    
    
    /**
     * インスタンスを取得する。
     */
    static function getinstance($filename, $page)
    {
        self::initNotifier();
        return new AttachedFile($filename, $page);
    }
    
    
    /**
     * コンストラクタ。
     */
    protected function __construct($filename, $page)
    {
        if(empty(self::$notifier)){
            self::$notifier = new NotifierImpl();
        }
        
        $this->filename = $filename;
        $this->page = $page;
    }
    
    
    /**
     * ファイルを保存する。
     * 
     * @param    const string    $bin    ファイルの内容。
     * @return    bool    成功すればtrue、すでにファイルがあればfalse。
     */
    function set($bin)
    {
        $db = DataBase::getinstance();
        
        $_filename = $db->escape($this->filename);
        $_pagename = $db->escape($this->page->getpagename());
        $_data = $db->escape($bin);
        $_size = strlen($bin);
        $_time = time();
        $query  = "INSERT OR IGNORE INTO attach";
        $query .= " (pagename, filename, binary, size, timestamp, count)";
        $query .= " VALUES('$_pagename', '$_filename', '$_data', $_size, $_time, 0)";
        $db->query($query);
        if($db->changes() != 0){
            $this->notify(array('attach'));
            return true;
        }
        else{
            return false;
        }
    }
    
    
    /**
     * ファイルを削除する。
     */
    function delete()
    {
        $db = DataBase::getinstance();
        
        $count = $this->getcount();
        
        $_filename = $db->escape($this->filename);
        $_pagename = $db->escape($this->page->getpagename());
        $query  = "DELETE FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $db->query($query);
        $this->notify(array('delete', $count));
    }
    
    
    /**
     * ファイル内容を取得する。
     * 
     * @param    bool    $count    取得時にカウンタを回すときはtrue
     * @return    string    ファイルがないときはnull。
     */
    function getdata($count = false)
    {
        $db = DataBase::getinstance();
        $db->begin();
        
        $_filename = $db->escape($this->filename);
        $_pagename = $db->escape($this->page->getpagename());
        
        $query  = "SELECT binary FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $row = $db->fetch($db->query($query));
        if($count){
            $query  = "UPDATE attach SET count = count + 1";
            $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
            $db->query($query);
        }
        $db->commit();
        return $row != false ? $row['binary'] : null;
    }
    
    
    /**
     * ファイルサイズを取得する。
     */
    function getsize()
    {
        $ret = $this->getcol('size');
        return $ret !== false ? $ret : 0;
    }
    
    
    /**
     * タイムスタンプを取得する。
     */
    function gettimestamp()
    {
        $ret = $this->getcol('timestamp');
        return $ret !== false ? $ret : null;
    }
    
    
    /**
     * ダウンロード数を取得する。
     */
    function getcount()
    {
        $ret = $this->getcol('count');
        return $ret !== false ? $ret : 0;
    }
    
    
    protected function getcol($col)
    {
        $db = DataBase::getinstance();
        
        $_filename = $db->escape($this->filename);
        $_pagename = $db->escape($this->page->getpagename());
        
        $query  = "SELECT $col FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $row = $db->fetch($db->query($query));
        return $row != false ? $row[$col] : false;
    }
}
