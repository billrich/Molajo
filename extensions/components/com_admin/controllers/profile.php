<?php
/**
 * @version		$Id: profile.php 21672 2011-06-24 22:04:46Z chdemko $
 * @package		Joomla.Administrator
 * @subpackage	com_admin
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * User profile controller class.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_admin
 * * * * @since		1.0
 */
class AdminControllerProfile extends JControllerForm
{
	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param	array	An array of input data.
	 * @param	string	The name of the key for the primary key.
	 *
	 * @return	boolean
	 * @since	1.0
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		return isset($data['id']) && $data['id'] == MolajoFactory::getUser()->id;
	}

	/**
	 * Overrides parent save method to check the submitted passwords match.
	 *
	 * @return	mixed	Boolean or JError.
	 * @since	1.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$data = JRequest::getVar('jform', array(), 'post', 'array');

		// TODO: JForm should really have a validation handler for this.
		if (isset($data['password']) && isset($data['password2'])) {
			// Check the passwords match.
			if ($data['password'] != $data['password2']) {
				$this->setMessage(MolajoText::_('MOLAJO_USER_ERROR_PASSWORD_NOT_MATCH'), 'warning');
				$this->setRedirect(MolajoRoute::_('index.php?option=com_admin&view=profile&layout=edit&id='.MolajoFactory::getUser()->id, false));
				return false;
			}

			unset($data['password2']);
		}

		$return = parent::save();

		if ($this->getTask() != 'apply') {
			// Redirect to the main page.
			$this->setRedirect(MolajoRoute::_('index.php', false));
		}

		return $return;
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param	string	$key	The name of the primary key of the URL variable.
	 *
	 * @return	Boolean	True if access level checks pass, false otherwise.
	 * @since	1.0
	 */
	public function cancel($key = null)
	{
		$return = parent::cancel($key);

		// Redirect to the main page.
		$this->setRedirect(MolajoRoute::_('index.php', false));

		return $return;
	}
}