<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Trackback_SpamCheck_DNSBL.
 *
 * This spam detection module for Services_Trackback utilizes DNS
 * blacklists for detection of hosts used for spamming.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Webservices
 * @package    Trackback
 * @author     Tobias Schlitt <toby@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: DNSBL.php,v 1.1 2005/12/24 00:13:27 youka Exp $
 * @link       http://pear.php.net/package/Services_Trackback
 * @since      File available since Release 0.5.0
 */
    
    // {{{ require_once

/**
 * Load PEAR error handling
 */
require_once 'PEAR.php';
   
/**
 * Load Net_DNSBL for spam cheching
 */
require_once 'Net/DNSBL.php';

/**
 * Load SpamCheck base class
 */
require_once 'Services/Trackback/SpamCheck.php';

    // }}}

/**
 * DNSBL
 * Module for spam detecion using DNSBL.
 *
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @category   Webservices
 * @package    Trackback
 * @author     Tobias Schlitt <toby@php.net>
 * @copyright  1997-2005 The PHP Group
 * @version    Release: 0.5.0
 * @link       http://pear.php.net/package/Services_Trackback
 * @since      0.5.0
 * @access     public
 */
class Services_Trackback_SpamCheck_DNSBL extends Services_Trackback_SpamCheck {

    // {{{ _options
    
    /**
     * Options for the SpamProtection.
     *
     * @var array
     * @since 0.5.0
     * @access protected
     */
    var $_options = array(
        'continuose'    => false,
        'sources'       => array(
            'bl.spamcop.net'
        ),
    );

    // }}}
    // {{{ _dnsbl
    
    /**
     * The Net_DNSBL object for checking.
     *
     * @var object(Net_DNSBL)
     * @since 0.5.0
     * @access protected
     */
    var $_dnsbl;

    // }}}
    // {{{ Services_Trackback_SpamCheck_DNSBL()
    
    /**
     * Constructor.
     * Create a new instance of the DNSBL spam protection module.
     *
     * @since 0.5.0
     * @access public
     * @param array $options An array of options for this spam protection module. General options are
     *                       'continuose':  Whether to continue checking more sources, if a match has been found.
     *                       'sources':     List of blacklist nameservers. Indexed.
     * @return object(Services_Trackback_SpamCheck_DNSBL) The newly created SpamCheck object.
     */
    function Services_Trackback_SpamCheck_DNSBL($options = null)
    {
        if (is_array($options)) {
            foreach ($options as $key => $val) {
                $this->_options[$key] = $val;
            }
        }
        $this->_dnsbl = new Net_DNSBL();
    }

    // }}}
    // {{{ _checkSource
    
    function _checkSource(&$source, $trackback)
    {
        $this->_dnsbl->setBlacklists(array($source));
        return $this->_dnsbl->isListed($trackback->get('host'));
    }
    
    // }}}

}
