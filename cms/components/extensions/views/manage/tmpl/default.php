<?php
/**
 * @version        $Id: default.php 21595 2011-06-21 02:51:29Z dextercowley $
 * @package        Joomla.Administrator
 * @subpackage    installer
 * @copyright    Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * * * @since        1.0
 */

// no direct access
defined('_JEXEC') or die;

MolajoHTML::_('behavior.multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo MolajoRouteHelper::_('index.php?option=installer&view=manage');?>" method="post"
      name="adminForm" id="adminForm">
    <?php if ($this->showMessage) : ?>
    <?php echo $this->loadTemplate('message'); ?>
    <?php endif; ?>

    <?php if ($this->ftp) : ?>
    <?php echo $this->loadTemplate('ftp'); ?>
    <?php endif; ?>

    <?php echo $this->loadTemplate('filter'); ?>

    <?php if (count($this->items)) : ?>
    <table class="adminlist">
        <thead>
        <tr>
            <th width="20">
                <input type="checkbox" name="checkall-toggle" value=""
                       title="<?php echo MolajoTextHelper::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
            </th>
            <th class="nowrap">
                <?php echo MolajoHTML::_('grid.sort', 'INSTALLER_HEADING_NAME', 'name', $listDirn, $listOrder); ?>
            </th>
            <th>
                <?php echo MolajoHTML::_('grid.sort', 'INSTALLER_HEADING_LOCATION', 'application_id', $listDirn, $listOrder); ?>
            </th>
            <th width="10%" class="center">
                <?php echo MolajoHTML::_('grid.sort', 'JSTATUS', 'enabled', $listDirn, $listOrder); ?>
            </th>
            <th>
                <?php echo MolajoHTML::_('grid.sort', 'INSTALLER_HEADING_TYPE', 'type', $listDirn, $listOrder); ?>
            </th>
            <th width="10%" class="center">
                <?php echo MolajoTextHelper::_('MOLAJOVERSION'); ?>
            </th>
            <th width="10%">
                <?php echo MolajoTextHelper::_('JDATE'); ?>
            </th>
            <th width="15%">
                <?php echo MolajoTextHelper::_('JAUTHOR'); ?>
            </th>
            <th>
                <?php echo MolajoHTML::_('grid.sort', 'INSTALLER_HEADING_FOLDER', 'folder', $listDirn, $listOrder); ?>
            </th>
            <th width="10">
                <?php echo MolajoHTML::_('grid.sort', 'INSTALLER_HEADING_ID', 'extension_id', $listDirn, $listOrder); ?>
            </th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <td colspan="11">
                <?php echo $this->pagination->getListFooter(); ?>
            </td>
        </tr>
        </tfoot>
        <tbody>
            <?php foreach ($this->items as $i => $item): ?>
        <tr class="row<?php echo $i % 2; if ($item->protected) echo ' protected';?>">
            <td>
                <?php echo MolajoHTML::_('grid.id', $i, $item->extension_id); ?>
            </td>
            <td>
					<span class="bold hasTip"
                          title="<?php echo htmlspecialchars($item->name . '::' . $item->description); ?>">
						<?php echo $item->name; ?>
					</span>
            </td>
            <td class="center">
                <?php echo $item->application; ?>
            </td>
            <td class="center">
                <?php if (!$item->element) : ?>
                <strong>X</strong>
                <?php else : ?>
                <?php echo MolajoHTML::_('jgrid.published', $item->enabled, $i, 'manage.'); ?>
                <?php endif; ?>
            </td>
            <td class="center">
                <?php echo MolajoTextHelper::_('INSTALLER_TYPE_' . $item->type); ?>
            </td>
            <td class="center">
                <?php echo @$item->version != '' ? $item->version : '&#160;'; ?>
            </td>
            <td class="center">
                <?php echo @$item->creationDate != '' ? $item->creationDate : '&#160;'; ?>
            </td>
            <td class="center">
					<span class="editlinktip hasTip"
                          title="<?php echo addslashes(htmlspecialchars(MolajoTextHelper::_('INSTALLER_AUTHOR_INFORMATION') . '::' . $item->author_info)); ?>">
						<?php echo @$item->author != '' ? $item->author : '&#160;'; ?>
					</span>
            </td>
            <td class="center">
                <?php echo @$item->folder != '' ? $item->folder
                    : MolajoTextHelper::_('INSTALLER_TYPE_NONAPPLICABLE'); ?>
            </td>
            <td>
                <?php echo $item->extension_id ?>
            </td>
        </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
    <?php echo MolajoHTML::_('form.token'); ?>
</form>
