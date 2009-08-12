<?php
/* 
 * $Id: recent.inc.php,v 1.2 2005/09/05 10:26:57 youka Exp $
 */

class Plugin_recent extends Plugin
{
    function do_block($page, $param1, $param2)
    {
        $num = (int)trim($param1) > 0 ? (int)trim($param1) : 15;
        $exp = array();
        foreach(array_map('trim', explode("\n", $param2)) as $s) {
            if ($s != '') {
                $exp[] = $s;
            }
        }
        
        $db = DataBase::getinstance();
        $query  = "SELECT pagename,timestamp FROM page";
        if ($exp != array()) {
            $_exp = $db->escape('(?:' . join('|', $exp) . ')');
            $query .= " WHERE php('mb_ereg', '$_exp', pagename) = 0";
        }
        $query .= " ORDER BY timestamp DESC, pagename ASC LIMIT $num";
        $result = $db->query($query);
        
        $list = array();
        while($row = $db->fetch($result)) {
            $list[date('Y-m-d', $row['timestamp'])][] = makelink(Page::getinstance($row['pagename']));
        }
        
        $smarty = $this->getSmarty();
        $smarty->assign('list', $list);
        return $smarty->fetch('recent.tpl.htm');
    }
}

