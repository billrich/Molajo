<?php
/**
 * @version		$Id: default_phpinfo.php 20196 2011-01-09 02:40:25Z ian $
 * @package		Joomla.Administrator
 * @subpackage	com_admin
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
?>
<fieldset class="adminform">
	<legend><?php echo MolajoText::_('COM_ADMIN_PHP_INFORMATION'); ?></legend>
	<?php echo $this->php_info;?>
</fieldset>