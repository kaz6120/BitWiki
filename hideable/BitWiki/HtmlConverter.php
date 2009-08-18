<?php
/*
 * HTMLConverter
 *
 * based on htmlconverter.inc.php,v 1.6 2005/09/06 01:14:55 
 *
 * @package BitWiki
 * @author  youka
 * @author  kaz <kaz6120@gmail.com>
 * @since   5.9.6
 * @version 9.8.16
 */

 

/**
 * パースにより生成された内部表現を元にHTML形式の文字列を生成するクラス。
 */
class HTMLConverter
{
    protected $root;    // Root element
    
    
    protected function __construct($root)
    {
        $this->root = $root;
    }
    
    
    static function visit($e)
    {
        return $e->accept(new self($e));
    }
    
    
    public function visitT_Body($e)
    {
                $ret = array();
                foreach($e->getelements() as $elem) {
                        $ret[] = $elem->accept($this);
                }
                return str_replace("\n\n", "\n", join("\n", $ret));

    }
    
    
    public function visitT_Empty($e)
    {
        return '';
    }
    
    
    public function visitT_Heading($e)
    {
        static $list = array('',
            '<h3 id=\"{$id}\"><a href=\"{$link}#{$id}\"><span class=\"sanchor\">■</span></a> {$str}</h3>',
            '<h4 id=\"{$id}\">{$str}</h4>',
            '<h5 id=\"{$id}\">{$str}</h5>',
            '<h6 id=\"{$id}\">{$str}</h6>');
        
        $level = $e->getlevel();
        $id = 'id' . substr(md5($level . $e->getsource()), 0, 6);
        $link = getURL($e->getcontext()->pagename);
        $str = $e->getelem()->accept($this);
        return eval("return \"{$list[$level]}\";");
    }
    
    
    public function visitT_Horizon($e)
    {
        return '<hr />';
    }
    
    
    public function visitT_Pre($e)
    {
        return '<pre>' . htmlspecialchars($e->gettext()) . '</pre>';
    }
    
    
    public function visitT_BlockQuote($e)
    {
        return '<blockquote>' . $e->getelem()->accept($this) . '</blockquote>';
    }
    
    
    public function visitT_UL($e)
    {
        return '<ul>' . "\n" . $e->getelem()->accept($this) . "\n" . '</ul>';
    }
    
    
    public function visitT_OL($e)
    {
        return '<ol>' . "\n" . $e->getelem()->accept($this) . "\n" . '</ol>';
    }
    
    
    public function visitT_List($e)
    {
        $ret[] = ' <li>';
        foreach($e->getelements() as $elem) {
            $ret[] = $elem->accept($this);
            if (get_class($elem->getnext()) == 'T_LI') {
                $ret[] = '</li>' . "\n" . ' <li>';
            }
        }
        $ret[] = '</li>';
        return join('', $ret);
    }
    
    
    public function visitT_LI($e)
    {
        return $this->setSmiley($e->getelem()->accept($this));
    }
    
    
    public function visitT_DL($e)
    {
        $ret = array();
        foreach($e->getelements() as $elem) {
            $ret[] = $elem->accept($this);
        }
        return '<dl>' . "\n" . join("\n", $ret) . "\n" . '</dl>';
    }
    
    
    public function visitT_DT($e)
    {
        return '<dt>' . $e->getelem()->accept($this) . '</dt>';
    }
    
    
    public function visitT_DD($e)
    {
        return '<dd>' . $this->setSmiley($e->getelem()->accept($this)) . '</dd>';
    }
    
    
    public function visitT_Table($e)
    {
        $ret = array();
        foreach($e->getelements() as $elem) {
            $ret[] = $elem->accept($this);
        }
        return '<table>' . "\n" . join("\n", $ret) . "\n" . '</table>';
    }
    
    
    public function visitT_TR($e)
    {
        $ret = array();
        foreach($e->getelements() as $elem) {
            $ret[] = $elem->accept($this);
        }
        return ' <tr>' . "\n" . join("\n", $ret) . "\n" . ' </tr>';
    }
    
    
    public function visitT_TD($e)
    {
        $ret = array();
        foreach($e->getelements() as $elem) {
            $ret[] = $elem->accept($this);
        }
        
        $tag = $e->isheader() ? 'th' : 'td';
        $align = $e->getalign() != null ? ' text-align: ' . $e->getalign() . ';' : '';
        $bgcolor = $e->getbgcolor() != null ? ' background-color: ' . $e->getbgcolor() . ';' : '';
        $style = ($align . $bgcolor) != '' ? ' style="' . $align . $bgcolor . '"' : '';
        
        return "  <{$tag}{$style}>" . join("\n", $ret) . "</{$tag}>";
    }
    
    
    public function visitT_BlockPlugin($e)
    {
        try {
            $plugin = Plugin::getPlugin($e->getpluginname());
            return $plugin->do_block(Page::getinstance($e->getcontext()->pagename), $e->getparam1(), $e->getparam2());
        } catch(NoExistPluginException $exc) {
            return nl2br(htmlspecialchars($e->getsource()));
        } catch(PluginException $exc) {
            return '<p class="warning">' . htmlspecialchars($exc->getMessage()) . '</p>';
        }
    }
    
    
    public function visitT_BlockTag($e)
    {
        try {
            $plugin = Plugin::getPlugin($e->getpluginname());
            return $plugin->do_blocktag(Page::getinstance($e->getcontext()->pagename), $e->getparam1(), $e->getparam2());
        } catch(NoExistPluginException $exc) {
            return nl2br(htmlspecialchars($e->getsource()));
        } catch(PluginException $exc) {
            return '<p class="warning">' . htmlspecialchars($exc->getMessage()) . '</p>';
        }
    }
    
    
    public function visitT_Comment($e)
    {
        return '';
    }
    

