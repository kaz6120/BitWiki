<?php
/* 
 * $Id: attach.inc.php,v 1.3 2005/12/19 16:22:34 youka Exp $
 */



class Command_attach extends Command implements MyObserver
{
    public function init()
    {
        Command::getCommand('show')->attach($this);
    }
    
    
    public function do_url()
    {
        if (isset(Vars::$get['param'])) {
            switch(Vars::$get['param']) {
                case 'upload':
                    return $this->upload();
                case 'download':
                    return $this->download();
                case 'rename':
                    return $this->rename();
                case 'delete':
                    return $this->delete();
                case 'show':
                    return $this->show();
                case 'list':
                    return $this->listfile();
                case 'listpage':
                    return $this->listpage();
            default:
                throw new CommandException('パラメータがちがいます。', $this);
            }
        } else {
            return $this->showform();
        }
    }
    
    
    protected function upload()
    {
        if (!isset(Vars::$post['page'])) {
            throw new CommandException('パラメータが足りません。', $this);
        }
        
        $page = Page::getinstance(Vars::$post['page']);
        if ($page->isnull()) {
            throw new CommandException('パラメータが正しくありません。', $this);
        }
        
        if (!is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
            throw new CommandException('ファイルアップロード攻撃をされた可能性があります。', $this);
        }
        
        if ($_FILES['uploadfile']['error'] != UPLOAD_ERR_OK) {
            switch($_FILES['uploadfile']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    throw new CommandException('アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。', $this);
                case UPLOAD_ERR_FORM_SIZE:
                    throw new CommandException('アップロードされたファイルは、HTMLフォームで指定された MAX_FILE_SIZE を超えています。', $this);
                case UPLOAD_ERR_PARTIAL:
                    throw new CommandException('アップロードされたファイルは一部のみしかアップロードされていません。', $this);
                case UPLOAD_ERR_NO_FILE:
                    throw new CommandException('ファイルはアップロードされませんでした。', $this);
                default:
                    throw new CommandException('何らかの理由でアップロードが失敗しました。', $this);
            }
        }
        
        $file = AttachedFile::getinstance($_FILES['uploadfile']['name'], $page);
        $r = $file->set(file_get_contents($_FILES['uploadfile']['tmp_name']));
        $smarty = $this->getSmarty();
        $smarty->assign('filename', $_FILES['uploadfile']['name']);
        $smarty->assign('pagename', $page->getpagename());
        if ($r) {
            $ret['body'] = $smarty->fetch('success.tpl.htm');
            $ret['title'] = $page->getpagename() . ' にファイルを添付しました';
        } else {
            $ret['body'] = $smarty->fetch('failed.tpl.htm');
            $ret['title'] = $page->getpagename() . ' にファイルを添付できませんでした';
        }
        $ret['pagename'] = $page->getpagename();
        return $ret;
    }
    
    
    protected function download()
    {
        if (!isset(Vars::$get['page']) || !isset(Vars::$get['file'])) {
            throw new CommandException('パラメータが足りません。', $this);
        }
        
        $page = Page::getinstance(Vars::$get['page']);
        if ($page->isnull() || Vars::$get['file'] == '') {
            throw new CommandException('パラメータが正しくありません。', $this);
        }
        
        $file = AttachedFile::getinstance(Vars::$get['file'], $page);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . Vars::$get['file'] . '"');
        header('Content-Length: ' . $file->getsize());
        echo $file->getdata(true);
        exit();
    }
    
    
    protected function rename()
    {
        if (!isset(Vars::$get['page']) || !isset(Vars::$get['file'])) {
            throw new CommandException('パラメータが足りません。', $this);
        }
        
        $page = Page::getinstance(Vars::$get['page']);
        if ($page->isnull() || Vars::$get['file'] == '') {
            throw new CommandException('パラメータが正しくありません。', $this);
        }
        
        $smarty = $this->getSmarty();
        $smarty->assign('filename', Vars::$get['file']);
        $smarty->assign('pagename', $page->getpagename());
        $ret['title'] = '添付ファイル名の変更';
        $ret['pagename'] = $page->getpagename();
        if (!isset(Vars::$post['password']) || !isset(Vars::$post['newname']) || Vars::$post['newname'] == '') {
            $ret['body'] = $smarty->fetch('rename.tpl.htm');
        }
        else{
            if (md5(Vars::$post['password']) == ADMINPASS) {
                $r = Attach::getinstance($page)->rename(Vars::$get['file'], Vars::$post['newname']);
                if ($r) {
                    $ret['body'] = $smarty->fetch('rename_success.tpl.htm');
                }
                else{
                    $ret['body'] = $smarty->fetch('rename_failed.tpl.htm');
                }
            }
            else{
                $smarty->assign('newname', Vars::$post['newname']);
                $ret['body'] = $smarty->fetch('rename.tpl.htm');
            }
        }
        return $ret;
    }
    
    
    protected function delete()
    {
        if (!isset(Vars::$get['page']) || !isset(Vars::$get['file'])) {
            throw new CommandException('パラメータが足りません。', $this);
        }
        
        $page = Page::getinstance(Vars::$get['page']);
        if ($page->isnull() || Vars::$get['file'] == '') {
            throw new CommandException('パラメータが正しくありません。', $this);
        }
        
        $smarty = $this->getSmarty();
        $smarty->assign('filename', Vars::$get['file']);
        $smarty->assign('pagename', $page->getpagename());
        $ret['title'] = '添付ファイルの削除';
        $ret['pagename'] = $page->getpagename();
        if (!isset(Vars::$post['password'])) {
            $ret['body'] = $smarty->fetch('delete.tpl.htm');
        }
        else{
            if (md5(Vars::$post['password']) == ADMINPASS) {
                AttachedFile::getinstance(Vars::$get['file'], $page)->delete();
                $smarty->assign('filename', Vars::$get['file']);
                $ret['body'] = $smarty->fetch('delete_success.tpl.htm');
            }
            else{
                $smarty->assign('failed', true);
                $ret['body'] = $smarty->fetch('delete.tpl.htm');
            }
        }
        return $ret;
    }
    
    
    protected function show()
    {
        if (!isset(Vars::$get['page']) || !isset(Vars::$get['file'])) {
            throw new CommandException('パラメータが足りません。', $this);
        }
        
        $page = Page::getinstance(Vars::$get['page']);
        if ($page->isnull() || Vars::$get['file'] == '') {
            throw new CommandException('パラメータが正しくありません。', $this);
        }
        
        $smarty = $this->getSmarty();
        $smarty->assign('filename', Vars::$get['file']);
        $smarty->assign('pagename', $page->getpagename());
        $file = AttachedFile::getinstance(Vars::$get['file'], $page);
        $smarty->assign('size', $file->getsize());
        $smarty->assign('count', $file->getcount());
        $smarty->assign('timestamp', $file->gettimestamp());
        $smarty->assign('md5', md5($file->getdata()));
        $ret['title'] = $page->getpagename() . ' の添付ファイル ' . Vars::$get['file'];
        $ret['pagename'] = Vars::$get['page'];
        $ret['body'] = $smarty->fetch('show.tpl.htm');
        return $ret;
    }
    
    
    protected function listfile()
    {
        if (!isset(Vars::$get['page'])) {
            throw new CommandException('パラメータが足りません。', $this);
        }
        
        $page = Page::getinstance(Vars::$get['page']);
        if ($page->isnull()) {
            throw new CommandException('パラメータが正しくありません。', $this);
        }
        
        $smarty = $this->getSmarty();
        $smarty->assign('pagename', $page->getpagename());
        $smarty->assign('list', Attach::getinstance($page)->getlist());
        $ret['title'] = Vars::$get['page'] . ' の添付ファイル一覧';
        $ret['pagename'] = Vars::$get['page'];
        $ret['body'] = $smarty->fetch('list.tpl.htm');
        return $ret;
    }
    
    
    protected function listpage()
    {
        $db = DataBase::getinstance();
        $smarty = $this->getSmarty();
        
        $query = "SELECT DISTINCT pagename FROM attach ORDER BY pagename ASC";
        $result = $db->query($query);
        while($row = $db->fetch($result)) {
            $smarty->append('list', $row['pagename']);
        }
        $ret['title'] = '添付ファイルを持つページ一覧';
        $ret['body'] = $smarty->fetch('listpage.tpl.htm');
        return $ret;
    }
    
    
    protected function showform()
    {
        if (!isset(Vars::$get['page'])) {
            throw new CommandException('パラメータが足りません。', $this);
        }
        
        $page = Page::getinstance(Vars::$get['page']);
        if ($page->isnull()) {
            throw new CommandException('パラメータが正しくありません。', $this);
        }
        
        $smarty = $this->getSmarty();
        $smarty->assign('pagename', $page->getpagename());
        $smarty->assign('maxfilesize', $this->maxsize());
        $ret['body'] = $smarty->fetch('attach.tpl.htm');
        $ret['title'] = $page->getpagename() . ' への添付';
        $ret['pagename'] = $page->getpagename();
        return $ret;
    }
    
    
    protected function maxsize()
    {
        static $search = array('K', 'M');
        static $replace = array('000', '000000');
        $postmax = str_replace($search, $replace, ini_get('post_max_size'));
        $uploadmax = str_replace($search, $replace, ini_get('upload_max_filesize'));
        return min($postmax, $uploadmax, ATTACH_MAXSIZE);
    }
    
    
    public function update($show, $arg)
    {
        if ($arg == 'done') {
            $page = $this->getcurrentPage();
            $list = Attach::getinstance($page)->getlist();
            if ($list != array()) {
                $smarty = $this->getSmarty();
                $smarty->assign('attach', $list);
                $smarty->assign('pagename', $page->getpagename());
                $this->setbody($smarty->fetch('page.tpl.htm'));
            }
        }
    }
}
