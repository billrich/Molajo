<?php
/**
 * @package     Molajo
 * @subpackage  Helper
 * @copyright   Copyright (C) 2011 Babs Gösgens. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

/**
 * Molajo Installation Helper
 *
 * @package		Molajo
 * @subpackage	Helper
 * @since       1.0
 */
class MolajoInstallationHelper extends MolajoHelper
{
	/**
	 * Return the application option string [main component].
	 *
	 * @return	string		Option.
	 * @since	1.0
	 */
	public static function findOption()
	{
		JRequest::setVar('option', 'com_installer');
		return 'com_installer';
	}
}