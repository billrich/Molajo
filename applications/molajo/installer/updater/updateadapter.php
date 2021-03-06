<?php
/**
 * @package     Molajo
 * @subpackage  Application
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @copyright   Copyright (C) 2011 Individual Molajo Contributors. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

/**
 * UpdateAdapter class.
 *
 * @package     Joomla.Platform
 * @subpackage  Updater
 * @since       11.1
 */
class MolajoUpdateadapter extends MolajoAdapterInstance
{
    /**
     * @var    string
     * @since  11.1
     */
    protected $xml_parser;

    /**
     * @var    array
     * @since 11.1
     */
    protected $_stack = array('base');

    /**
     * ID of update site
     *
     * @var    string
     * @since  11.1
     */
    protected $_extension_site_id = 0;

    /**
     * Columns in the extensions table to be updated
     *
     * @var    array
     * @since  11.1
     */
    protected $_updatecols = array('NAME', 'ELEMENT', 'TYPE', 'FOLDER', 'CLIENT_ID', 'VERSION', 'DESCRIPTION');

    /**
     * Gets the reference to the current direct parent
     *
     * @return  object
     *
     * @since   11.1
     */
    protected function _getStackLocation()
    {
        return implode('->', $this->_stack);
    }

    /**
     * Gets the reference to the last tag
     *
     * @return  object
     *
     * @since   11.1
     */
    protected function _getLastTag()
    {
        return $this->_stack[count($this->_stack) - 1];
    }
}
