<?php
/**
 * @version		$Id: view.html.php 21655 2011-06-23 05:43:24Z chdemko $
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * The HTML Menus Menu Item View.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_menus
 * * * @since		1.0
 */
class MenusViewItem extends JView
{
	protected $form;
	protected $item;
	protected $modules;
	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->form		= $this->get('Form');
		$this->item		= $this->get('Item');
		$this->modules	= $this->get('Modules');
		$this->state	= $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		parent::display($tpl);
		$this->addToolbar();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.0
	 */
	protected function addToolbar()
	{
		JRequest::setVar('hidemainmenu', true);

		$user		= MolajoFactory::getUser();
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		$canDo		= MenusHelper::getActions($this->state->get('filter.parent_id'));

		MolajoToolbarHelper::title(MolajoText::_($isNew ? 'COM_MENUS_VIEW_NEW_ITEM_TITLE' : 'COM_MENUS_VIEW_EDIT_ITEM_TITLE'), 'menu-add');

		// If a new item, can save the item.  Allow users with edit permissions to apply changes to prevent returning to grid.
		if ($isNew && $canDo->get('core.create')) {
			if ($canDo->get('core.edit')) {
				MolajoToolbarHelper::apply('item.apply');
			}
			MolajoToolbarHelper::save('item.save');
		}

		// If not checked out, can save the item.
		if (!$isNew && !$checkedOut && $canDo->get('core.edit')) {
			MolajoToolbarHelper::apply('item.apply');
			MolajoToolbarHelper::save('item.save');
		}

		// If the user can create new items, allow them to see Save & New
		if ($canDo->get('core.create')) {
			MolajoToolbarHelper::save2new('item.save2new');
		}

		// If an existing item, can save to a copy only if we have create rights.
		if (!$isNew && $canDo->get('core.create')) {
			MolajoToolbarHelper::save2copy('item.save2copy');
		}

		if ($isNew)  {
			MolajoToolbarHelper::cancel('item.cancel');
		} else {
			MolajoToolbarHelper::cancel('item.cancel', 'JTOOLBAR_CLOSE');
		}

		MolajoToolbarHelper::divider();

		// Get the help information for the menu item.
		$lang = MolajoFactory::getLanguage();

		$help = $this->get('Help');
		if ($lang->hasKey($help->url)) {
			$debug = $lang->setDebug(false);
			$url = MolajoText::_($help->url);
			$lang->setDebug($debug);
		}
		else {
			$url = $help->url;
		}
		MolajoToolbarHelper::help($help->key, $help->local, $url);
	}
}