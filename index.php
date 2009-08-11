<?php
/*
 * BitWiki index.php
 *
 * based on KinoWiki/index.php,v 1.2 2005/06/29 10:54:02 youka
 *
 * @version 9.8.11
 */

/**
 * BEGIN SETTINGS 
 */

// Site name
define('SITENAME', 'BitWiki');

// Name of the top page
define('DEFAULTPAGE', 'FrontPage');

// WikiFram ID
define('WIKIID', 'index');

// HTML template file
define('SKINFILE', 'default.tpl.html');

// Theme to use.
define('THEME', 'default');

// Admin Password. Use md() or md5 hashed code.
define('ADMINPASS', md5('password'));

// Mail settings
define('MAIL_USE', false);	  // Use mail:true, Don't use mail:false
define('MAIL_DIFF', true);	  // Send diff only:true, diff and whole text:false
define('MAIL_SMTP', 'localhost'); // Windows only option
define('MAIL_SMTP_PORT', 25);	  // WIndows only option
define('MAIL_FROM', 'yourmail@example.com');
define('MAIL_TO', 'yourmail@example.com');


/*
 * SETTINGS DETAIL
 */

// Max attachiment size
define('ATTACH_MAXSIZE', 2000000);

// Fuzzy link settings
define('FUZZYLINK_SPELLMISSMINSIZE', 5);

/*
 * DIRECTORY SETTINGS 
 */

// Core php files
define('HIDEABLE_DIR', './hideable/');

// SQLite database file 
// (Please don't forget to set permissions read/writable
define('DATA_DIR', HIDEABLE_DIR . 'data/');

// Command files 
define('COMMAND_DIR', './command/');

// Plugins
define('PLUGIN_DIR', './plugin/');

// View files
// HTML
define('SKIN_DIR', './theme/');
// CSS
define('THEME_DIR', './theme/');

// Smarty compiled files 
// (Please don't forget to set permissions read/writeable)
define('COMPILEDTPL_DIR', HIDEABLE_DIR . 'templates_c/');


/*
 * END SETTINGS 
 */



require_once(HIDEABLE_DIR . 'bitwiki.inc.php');

BitWiki::main();
