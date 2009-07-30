<?php /* Smarty version 2.6.9, created on 2009-07-31 00:20:35
         compiled from default.tpl.htm */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'default.tpl.htm', 12, false),array('modifier', 'strip_tags', 'default.tpl.htm', 21, false),array('modifier', 'time2date', 'default.tpl.htm', 92, false),array('modifier', 'old', 'default.tpl.htm', 92, false),array('modifier', 'tinyurl', 'default.tpl.htm', 93, false),)), $this); ?>
<?php echo '<?xml'; ?>
 version="1.0" encoding="UTF-8"<?php echo '?>'; ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
<head>
<link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['theme_url']; ?>
default.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['theme_url']; ?>
parsed.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=csscollector" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['theme_url'];  echo $this->_tpl_vars['theme']; ?>
/<?php echo $this->_tpl_vars['theme']; ?>
.css" />
<link rel="alternate" type="application/xml+rss" title="RSS1.0" href="<?php echo $this->_tpl_vars['script']; ?>
?plugin=rss10" />
<title><?php echo ((is_array($_tmp=$this->_tpl_vars['sitename'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
 - <?php echo ((is_array($_tmp=$this->_tpl_vars['title'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</title>
<?php $_from = $this->_tpl_vars['headeroption']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
?>
	<?php echo $this->_tpl_vars['item']; ?>

<?php endforeach; endif; unset($_from); ?>
</head>

<body>

<div class="header">
<h1><?php echo ((is_array($_tmp=$this->_tpl_vars['sitename'])) ? $this->_run_mod_handler('strip_tags', true, $_tmp) : smarty_modifier_strip_tags($_tmp)); ?>
</h1>
 <div class="globalmenu">
  <ul>
   <li><a href="<?php echo $this->_tpl_vars['script']; ?>
">トップ</a></li>
   <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=new<?php if (isset ( $this->_tpl_vars['pagename'] )): ?>&amp;page=<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url'));  endif; ?>">新規</a></li>
   <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=list">一覧</a></li>
   <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=search">検索</a></li>
   <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?%E3%83%98%E3%83%AB%E3%83%97">ヘルプ</a></li>
  </ul>
 </div>
</div>

<?php if (isset ( $this->_tpl_vars['pagename'] )): ?>
<div class="pagemenu">
 <ul>
  <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=edit&amp;page=<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
">編集</a></li>
  <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=diff&amp;page=<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
">差分</a></li>
  <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=backup&amp;param=list&amp;page=<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
">バックアップ</a></li>
  <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=rename&amp;page=<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
">リネーム</a></li>
  <li><a href="<?php echo $this->_tpl_vars['script']; ?>
?cmd=attach&amp;page=<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
">添付ファイル</a></li>
 </ul>
</div>
<?php endif; ?>

<div class="main">


<h2 class="title"><?php echo ((is_array($_tmp=$this->_tpl_vars['title'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</h2>
<?php if (isset ( $this->_tpl_vars['command']['fuzzylink'] )): ?>
<div class="floatbox">
<?php echo $this->_tpl_vars['command']['fuzzylink']; ?>

</div>
<?php endif; ?>

<div class="body">
<?php echo $this->_tpl_vars['body']; ?>

</div>


<?php if (isset ( $this->_tpl_vars['command']['footnote'] )): ?><div class="option"><?php echo $this->_tpl_vars['command']['footnote']; ?>
</div><?php endif;  if (isset ( $this->_tpl_vars['command']['attach'] )): ?><div class="option"><?php echo $this->_tpl_vars['command']['attach']; ?>
</div><?php endif;  if (isset ( $this->_tpl_vars['command']['backlink'] )): ?><div class="option"><?php echo $this->_tpl_vars['command']['backlink']; ?>
</div><?php endif;  if (isset ( $this->_tpl_vars['plugin']['trackback'] )): ?><div class="option"><?php echo $this->_tpl_vars['plugin']['trackback']; ?>
</div><?php endif;  if (isset ( $this->_tpl_vars['plugin']['referrer'] )): ?><div class="option"><?php echo $this->_tpl_vars['plugin']['referrer']; ?>
</div><?php endif;  $_from = $this->_tpl_vars['command']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
 if ($this->_tpl_vars['item'] != $this->_tpl_vars['command']['sidebar'] && $this->_tpl_vars['item'] != $this->_tpl_vars['command']['footnote'] && $this->_tpl_vars['item'] != $this->_tpl_vars['command']['attach'] && $this->_tpl_vars['item'] != $this->_tpl_vars['command']['backlink'] && $this->_tpl_vars['item'] != $this->_tpl_vars['command']['fuzzylink']): ?>
<div class="option">
<?php echo $this->_tpl_vars['item']; ?>

</div>
<?php endif;  endforeach; endif; unset($_from);  $_from = $this->_tpl_vars['plugin']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
 if ($this->_tpl_vars['item'] != $this->_tpl_vars['plugin']['trackback'] && $this->_tpl_vars['item'] != $this->_tpl_vars['plugin']['referrer']): ?>
<div class="option">
<?php echo $this->_tpl_vars['item']; ?>

</div>
<?php endif;  endforeach; endif; unset($_from); ?>
</div>


<div class="sidebar">
<?php echo $this->_tpl_vars['command']['sidebar']; ?>

</div>

<div class="footer">
<?php if (isset ( $this->_tpl_vars['lastmodified'] )): ?>Last-modified: <?php echo ((is_array($_tmp=$this->_tpl_vars['lastmodified'])) ? $this->_run_mod_handler('time2date', true, $_tmp) : MySmarty::time2date($_tmp)); ?>
&nbsp;&nbsp;(<?php echo ((is_array($_tmp=$this->_tpl_vars['lastmodified'])) ? $this->_run_mod_handler('old', true, $_tmp) : MySmarty::old($_tmp)); ?>
)<br /><?php endif;  if (isset ( $this->_tpl_vars['pagename'] )):  echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('tinyurl', true, $_tmp) : MySmarty::tinyurl($_tmp)); ?>
<br /><?php endif; ?>
Running Time: <?php echo $this->_tpl_vars['runningtime']; ?>
sec.<br />
<a href="http://kinowiki.net/">KinoWiki <?php echo $this->_tpl_vars['version']; ?>
</a>
</div>

<?php $_from = $this->_tpl_vars['option']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
 echo $this->_tpl_vars['item']; ?>

<?php endforeach; endif; unset($_from); ?>

</body>
</html>