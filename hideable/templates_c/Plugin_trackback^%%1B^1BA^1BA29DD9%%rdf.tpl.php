<?php /* Smarty version 2.6.9, created on 2009-07-30 12:41:39
         compiled from rdf.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'rdf.tpl', 6, false),)), $this); ?>
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
    rdf:about="<?php echo $this->_tpl_vars['script']; ?>
?<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
"
    dc:identifier="<?php echo $this->_tpl_vars['script']; ?>
?<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
"
    dc:title="<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
"
    trackback:ping="<?php echo $this->_tpl_vars['script']; ?>
?plugin=trackback&amp;param=ping&amp;page=<?php echo ((is_array($_tmp=$this->_tpl_vars['pagename'])) ? $this->_run_mod_handler('escape', true, $_tmp, 'url') : smarty_modifier_escape($_tmp, 'url')); ?>
" />
</rdf:RDF>
-->