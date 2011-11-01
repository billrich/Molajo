<?php
/**
 * @version		$Id: view.html.php 21320 2011-05-11 01:01:37Z dextercowley $
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * HTML View class for the Media component
 *
 * @package		Joomla.Administrator
 * @subpackage	com_media
 * @since 1.0
 */
class MediaViewMedia extends JView
{
	function display($tpl = null)
	{
		$app	= MolajoFactory::getApplication();
		$config = JComponentHelper::getParams('com_media');

		$lang	= MolajoFactory::getLanguage();

		$style = $app->getUserStateFromRequest('media.list.layout', 'layout', 'thumbs', 'word');

		$document = MolajoFactory::getDocument();
		$document->setBuffer($this->loadTemplate('navigation'), 'modules', 'submenu');

		MolajoHTML::_('behavior.framework', true);

		MolajoHTML::_('script','media/mediamanager.js', true, true);
		MolajoHTML::_('stylesheet','media/mediamanager.css', array(), true);
		if ($lang->isRTL()) :
			MolajoHTML::_('stylesheet','media/mediamanager_rtl.css', array(), true);
		endif;

		MolajoHTML::_('behavior.modal');
		$document->addScriptDeclaration("
		window.addEvent('domready', function() {
			document.preview = SqueezeBox;
		});");

		MolajoHTML::_('script','system/mootree.js', true, true, false, false);
		MolajoHTML::_('stylesheet','system/mootree.css', array(), true);
		if ($lang->isRTL()) :
			MolajoHTML::_('stylesheet','media/mootree_rtl.css', array(), true);
		endif;

		if ($config->get('enable_flash', 1)) {
			$fileTypes = $config->get('upload_extensions', 'bmp,gif,jpg,png,jpeg');
			$types = explode(',', $fileTypes);
			$displayTypes = '';		// this is what the user sees
			$filterTypes = '';		// this is what controls the logic
			$firstType = true;
			foreach($types AS $type) {
				if(!$firstType) {
					$displayTypes .= ', ';
					$filterTypes .= '; ';
				} else {
					$firstType = false;
				}
				$displayTypes .= '*.'.$type;
				$filterTypes .= '*.'.$type;
			}
			$typeString = '{ \''.MolajoText::_('COM_MEDIA_FILES','true').' ('.$displayTypes.')\': \''.$filterTypes.'\' }';

			MolajoHTML::_('behavior.uploader', 'upload-flash',
				array(
					'onBeforeStart' => 'function(){ Uploader.setOptions({url: document.id(\'uploadForm\').action + \'&folder=\' + document.id(\'mediamanager-form\').folder.value}); }',
					'onComplete' 	=> 'function(){ MediaManager.refreshFrame(); }',
					'targetURL' 	=> '\\document.id(\'uploadForm\').action',
					'typeFilter' 	=> $typeString,
					'fileSizeMax'	=> (int) ($config->get('upload_maxsize',0) * 1024 * 1024),
				)
			);
		}

		if (DS == '\\')
		{
			$base = str_replace(DS,"\\\\",COM_MEDIA_BASE);
		} else {
			$base = COM_MEDIA_BASE;
		}

		$js = "
			var basepath = '".$base."';
			var viewstyle = '".$style."';
		" ;
		$document->addScriptDeclaration($js);

		/*
		 * Display form for FTP credentials?
		 * Don't set them here, as there are other functions called before this one if there is any file write operation
		 */
		$ftp = !JClientHelper::hasCredentials('ftp');

		$session	= MolajoFactory::getSession();
		$state		= $this->get('state');
		$this->assignRef('session', $session);
		$this->assignRef('config', $config);
		$this->assignRef('state', $state);
		$this->assign('require_ftp', $ftp);
		$this->assign('folders_id', ' id="media-tree"');
		$this->assign('folders', $this->get('folderTree'));

		// Set the toolbar
		$this->addToolbar();

		parent::display($tpl);
		echo MolajoHTML::_('behavior.keepalive');
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.0
	 */
	protected function addToolbar()
	{
		// Get the toolbar object instance
		$bar = MolajoToolbar::getInstance('toolbar');
		$user = MolajoFactory::getUser();

		// Set the titlebar text
		MolajoToolbarHelper::title(MolajoText::_('COM_MEDIA'), 'mediamanager.png');

		// Add a delete button
		if ($user->authorise('core.delete','com_media'))
		{
			$title = MolajoText::_('JTOOLBAR_DELETE');
			$dhtml = "<a href=\"#\" onclick=\"MediaManager.submit('folder.delete')\" class=\"toolbar\">
						<span class=\"icon-32-delete\" title=\"$title\"></span>
						$title</a>";
			$bar->appendButton('Custom', $dhtml, 'delete');
			MolajoToolbarHelper::divider();
		}
		// Add a delete button
		if ($user->authorise('core.admin','com_media'))
		{
			MolajoToolbarHelper::preferences('com_media', 450, 800, 'JToolbar_Options', '', 'window.location.reload()');
			MolajoToolbarHelper::divider();
		}
		MolajoToolbarHelper::help('JHELP_CONTENT_MEDIA_MANAGER');
	}

	function getFolderLevel($folder)
	{
		$this->folders_id = null;
		$txt = null;
		if (isset($folder['children']) && count($folder['children'])) {
			$tmp = $this->folders;
			$this->folders = $folder;
			$txt = $this->loadTemplate('folders');
			$this->folders = $tmp;
		}
		return $txt;
	}
}