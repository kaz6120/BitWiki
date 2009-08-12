<?php
/* 
 * Attachment classes
 * 
 * based on attach.inc.php,v 1.2 2005/06/27 18:24:27 youka
 *
 * @version 9.8.13
 */


/**
 * Attach class
 * 
 * Attachment file manager. Behave like singleton in each page.
 */
class Attach
{
    protected $page;
    protected static $notifier;
    
    /** Get page */
    function getpage() { return $this->page; }
    
    static function attach($obj) { self::initNotifier(); self::$notifier->attach($obj); }
    static function detach($obj) { self::initNotifier(); self::$notifier->detach($obj); }
    protected function notify($arg = null) { self::$notifier->notify($this, $arg); }
    protected static function initNotifier()
    {
        if (empty(self::$notifier)) {
            self::$notifier = new NotifierImpl();
        }
    }
    
    
    /**
     * Get instance
     * 
     * @param  $page
     */
    static function getInstance($page)
    {
        self::initNotifier();
        return new self($page);
    }
    
    
    /**
     * Constructor
     */
    protected function __construct($page)
    {
        $this->page = $page;
    }
    
    
    /**
     * Get file list
     * 
     * @return array(string)  file names array 
     */
    function getlist()
    {
        $db = DataBase::getInstance();
        
        $_pagename = $db->escape($this->page->getpagename());
        $query  = "SELECT filename FROM attach WHERE pagename = '$_pagename'";
        $query .= " ORDER BY filename ASC";
        $result = $db->query($query);
        $ret = array();
        while($row = $db->fetch($result)) {
            $ret[] = $row['filename'];
        }
        return $ret;
    }
    
    
    /**
     * Rename attachiment file name
     * 
     * @param    string    $old   Old name
     * @param    string    $new   New name
     * @return   bool 
     */
    function rename($old, $new)
    {
        $db = DataBase::getInstance();
        
        $_pagename = $db->escape($this->page->getpagename());
        $_old = $db->escape($old);
        $_new = $db->escape($new);
        $query  = "UPDATE OR IGNORE attach SET filename = '$_new'";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_old')";
        $db->query($query);
        if ($db->changes() != 0) {
            $this->notify(array('rename', $old, $new));
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Check if file is exist
     * 
     * @param    string  $filename
     * @return   bool
     */
    function isexist($filename)
    {
        $db = DataBase::getInstance();
        
        $_pagename = $db->escape($this->page->getpagename());
        $_filename = $db->escape($filename);
        $query  = "SELECT count(*) FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $row = $db->fetch($db->query($query));
        return $row[0] == 1;
    }
    
    
    /**
     * Move attachiment file to another page.
     * 
     * @param    Page    $newpage
     */
    function move($newpage)
    {
        $db = DataBase::getInstance();
        
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
 * Attached file class 
 * 
 * Each file behaves like singleton.
 */
class AttachedFile
{
    protected $filename;
    protected $page;
    protected static $notifier;
    
    
    function getfilename() { return $this->filename; }
    function getpage() { return $this->page; }
    
    static function attach($obj) { self::initNotifier(); self::$notifier->attach($obj); }
    static function detach($obj) { self::initNotifier(); self::$notifier->detach($obj); }
    protected function notify($arg = null) { self::$notifier->notify($this, $arg); }
    protected static function initNotifier()
    {
        if (empty(self::$notifier)) {
            self::$notifier = new NotifierImpl();
        }
    }
    
    
    /**
     * Get instance
     */
    static function getInstance($filename, $page)
    {
        self::initNotifier();
        return new AttachedFile($filename, $page);
    }
    
    
    /**
     * Constructor
     */
    protected function __construct($filename, $page)
    {
        if (empty(self::$notifier)) {
            self::$notifier = new NotifierImpl();
        }
        
        $this->filename = $filename;
        $this->page = $page;
    }
    
    
    /**
     * Save atttachment file into database.
     * 
     * @param    const string $bin // Content of the file
     * @return   bool
     */
    public function set($bin)
    {

        $db = new Database();

        $query  = 'INSERT OR IGNORE INTO '
                .     'attach '
                .         '(pagename, filename, binary, size, timestamp, count) '
                .     'VALUES '
                .         '(:pagename, :filename, :data, :size, :timestamp, :count)';

        $stmt = $db->link->prepare($query);
        $res  = $stmt->execute(
                    array(
                        ':pagename'  => $this->page->getpagename(),
                        ':filename'  => $this->filename,
                        ':data'      => $bin,
                        ':size'      => strlen($bin),
                        ':timestamp' => time(),
                        ':count'     => 0,
                    )
                );
        if ($res == true) {
            $this->notify(array('attach'));
            return true;
        } else{
            return false;
        }
        
        /*
         ****** OLD WAY ****** 
         This doesn't work properly on PHP5.3 with magic_quotes_gpc = Off.
         Using default prepare statement on quoting data is better way.
         */
        //$db = DataBase::getInstance();
        //$_filename = $db->escape($this->filename);
        //$_pagename = $db->escape($this->page->getpagename());
        //$_data = $db->escape($bin);
        //$_size = strlen($bin);
        //$_time = time();
        //$query  = "INSERT OR IGNORE INTO attach";
        //$query .= " (pagename, filename, binary, size, timestamp, count)";
        //$query .= " VALUES('$_pagename', '$_filename', '$_data', $_size, $_time, 0)";
        //$db->query($query);
        //if ($db->changes() != 0) {
            //$this->notify(array('attach'));
            //return true;
        //} else{
            //return false;
        //}
    }
    
    
    /**
     * Delete attachment file
     */
    function delete()
    {
        $db = DataBase::getInstance();
        
        $count = $this->getcount();
        
        $_filename = $db->escape($this->filename);
        $_pagename = $db->escape($this->page->getpagename());
        $query  = "DELETE FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $db->query($query);
        $this->notify(array('delete', $count));
    }
    
    
    /**
     * Get content of the file
     * 
     * @param    bool    $count
     * @return   string    
     */
    function getdata($count = false)
    {
        $db = DataBase::getInstance();
        $db->begin();
        
        $_filename = $db->escape($this->filename);
        $_pagename = $db->escape($this->page->getpagename());
        
        $query  = "SELECT binary FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $row = $db->fetch($db->query($query));
        if ($count) {
            $query  = "UPDATE attach SET count = count + 1";
            $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
            $db->query($query);
        }
        $db->commit();
        return $row != false ? $row['binary'] : null;
    }
   
    
    /**
     * Get size of the attachment file. 
     */
    function getsize()
    {
        $ret = $this->getcol('size');
        return $ret !== false ? $ret : 0;
    }
    
    
    /**
     * Get timestamp of the attachment file
     */
    function gettimestamp()
    {
        $ret = $this->getcol('timestamp');
        return $ret !== false ? $ret : null;
    }
    
    
    /**
     * Download count
     */
    function getcount()
    {
        $ret = $this->getcol('count');
        return $ret !== false ? $ret : 0;
    }
    
    
    protected function getcol($col)
    {
        $db = DataBase::getInstance();
        
        $_filename = $db->escape($this->filename);
        $_pagename = $db->escape($this->page->getpagename());
        
        $query  = "SELECT $col FROM attach";
        $query .= " WHERE (pagename = '$_pagename' AND filename = '$_filename')";
        $row = $db->fetch($db->query($query));
        return $row != false ? $row[$col] : false;
    }
}
