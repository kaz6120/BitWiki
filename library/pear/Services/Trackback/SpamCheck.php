<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Trackback_SpamCheck.
 *
 * This is the base class for Services_Trackback spamchecks. Since PHP4
 * lacks abstract class support, this class acts like a virtual abstract class. 
 * Each SpamCheck implementation has to extend this class and implement all of it's
 * abstract methods.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @abstract
 * @category   Webservices
 * @package    Trackback
 * @author     Tobias Schlitt <toby@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: SpamCheck.php,v 1.1 2005/12/24 00:13:27 youka Exp $
 * @link       http://pear.php.net/package/Services_Trackback
 * @since      File available since Release 0.5.0
 */
    
    // {{{ require_once

/**
 * Load PEAR error handling
 */

require_once 'PEAR.php';
    
    // }}}

/**
 * SpamCheck
 * Base class for Services_Trackback spam protection modules.
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
class Services_Trackback_SpamCheck {

    // {{{ _options
    /**
     * Options for the spam check module. General and module specific.
     *
     * @var array
     * @since 0.5.0
     * @access protected
     */
    var $_options = array(
        'continuose'    => false,
        'sources'       => array(),
    );

    // }}}
    // {{{ _results
    
    /**
     * Array of results, indexed analogue to the 'sources' option (boolean result value per source).
     *
     * @var array
     * @access protected
     */
    var $_results = array();

    // }}}
    // {{{ create()
    
    /**
     * Factory.
     * Create a new instance of a spam protection module.
     *
     * @since 0.5.0
     * @static
     * @access public
     * @param array $options An array of options for this spam protection module. General options are
     *                       'continuose':  Whether to continue checking more sources, if a match has been found.
     *                       'sources':     List of different sources for this module to check (eg. blacklist URLs,
     *                                      word arrays,...).
     *                       All further options depend on the specific module.
     * @return object(Services_Trackback_SpamCheck) The newly created SpamCheck object.
     */
    function &create($type, $options = null)
    {
        $filename = 'Services/Trackback/SpamCheck/' . $type . '.php';
        $classname = 'Services_Trackback_SpamCheck_' . $type;

        @include_once $filename;
        if (!class_exists($classname)) {
            return PEAR::raiseError('SpamCheck ' . $type . ' not found.');
        }
        
        return new $classname(@$options);
    }
    
    // }}}
    // {{{ check()

    /**
     * Check for spam using this module.
     * This method is utilized by a Services_Trackback object to check for spam. Generally this method
     * may not be overwritten, but it can be, if necessary. This method calls the _checkSource() method
     * for each source defined in the $_options array (depending on the 'continuose' option), saves the 
     * results and returns the spam status determined by the check.
     *
     * @since 0.5.0
     * @access public
     * @return bool Whether the checked object is spam or not.
     */
    function check($trackback)
    {
        $this->reset();
        $spam = false;
        foreach ($this->_options['sources'] as $id => $source) {
            if ($spam && !$this->_options['continuose']) {
                // We already found spam and shall not continue
                $this->_results[$id] = false;
            } else {
                $this->_results[$id] = $this->_checkSource($this->_options['sources'][$id], $trackback);
                $spam = ($spam || $this->_results[$id]);
            }
        }
        return $spam;
    }
    // }}}
    // {{{ getResults()
    
    /**
     * Get spam check results.
     * Receive the results determined by the spam check.
     *
     * @since 0.5.0
     * @access public
     * @return array Array of specific spam check results.
     */
    function getResults()
    {
        return $this->_results;
    }
    
    // }}}
    // {{{ reset()
    
    /**
     * Reset results.
     * Reset results to reuse SpamCheck.
     *
     * @since 0.5.0
     * @static
     * @access public
     * @return null
     */
    function reset()
    {
        $this->_results = array();
    }

    // }}}
    // {{{ _checkSource()

    /**
     * Check a specific source if a trackback has to be considered spam.
     * Check a specific source if a trackback has to be considered spam.
     *
     * @since 0.5.0
     * @access protected
     * @abstract
     * @param mixed $source Element of the _sources array to check.
     * @param object(Services_Trackback) The trackback to check.
     * @return bool True if trackback is spam, false, if not, PEAR_Error on error. 
     */
    function _checkSource($source, $trackback)
    {
        return PEAR::raiseError('Method not implemented.', -1);
    }
    
    // }}}

}
