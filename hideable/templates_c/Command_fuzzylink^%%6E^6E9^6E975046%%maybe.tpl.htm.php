<?php /* Smarty version 2.6.9, created on 2009-08-08 03:29:17
         compiled from maybe.tpl.htm */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'makelink', 'maybe.tpl.htm', 6, false),)), $this); ?>
<?php if (isset ( $this->_tpl_vars['pagelist'] )): ?>
<div class="fuzzylink_maybe">
	もしかして
	<ul>
		<?php $_from = $this->_tpl_vars['pagelist']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['pagename']):
?>
			<li><?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('makelink', true, $_tmp) : MySmarty::makelink($_tmp)); ?>
</li>
		<?php endforeach; endif; unset($_from); ?>
	</ul>
</div>
<?php endif; ?>