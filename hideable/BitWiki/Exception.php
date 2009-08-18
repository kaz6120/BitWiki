<?php
/*
 * Exception Class
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.6.29
 * @version 9.8.12
 */



/**
 * 例外用クラス。続行可能な場合（他の処理に影響を及ぼす恐れがない場合）に使用する
 */
class MyException extends Exception
{
}

/**
 * 致命的例外用クラス。続行不可能な場合（他の処理に影響を及ぼす恐れがある場合）に使用する
 */
class FatalException extends Exception
{
    protected $hiddenmessage;    //外部に漏らすべきではない情報（文字列）を格納する。
    
    /** 
     * 外部に漏らしてはいけない情報を取得する。
     */
     function getHiddenMessage(){
         return $this->hiddenmessage;
     }
     
     
     /**
      * コンストラクタ
      * @param    string    $mes    エラーメッセージ。このメッセージは外部に表示される。
      * @param    string    $hiddenmes    外部に表示してはいけない情報。ログにのみ記録される。
      */
     function __construct($mes, $hiddenmes = ''){
         parent::__construct($mes);
         $this->hiddenmessage = $hiddenmes;
     }
}

/**
 * Exceptionの内容をエラーログに保存する。
 *
 * @param Exception    $exc
 * @return boolean    成功すればtrueを返す。
 */
function saveexceptiondump($exc)
{
    $fp = fopen(DATA_DIR . WIKIID . '.error.log', 'a');
    if($fp == false){
        return false;
    }
    
    $str[] = date('Y-m-d H:i:s');
    $str[] = 'Exception: ' . get_class($exc);
    $str[] = $exc->getFile() . '(' . $exc->getLine() . ')';
    $str[] = $exc->getMessage();
    if(is_a($exc, 'FatalException')){
        $str[] = $exc->getHiddenMessage();
    }
    $str[] = $exc->getTraceAsString();
    $str[] = "\n\n";
    $ret = fwrite($fp, join("\n", $str));
    fclose($fp);
    return $ret !== false ? true : false;
}