    public function setSmiley($str)
    {
        $smileyDir = SCRIPTURL . '/../theme/smiley/';
        $smilies = array(
                ':-)'  => 'smile.png',
                ';-)'  => 'wink.png',
                ':-D'  => 'laugh.png',
                ':-!'  => 'foot_in_mouth.png',
                ':-('  => 'frown.png',
                '=-o'  => 'gasp.png',
                '8-)'  => 'cool.png',
                ':-P'  => 'tongue.png',
                '}:-[' => 'angry.png',
                ':-|'  => 'ambivalent.png',
                ':-/'  => 'undecided.png',
                ':-d'  => 'yum.png',
                ':.('  => 'cry.png',
                ':-X'  => 'sealed.png',
                ':.)'  => 'touched.png',
                '|-|'  => 'sleep.png',
                '}}:'  => 'focus.png',
                '}-)'  => 'strong.png',
                ':-S'  => 'confused.png',
                ':-}'  => 'embarassed.png',
        );
        $iconWidth  = 18;
        $iconHeight = 18;
        foreach ($smilies as $smileyText => $smileyImage) {
                $str = str_replace(
                        $smileyText, 
                        '<img' . 
                        ' src="'    . $smileyDir  . $smileyImage . '"' .
                        ' width="'  . $iconWidth  . '"' .
                        ' height="' . $iconHeight . '"' .
                        ' alt="'    . $smileyText . '"' .
                        ' />',
                        $str
                );
        }
        return $str;
    }

