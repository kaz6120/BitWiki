<?php

class Plugin_outline extends Plugin
{
    function do_block($page, $param1, $param2)
    {
        $nest = max((int)trim($param1), 1);
        
        $body = parse_Page($page);
        $list = array();
        foreach($body->getelements() as $e) {
            if (get_class($e) == 'T_Heading') {
                $str = $e->getelem()->getsource();
                $id = 'id' . substr(md5($e->getlevel() . $e->getsource()), 0, 6);
                if ($e->getlevel() <= $nest) {
                    $list[] = str_repeat('-', $e->getlevel()) . "&anchor($id) {{$str}}";
                }
            }
        }
        return convert_block(join("\n", $list), $page->getpagename());
    }
}
