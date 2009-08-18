<?php
/* 
 * Controller
 *
 * controller.inc.php,v 1.4 2005/09/05 22:17:49 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.9.5
 * @version 9.8.18
 */



/**
 * CommandとPluginのもととなるクラス。
 * 派生させたCommandとPluginはシングルトンとする。
 */
abstract class Controller implements Notifier
{
    /**
     * NotifierImplのインスタンス。
     * @var NotifierImpl
     */
    protected $notifier;
    
    protected $body = '';

    
    protected function __construct()
    {
        $this->notifier = new NotifierImpl();
    }
    
    
    /**
     * 初期化を行う。アプリケーション準備作業中に呼び出される。
     */
    function init()
    {
        //do nothing
    }

    
    /**
     * コマンド実行前に呼び出される。
     */
    function doing()
    {
        //do nothing
    }
    
    
    /**
     * コマンド実行後に呼び出される。
     */
    function done()
    {
        //do nothing
    }
    
    
    /**
     * 本体処理を実行する。
     */     
    function run()
    {
        $this->notifier->notify($this, 'doing');
        $ret = $this->do_url();
        $this->notifier->notify($this, 'done');
        return $ret;
    }
    
    
    /**
     * URLから指定された場合にこの関数を実行する。
     *
     * @return array(string => string)    スキンに渡す値。titleとbodyは必須。
     */     
    function do_url()
    {
        //do nothing
    }
    
    
    /**
     * 表示するHTMLテキストを設定する。
     *
     * @param string    $html
     */
    protected function setbody($html)
    {
        $this->body = $html;
    }

    
    /**
     * 表示するHTMLテキストを取得する。
     *
     * @return string    
     */
    function getbody()
    {
        return $this->body;
    }

    
    /**
     * 実行中のPageを取得する。
     *
     * @return Page
     */
    protected function getcurrentPage()
    {
        return BitWiki::getinstance()->getPage();
    }
    
    
    /**
     * 通知対象に追加する。
     * 
     * @param    MyObserver    $obj    通知対象のインスタンス。
     */
    function attach($obj){ $this->notifier->attach($obj); }
    
    
    /**
     * 通知対象から除外する。
     * 
     * @param    MyObserver    $obj    通知対象のインスタンス。
     */
    function detach($obj){ $this->notifier->detach($obj); }
    
    
    /**
     * 更新を通知する。
     */
    function notify($arg = null)
    {
        $this->notifier->notify($this, $arg);
    }
}
