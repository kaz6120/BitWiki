<?php
/* 
 * $Id: vars.inc.php,v 1.2 2005/09/13 16:51:12 youka Exp $
 */



/**
 * 外部から来る変数を管理するクラス。
 */
class Vars
{
    public static $get;
    public static $post;
    public static $cookie;
    
    
    /**
     * 本体実行前にクラスを初期化する。
     */
    static function init()
    {
        //GET、POST、COOKIEの初期化
        self::$post = $_POST;
        self::$get = $_GET;
        self::$cookie = $_COOKIE;
        if (get_magic_quotes_gpc()) {
            self::$post = map('stripslashes', self::$post);
            self::$get = map('stripslashes', self::$get);
            self::$cookie = map('stripslashes', self::$cookie);
        }
        self::$get = map('rawurldecode', self::$get);
        if (ini_get('mbstring.encoding_translation')) {
            $encode = ini_get('mbstring.internal_encoding');
            $proc = "return mb_convert_encoding(\$str, 'UTF-8', '$encode');";
            self::$post = map(create_function('$str', $proc), self::$post);
        }        
    }
}