    public function visitT_Paragraph($e)
    {
        $ret = array();
        foreach($e->getelements() as $elem) {
            $ret[] = '<p>' . $this->setSmiley($elem->accept($this)) . '</p>';
        }
        return str_replace("\n", '', join("\n", $ret));
    }
    
    
    public function visitT_Line($e)
    {
        $ret = array();
        foreach($e->getelements() as $elem) {
            $ret[] = $elem->accept($this);
        }
        return join('', $ret);
    }
    
    
    public function visitT_URL($e)
    {
        $url = htmlspecialchars($e->geturl());
        return "<a class=\"externallink\" href=\"$url\">$url</a>";
    }
    
    
    public function visitT_Mail($e)
    {
        $address = protectmail_url($e->getaddress());
        $alias = protectmail_html($e->getaddress());
        return "<a class=\"maillink\" href=\"mailto:$address\">$alias</a>";
    }
    
    
    public function visitT_BlacketName($e)
    {
        $pagename = $e->getpagename();
        $alias = $e->getalias() != '' ? $e->getalias() : $e->getpagename();
        if (mb_ereg('^' . EXP_URL . '$', $pagename)) {
            $alias = htmlspecialchars($alias);
            return "<a class=\"externallink\" href=\"$pagename\">$alias</a>";
        } else if (mb_ereg('^' . EXP_MAIL . '$', $pagename)) {
            $address = protectmail_url($pagename);
            $alias = protectmail_html($alias);
            return "<a class=\"maillink\" href=\"mailto:$address\">$alias</a>";
        } else if (mb_ereg('^(.+?):(.+)$', $pagename, $m) && !Page::getinstance($pagename)->isexist()) {
            return makeinterwikilink($m[1], $m[2], $alias);
        } else {
            $fullname = resolvepath($pagename, $e->getcontext()->pagename);
            return makelink(Page::getinstance($fullname), $alias);
        }
    }
    
    
    public function visitT_InlinePlugin($e)
    {
        try {
            $plugin = Plugin::getPlugin($e->getpluginname());
            return $plugin->do_inline(Page::getinstance($e->getcontext()->pagename), $e->getparam1(), $e->getparam2());
        } catch(NoExistPluginException $exc) {
            return nl2br(htmlspecialchars($e->getsource()));
        } catch(PluginException $exc) {
            return '<span class="warning">' . htmlspecialchars($exc->getMessage()) . '</span>';
        }
    }
    
    
    public function visitT_InlineTag($e)
    {
        try {
            $plugin = Plugin::getPlugin($e->getpluginname());
            return $plugin->do_inlinetag(Page::getinstance($e->getcontext()->pagename), $e->getparam1(), $e->getparam2());
        } catch(NoExistPluginException $exc) {
            return nl2br(htmlspecialchars($e->getsource()));
        } catch(PluginException $exc) {
            return '<span class="warning">' . htmlspecialchars($exc->getMessage()) . '</span>';
        }
    }
    
    
    public function visitT_Footnote($e)
    {
        $footnote = Footnote::getinstance();
        $num = $footnote->reserve();
        return $footnote->setnote($e->getelem()->accept($this), $num);
    }
    
    
    public function visitT_Strong($e)
    {
        $str = $e->getelem()->accept($this);
        $level = $e->getlevel();
        return $level == 1 ? "<em>$str</em>" : "<strong>$str</strong>";
    }
    
    
    public function visitT_Text($e)
    {
        $ret = array();
        foreach($e->getelements() as $elem) {
            $ret[] = $elem->accept($this);
        }
        return join('', $ret);
    }
    
    
    public function visitT_String($e)
    {
        $exp = '&amp;(#\d{2,4}|#x[0-9a-fA-F]{2,3}|' . CHARACTER_ENTITY_REFERENCES . ');';
        
        $str = htmlspecialchars($e->getstring());
        return mb_ereg_replace($exp, '&\1;', $str);
    }
    
    
    public function visitT_AutoLink($e)
    {
        return makelink($e->getpagename(), $e->getalias());
    }
    
    
    public function visitT_FuzzyLink($e)
    {
        return '<a '
               . 'class="fuzzylink" '
               . 'href="' . SCRIPTURL . '?cmd=fuzzylink&amp;key=' . rawurldecode($e->getkey()) . '" '
               . 'title="あいまいリンク"'
               . '>' 
               . htmlspecialchars($e->getkey()) . '</a>';
    }
}



/**
 * Footnone manager
 */
class Footnote
{
    protected $note = array();
    
    
    public static function getinstance()
    {
        static $ins = null;
        if ($ins == null) {
            $ins = new Footnote;
        }
        return $ins;
    }
    
    
    protected function __construct()
    {
        //do nothing.
    }
    
    
    /**
     * 番号だけ予約する。
     * 
     * @return    int    番号
     * */
    public function reserve()
    {
        $this->note[] = '';
        return count($this->note);
    }
    
    
    /**
     * 脚注を設定する。
     * 
     * @param   string  $html
     * @param   int     $num 
     * @return  string  $str
     */
    public function setnote($html, $num)
    {
        $this->note[$num-1] = $html;
        $note = strip_tags($html);
        return '<sup class="footnote" id="footnote_' . $num . '">'
             .  '<a href="#footnote_' . $num . '" title="' . $note . '">'
             .  '[*' . $num . ']'
             .  '</a>'
             . '</sup>';
    }
    
    
    /**
     * 脚注を取得する。
     * 
     * @return    string    html形式の文字列。
     */
    public function getnote()
    {
        if ($this->note == array()) {
            return '';
        }
        
        foreach($this->note as $i => $item) {
            $i++;
            $str[] = '  <li><a id="footnote_' . $i . '" href="#footnote_' . $i . '_r">*' . $i . '</a>: ' . $item . '</li>';
        }
        return "\n" . '<div class="footnote">' . "\n"
             . ' <ul>' . "\n"
             . join($str) . "\n"
             . ' </ul>' . "\n"
             . '</div>' . "\n";
    }
}
