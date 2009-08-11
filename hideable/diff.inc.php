<?php
/* 
 * $Id: diff.inc.php,v 1.3 2005/09/06 01:14:55 youka Exp $
 *
 * @version  9.8.11
 */
require_once('Text/Diff.php');



/**
 * 差分をわかりやすいようにして文字列を取得する。
 *
 * @param mixed    $old    stringまたはarray()
 * @param mixed    $new    stringまたはarray()
 * @param boolean    $diffonly    trueのときは差分のみを返す。省略するとfalse。
 * @return string    差分の行頭に+-をつけて返す。
 */
function diff($old, $new, $diffonly = false)
{
    if(is_string($old)){
        $old = explode("\n", $old);
    }
    if(is_string($new)){
        $new = explode("\n", $new);
    }
    
    $renderer = new DiffRenderer(new Text_Diff($old, $new));
    return $renderer->render($diffonly);
}


/**
 * Diffの内容を表示しやすいように加工するクラス。
 */
class DiffRenderer
{
    protected $diff;    //加工するDiff
    
    
    /**
     * @param    Text_Diff    $diff    加工対象のDiff
     */
    function __construct($diff)
    {
        $this->diff = $diff;
    }
    
    
    /**
     * Diffをテキスト形式にして取得する。
     * 
     * @param    bool    $diffonly    trueのときは差分のみを返す。
     */
    function render($diffonly = false)
    {
        $ret = array();
        foreach($this->diff->getDiff() as $edit){
            switch (get_class($edit)) {
                case 'Text_Diff_Op_copy':
                    if(!$diffonly){
                        $ret[] = $this->lines(' ', $edit->orig);
                    }
                    break;
                case 'Text_Diff_Op_add':
                    $ret[] = $this->lines('+', $edit->final);
                    break;
                case 'Text_Diff_Op_delete':
                    $ret[] = $this->lines('-', $edit->orig);
                    break;
                case 'Text_Diff_Op_change':
                    $ret[] = $this->lines('-', $edit->orig);
                    $ret[] = $this->lines('+', $edit->final);
                    break;
                default:
                    throw FatalException("DiffRenderer: Unknown edit type");
            }
        }
        return join("\n", $ret);
    }
    
    
    /**
     * 行頭にprefixをつける。
     * 
     * 引数が配列で返値はstringになる。
     * 
     * @param    string    $prefix    行頭につけるprefix
     * @param    array(string)    $lines    文字列群
     * @return    string
     */
    protected function lines($prefix, $lines)
    {
        $ret = array();
        foreach($lines as $l){
            $ret[] = $prefix . $l;
        }
        return join("\n", $ret);
    }
}
