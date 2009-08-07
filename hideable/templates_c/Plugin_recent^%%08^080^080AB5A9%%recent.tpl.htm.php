<?php /* Smarty version 2.6.9, created on 2009-08-08 03:29:17
         compiled from recent.tpl.htm */ ?>
<h2>Recent</h2>
<div id="plugin_recent">
<?php $_from = $this->_tpl_vars['list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['date'] => $this->_tpl_vars['datelist']):
?>
<h3 class="plugin_recent_date"><?php echo $this->_tpl_vars['date']; ?>
</h3>
<ul class="plugin_recent_page">
<?php $_from = $this->_tpl_vars['datelist']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
?>
<li><?php echo $this->_tpl_vars['item']; ?>
</li>
<?php endforeach; endif; unset($_from); ?>
</ul>
<?php endforeach; else: ?>
<div class="recentsubtitles">ページは１つもありません。</div>
<?php endif; unset($_from); ?>
</div>