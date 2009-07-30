<?php
/* 
 * $Id: index.php,v 1.2 2005/06/29 10:54:02 youka Exp $
 */

/*
 * 設定ここから 
 */

//サイト名
define('SITENAME', 'KinoWiki-Rev');

//トップページのページ名
define('DEFAULTPAGE', 'FrontPage');

//WikiFarmのID。ファイル名（拡張子を除く）と一致させるのが無難。
define('WIKIID', 'index');

//HTMLのテンプレート。スキン用ディレクトリにあるファイルを指定。
define('SKINFILE', 'default.tpl.htm');

//使用するテーマ。テーマ用ディレクトリ（THEME_DIR）に入れてあるディレクトリ名を指定。
define('THEME', 'default');

//パスワード。md5()で括るか、md5で暗号化したものをセットする。
define('ADMINPASS', md5('password'));

//メールの設定
define('MAIL_USE', false);	//メールを使う場合はtrue、使わない場合はfalse。
define('MAIL_DIFF', true);	//差分のみを送る場合はtrue、差分＋全文送るならfalse。
define('MAIL_SMTP', 'localhost');	//Windowsでのみ有効
define('MAIL_SMTP_PORT', 25);	//Windowsでのみ有効
define('MAIL_FROM', 'yourmail@example.com');
define('MAIL_TO', 'yourmail@example.com');


/*
 * 以下は細かな設定です。
 * よくわからない場合はデフォルトのままでかまいません。
 */

//添付ファイルの最大サイズ（byte）
define('ATTACH_MAXSIZE', 2000000);

//あいまいリンクのつづりミス許容ページ名最小文字数
//ここで設定した文字数以上のページ名は１文字違っていてもあいまいリンクされる。
//運用途中で変更した場合はあいまいリンクの再構築が必要。
define('FUZZYLINK_SPELLMISSMINSIZE', 5);

/*
 * ディレクトリの設定
 */

//本体を保存するディレクトリ
define('HIDEABLE_DIR', './hideable/');

//DBファイルおよびエラーログを保存するディレクトリ
//このディレクトリには書き込み権限を設定しなければならない
define('DATA_DIR', HIDEABLE_DIR . 'data/');

//コマンド用ファイルを保存するディレクトリ
define('COMMAND_DIR', './cmd/');

//プラグインを保存するディレクトリ
define('PLUGIN_DIR', './plugin/');

//スキンを保存するディレクトリ
define('SKIN_DIR', HIDEABLE_DIR . 'skin/');

//テーマを保存するディレクトリ
define('THEME_DIR', './theme/');

//コンパイル済みテンプレートを保存するディレクトリ
//このディレクトリには書き込み権限を設定しなければならない
define('COMPILEDTPL_DIR', HIDEABLE_DIR . 'templates_c/');


/*
 * 設定ここまで
 */



require_once(HIDEABLE_DIR . 'kinowiki.inc.php');

KinoWiki::main();


?>
