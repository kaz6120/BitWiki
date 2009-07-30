<?php /* Smarty version 2.6.9, created on 2009-07-30 12:41:39
         compiled from recent.tpl.htm */ ?>
<div class="plugin_recent">
<?php $_from = $this->_tpl_vars['list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['date'] => $this->_tpl_vars['datelist']):
?>
	<span class="plugin_recent_date"><?php echo $this->_tpl_vars['date']; ?>
</span>
	<div class="plugin_recent_page">
	<?php $_from = $this->_tpl_vars['datelist']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['item']):
?>
		<?php echo $this->_tpl_vars['item']; ?>
<br />
	<?php endforeach; endif; unset($_from); ?>
	</div>
<?php endforeach; else: ?>
	<div class="recentsubtitles">ページは１つもありません。</div>
<?php endif; unset($_from); ?>
</div>