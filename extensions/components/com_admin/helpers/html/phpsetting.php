<?php
/**
 * @version		$Id: phpsetting.php 20196 2011-01-09 02:40:25Z ian $
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Utility class working with phpsetting
 *
 * @package		Joomla.Administrator
 * @subpackage	com_admin
 * * * @since		1.0
 */
abstract class MolajoHTMLPhpSetting
{
	/**
	 * method to generate a boolean message for a value
	 *
	 * @param boolean $val is the value set?
	 *
	 * @return string html code
	 */
	public static function boolean($val)
	{
		if ($val) {
			return MolajoText::_('JON');
		}
		else {
			return MolajoText::_('JOFF');
		}
	}

	/**
	 * method to generate a boolean message for a value
	 *
	 * @param boolean $val is the value set?
	 *
	 * @return string html code
	 */
	public static function set($val)
	{
		if ($val) {
			return MolajoText::_('JYES');
		} else {
			return MolajoText::_('JNO');
		}
	}

	/**
	 * method to generate a string message for a value
	 *
	 * @param string $val a php ini value
	 *
	 * @return string html code
	 */
	public static function string($val)
	{
		if (empty($val)) {
			return MolajoText::_('JNONE');
		} else {
			return $val;
		}
	}

	/**
	 * method to generate an integer from a value
	 *
	 * @param string $val a php ini value
	 *
	 * @return string html code
	 */
	public static function integer($val)
	{
		return intval($val);
	}
}
