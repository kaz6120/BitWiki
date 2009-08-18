<?php

class Plugin_download extends Plugin
{
    function do_inline($page, $param1, $param2)
    {
        mb_ereg('^(.*?)(?:\s*,\s*(.*?))?$', trim($param1), $m);
        $file = $m[1];
        $page = $m[2] == '' ? $page : Page::getinstance($m[2]);
        
        if(!Attach::getinstance($page)->isexist($file)){
            return '<span class="warning">ファイルがありません</span>';
        }
        $url = SCRIPTURL . '?cmd=attach'
             . '&amp;param=download'
             . '&amp;page=' . rawurlencode($page->getpagename()) 
             . '&amp;file=' . rawurlencode($file);
        return '<a href="' . $url . '">' . htmlspecialchars($file) . '</a>';
    }
}

