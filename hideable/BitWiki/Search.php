<?php
/*
 * Search
 *
 * based on search.inc.php,v 1.1.1.1 2005/06/12 15:38:36 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.6.12
 * @version 9.8.18
 */


/**
 * 検索機能のクラス。
 * 
 * シングルトンのように振舞う。
 */
class Search
{
    /**
     * インスタンスを取得する。
     */
    static function getinstance()
    {
        static $ins;
        
        if (empty($ins)) {
            $ins = new self;
        }
        return $ins;
    }
    
    
    /**
     * コンストラクタ。
     */
    protected function __construct()
    {
        //do nothing
    }
    
    
    /**
     * ページ検索する。
     * 
     * @param    array(string)    $word    検索語句。
     * @param    bool    $andsearch    trueの場合はAND検索、falseの場合はOR検索。
     * @return    array(string)    ページ名。アルファベット順にソート済み。
     */
    public function normalsearch($word, $andsearch = true)
    {
        $db = DataBase::getinstance();
        
        for ($i = 0; $i < count($word); $i++) {
            $_word[] = $db->escape($word[$i]);
        }
        
        $andor = $andsearch ? 'AND' : 'OR';
        $query  = "SELECT pagename FROM page";
        $query .= " WHERE";
        $query .= "  (pagename like '%" . join("%' $andor pagename like '%", $_word) . "%')";
        $query .= "  OR";
        $query .= "  (source like '%" . join("%' $andor source like '%", $_word) . "%')";
        $query .= " ORDER BY pagename ASC";
        return $this->_search($query);
    }
    
    
    /**
     * あいまい検索する。
     * 
     * @param    array(string)    $word    検索語句。
     * @param    bool    $andsearch    trueの場合はAND検索、falseの場合はOR検索。
     * @return    array(string)    ページ名。アルファベット順にソート済み。
     */
    public function fuzzysearch($word, $andsearch = true)
    {
        $exp = array();
        foreach ($word as $w) {
            $exp[] = FuzzyFunc::makefuzzyexp($w);
        }
        return $this->eregsearch($exp, $andsearch);
    }
    
    
    /**
     * 正規表現検索する。
     * 
     * @param    array(string)    $word    検索語句（正規表現）。
     * @param    bool    $andsearch    trueの場合はAND検索、falseの場合はOR検索。
     * @return    array(string)    ページ名。アルファベット順にソート済み。
     */
    public function eregsearch($word, $andsearch = true)
    {
        $db = DataBase::getinstance();
        
        for ($i = 0; $i < count($word); $i++) {
            $_word[] = $db->escape($word[$i]);
        }
        
        $andor = $andsearch ? 'AND' : 'OR';
        $query  = "SELECT pagename FROM page";
        $query .= " WHERE";
        $query .= "  (php('mb_ereg', '" . join("', pagename) $andor php('mb_ereg', '", $_word) . "', pagename))";
        $query .= "  OR";
        $query .= "  (php('mb_ereg', '" . join("', source) $andor php('mb_ereg', '", $_word) . "', source))";
        $query .= " ORDER BY pagename ASC";
        return $this->_search($query);
    }
    
    
    /**
     * 更新日時で検索する。
     * 
     * @param    int    $from    開始日時のタイムスタンプ
     * @param    int    $to    終了日時のタイムスタンプ
     * @return    array(string)    ページ名。新しい順にソート済み。
     */
    public function timesearch($from, $to)
    {
        $query  = "SELECT pagename FROM page";
        $query .= " WHERE ($from <= timestamp AND timestamp <= $to)";
        $query .= " ORDER BY timestamp DESC";
        return $this->_search($query);
    }
    
    
    /**
     * 検索クエリ実行。
     * 
     * @return    array(string)
     */
    protected function _search($query)
    {
        $db = DataBase::getinstance();
        $result = $db->query($query);
        $ret = array();
        while($row = $db->fetch($result)) {
            $ret[] = $row['pagename'];
        }
        return $ret;
    }
    
    
    /**
     * 検索語にタグをつける。
     * 
     * @param    string    $text    タグをつける対象（HTML形式）
     * @param    array(string)    $word    検索語
     * @param    string    $type    検索の種類
     */
    public function mark($text, $word, $type)
    {
        switch($type) {
            case 'fuzzy':
                $call = '_markword_fuzzy';
                break;
            case 'ereg':
                $call = '_markword_ereg';
                break;
            default:
                $call = '_markword_normal';
                break;
        }
        
        $count = 1;
        foreach ($word as $w) {
            $s = $this->$call($w);
            $pattern = "((?:\G|>)[^<]*?)($s)";
            $replace = "\\1<span class=\"search word$count\">\\2</span>";
            $text = mb_ereg_replace($pattern, $replace, $text, 'm');
            $count++;
        }
        return $text;
    }
    
    
    protected function _markword_normal($w)
    {
        return mb_ereg_quote($w);
    }
    
    
    protected function _markword_fuzzy($w)
    {
        return FuzzyFunc::makefuzzyexp($w);
    }
    
    
    protected function _markword_ereg($w)
    {
        return $w;
    }
}
