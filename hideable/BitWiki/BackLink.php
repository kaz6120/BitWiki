<?php
/* 
 * $Id: backlink.inc.php,v 1.4 2005/07/14 08:32:22 youka Exp $
 *
 * @version 9.8.11
 */



/**
 * 逆リンクを管理するクラス。
 * 
 * シングルトン。
 */
class BackLink implements MyObserver
{
    /**
     * インスタンスを取得する。
     */
    static function getinstance()
    {
        static $ins;

        if(empty($ins)){
            $ins = new self;
        }
        return $ins;
    }
    
    
    /**
     * コンストラクタ。
     */
    protected function __construct()
    {
        //do nothing
    }
    
    
    /**
     * 本体実行前にクラスを初期化する
     */
    static function init()
    {
        Page::attach(self::getinstance());
    }
    
    
    /**
     * 逆リンクのリストを取得する。
     * 
     * @param    Page    $page    リンクされている側のページ。
     * @return    array('pagename' => string, 'times' => int)    リンクしている側のページのリスト（timesはリンクの重複数）。
     */
    function getlist($page)
    {
        $db = DataBase::getinstance();
        
        $_pagename = $db->escape($page->getpagename());
        $query  = "SELECT linker,times FROM linklist";
        $query .= " WHERE linked = '$_pagename'";
        $query .= " ORDER BY times DESC, linker ASC";
        
        $result = $db->query($query);
        $ret = array();
        while($row = $db->fetch($result)){
            $ret[] = array('pagename' => $row['linker'], 'times' => $row['times']);
        }
        return $ret;
    }
    
    
    /**
     * ページ更新と同時に逆リンクを更新する。
     */
    function update($page, $arg)
    {
        $this->refreshlinker($page);
        if($page->isexist() != $page->isexist(1)){    //新規または削除のとき
            $this->refreshlinked($page);
        }
    }
    
    
    /**
     * リンクする側を軸にして逆リンクを更新する。
     * 
     * @param    Page    $linker    リンクする側のページ名。
     */
    function refreshlinker($linker)
    {
        //隠しページからのリンク情報は出さない。
        if($linker->ishidden()){
            return;
        }
        
        $db = DataBase::getinstance();
        $db->begin();
        
        $body = parse_Page($linker);
        $seeker = new LinkSeeker($linker);
        $body->accept($seeker);
        $list = $seeker->getlist();
        
        $_linker = $db->escape($linker->getpagename());
        $db->query("DELETE FROM linklist WHERE linker = '$_linker'");
        
        foreach($list as $linkedname => $times){
            if($linker->getpagename() != $linkedname){
                $_linked = $db->escape($linkedname);
                $query  = "INSERT INTO linklist (linker, linked, times)";
                $query .= " VALUES('$_linker', '$_linked', $times)";
                $db->query($query);
            }
        }
        
        $db->commit();
    }
    
    
    /**
     * リンクされる側を軸にして逆リンクを更新する。
     * 
     * @param    Page    $linked    リンクされる側のページ名。
     */
    function refreshlinked($linked)
    {
        $db = DataBase::getinstance();
        $db->begin();
        
        $_linked = $db->escape($linked->getpagename());
        $db->query("DELETE FROM linklist WHERE linked  = '$_linked'");
        
        $query  = "SELECT pagename FROM page";
        $query .= " WHERE (source like '%${_linked}%')";
        $result = $db->query($query);
        while($row = $db->fetch($result)){
            $this->refreshlinker(Page::getinstance($row['pagename']));
        }
        
        $db->commit();
    }
    
    
    /**
     * 逆リンクを全て更新する。
     */
    function refreshall()
    {
        $db = DataBase::getinstance();
        $db->begin();
        
        $db->query("DELETE FROM linklist");
        
        $result = $db->query("SELECT pagename FROM page");
        while($row = $db->fetch($result)){
            $this->refreshlinker(Page::getinstance($row['pagename']));
        }
        
        $db->commit();
    }
}



/**
 * サイト内リンクを探すVisitor。
 */
class LinkSeeker
{
    protected $linklist = array();
    protected $currentpage;
    
    
    /**
     * コンストラクタ。
     *
     * @param    Page    $page    解析するページ。
     */
    function __construct($page)
    {
        $this->currentpage = $page;
    }
    
    
    /**
     * サイト内リンクのリストを返す。
     * 
     * @return    array(string $linked => int $times)    $linkedはリンク先ページ名、$timesは重複数。
     */
    function getlist()
    {
        return $this->linklist;
    }
    
    
    /**
     * visit系関数のデフォルトは内包する要素を呼び出す。
     */
    function __call($funcname, $params)
    {
        $elements = $params[0]->getelements();
        foreach($elements as $elem){
            $elem->accept($this);
        }
    }
    
    
    /**
     * リストに加算する。
     * 
     * @param    string    $linked    リンクされる側のページ名。
     */
    protected function add($linked)
    {
        if(isset($this->linklist[$linked])){
            $this->linklist[$linked]++;
        }
        else{
            $this->linklist[$linked] = 1;
        }
    }
    
    
    function visitT_AutoLink($e)
    {
        $this->add($e->getpagename());
    }
    
    
    function visitT_BlacketName($e)
    {
        $pagename = $e->getpagename();
        if(mb_ereg('^' . EXP_URL . '$', $pagename)){
            //URLにヒット。何もしない。
        }
        else if(mb_ereg('^' . EXP_MAIL . '$', $pagename)){
            //Mailにヒット。何もしない。
        }
        else if(mb_ereg('^(.+?):(.+)$', $pagename)){
            //InterWikiNameにヒット。何もしない。
        }
        else{
            //のこるはサイト内リンク。
            $fullname = resolvepath($pagename, $this->currentpage->getpagename());
            $this->add($fullname);
        }
    }
}
