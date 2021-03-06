<?php
/**
 * @package     Molajo
 * @subpackage  Create System Plugin
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die('Restricted access');

/**
 * Molajo System Plugin
 */
class plgSystemCreate extends MolajoPlugin
{

    /**
     * System Event: onAfterInitialise
     *
     * @return    string
     */
    public function __construct(& $subject, $config = array())
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    /**
     * System Event: onAfterInitialise
     *
     * Load Installer Overrides for Core Submenu and Enabled New Create Submenu
     *
     * @return    string
     */
    function onAfterInitialise()
    {
        if ($this->parameters->def('enable_installer_create_extensions', 1) == 1
            && MolajoFactory::getApplication()->getName() == 'administrator'
            && (JRequest::getCmd('option') == 'installer' || JRequest::getCmd('option') == 'plugins')
        ) {

            define('INSTALLER_OVERRIDES', dirname(__FILE__));
            require_once INSTALLER_OVERRIDES . '/includes/include.php';
        }
    }
}