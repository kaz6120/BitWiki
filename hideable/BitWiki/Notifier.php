<?php
/**
 * Notifier
 *
 * based on notifier.inc.php,v 1.2 2005/06/27 18:08:07
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.12.1
 * @version 9.8.13 
 */



/**
 * 更新通知クラスのインターフェース。
 */
interface Notifier
{
    /**
     * 通知対象に登録する。
     * 
     * @param    MyObserver    $obj    通知対象オブジェクト。
     */
    function attach($obj);
    
    
    /**
     * 通知対象から外す。
     * 
     * @param    MyObserver    $obj    通知対象オブジェクト。
     */
    function detach($obj);
}



/**
 * 更新通知を受けるクラスのインターフェース。
 */
interface MyObserver
{
    /**
     * 更新通知を受け取る。
     * 
     * @param    object    $obj    更新したオブジェクト。
     * @param    mixed    $arg    更新したオブジェクトから渡されるなにか特別な値。
     * @return    mixed    返すと意味があるかも知れない値。
     */
    function update($obj, $arg);
}



/**
 * 更新通知クラスのデフォルト実装。
 */
class NotifierImpl implements Notifier 
{
    protected $observers = array();
    
    
    function getobservers(){ return $this->observers; }
    
    
    /**
     * 通知対象に登録する。
     * 
     * @param    MyObserver    $obj    通知対象オブジェクト。
     */
    function attach($obj)
    {
        $this->observers[] = $obj;
    }
    
    
    /**
     * 通知対象から外す。
     * 
     * @param    MyObserver    $obj    通知対象オブジェクト。
     */
    function detach($obj)
    {
        $this->observers = array_diff($this->observers, array($obj));
    }
    
    
    /**
     * 更新を通知する。
     * 
     * @param    Notifier    $notifier    更新したオブジェクト。多くは$this。
     * @return    void
     */
    function notify($notifier, $arg = null)
    {
        foreach($this->observers as $obj){
            $obj->update($notifier, $arg);
        }
    }
}
