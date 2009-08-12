<?php
/*
 * $Id: image.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */



class Plugin_image extends Plugin
{
    static $type = array(
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'swf' => 'application/x-shockwave-flash'
    );
    
    
    function do_inline($page, $param1, $param2)
    {
        mb_ereg('^(.*?)(?:\s*,\s*(.*?))?$', trim($param1), $m);
        $file = $m[1];
        $page = $m[2] == '' ? $page : Page::getinstance($m[2]);

        if (!Attach::getinstance($page)->isexist($file)) {
            return '<span class="warning">ファイルがありません</span>';
        }
        if (!mb_ereg('\.(.+?)$', $file, $m) || !isset(Plugin_image::$type[$m[1]])) {
            return '<span class="warning">.' . htmlspecialchars($m[1]) . 'には対応していません</span>';
        }
        
        $url = SCRIPTURL . '?plugin=image&amp;page=' . rawurlencode($page->getpagename()) . '&amp;file=' . rawurlencode($file);
        if ($m[1] == 'swf') {
            $smarty = new PluginSmarty('image');
            $smarty->assign('url', $url);
            return $smarty->fetch('swf.tpl.htm');
        } else {
            return '<img src="' . $url . '" />';
        }
    }
    
    
    function do_url()
    {
        if (empty(Vars::$get['page']) || empty(Vars::$get['file'])) {
            exit();
        }
        if (!mb_ereg('\.(.+?)$', Vars::$get['file'], $m) || empty(self::$type[$m[1]])) {
            exit();
        }
        
        $file = AttachedFile::getInstance(Vars::$get['file'], Page::getInstance(Vars::$get['page']));
        header('Content-Type: ' . self::$type[$m[1]]);
        header('Content-Length: ' . $file->getsize());
        echo $file->getdata();
        exit();
    }
}
