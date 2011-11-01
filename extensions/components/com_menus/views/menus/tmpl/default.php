<?php
/**
 * @version		$Id: default.php 21595 2011-06-21 02:51:29Z dextercowley $
 * @package		Joomla.Administrator
 * @subpackage	com_menus
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
MolajoHTML::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Load the tooltip behavior.
MolajoHTML::_('behavior.tooltip');
MolajoHTML::_('behavior.multiselect');

$uri		= MolajoFactory::getUri();
$return		= base64_encode($uri);
$user		= MolajoFactory::getUser();
$userId		= $user->get('id');
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task != 'menus.delete' || confirm('<?php echo MolajoText::_('COM_MENUS_MENU_CONFIRM_DELETE',true);?>')) {
			Joomla.submitform(task);
		}
	}
</script>
<form action="<?php echo MolajoRoute::_('index.php?option=com_menus&view=menus');?>" method="post" name="adminForm" id="adminForm">
	<table class="adminlist">
		<thead>
			<tr>
				<th width="1%" rowspan="2">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo MolajoText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th rowspan="2">
					<?php echo MolajoHTML::_('grid.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
				</th>
				<th width="30%" colspan="3">
					<?php echo MolajoText::_('COM_MENUS_HEADING_NUMBER_MENU_ITEMS'); ?>
				</th>
				<th width="20%" rowspan="2">
					<?php echo MolajoText::_('COM_MENUS_HEADING_LINKED_MODULES'); ?>
				</th>
				<th width="1%" class="nowrap" rowspan="2">
					<?php echo MolajoHTML::_('grid.sort',  'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
				</th>
			</tr>
			<tr>
				<th width="10%">
					<?php echo MolajoText::_('COM_MENUS_HEADING_PUBLISHED_ITEMS'); ?>
				</th>
				<th width="10%">
					<?php echo MolajoText::_('COM_MENUS_HEADING_UNPUBLISHED_ITEMS'); ?>
				</th>
				<th width="10%">
					<?php echo MolajoText::_('COM_MENUS_HEADING_TRASHED_ITEMS'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="15">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
			$canCreate	= $user->authorise('core.create',		'com_menus');
			$canEdit	= $user->authorise('core.edit',			'com_menus');
			$canChange	= $user->authorise('core.edit.state',	'com_menus');
		?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="center">
					<?php echo MolajoHTML::_('grid.id', $i, $item->id); ?>
				</td>
				<td>
					<a href="<?php echo MolajoRoute::_('index.php?option=com_menus&view=items&menu_id='.$item->menu_id) ?> ">
						<?php echo $this->escape($item->title); ?></a>
					<p class="smallsub">(<span><?php echo MolajoText::_('COM_MENUS_MENU_MENUTYPE_LABEL') ?></span>
						<?php if ($canEdit) : ?>
							<?php echo '<a href="'.MolajoRoute::_('index.php?option=com_menus&task=menu.edit&id='.$item->id).' title='.$this->escape($item->description).'">'.
							$this->escape($item->menu_id).'</a>'; ?>)
						<?php else : ?>
							<?php echo $this->escape($item->menu_id)?>)
						<?php endif; ?>
					</p>
				</td>
				<td class="center btns">
					<a href="<?php echo MolajoRoute::_('index.php?option=com_menus&view=items&menu_id='.$item->menu_id.'&filter_published=1');?>">
						<?php echo $item->count_published; ?></a>
				</td>
				<td class="center btns">
					<a href="<?php echo MolajoRoute::_('index.php?option=com_menus&view=items&menu_id='.$item->menu_id.'&filter_published=0');?>">
						<?php echo $item->count_unpublished; ?></a>
				</td>
				<td class="center btns">
					<a href="<?php echo MolajoRoute::_('index.php?option=com_menus&view=items&menu_id='.$item->menu_id.'&filter_published=-2');?>">
						<?php echo $item->count_trashed; ?></a>
				</td>
				<td class="left">
				<ul>
					<?php
					if (isset($this->modules[$item->menu_id])) :
						foreach ($this->modules[$item->menu_id] as &$module) :
						?>
						<li>
							<?php if ($canEdit) : ?>
								<a class="modal" href="<?php echo MolajoRoute::_('index.php?option=com_modules&task=module.edit&id='.$module->id.'&return='.$return.'&tmpl=component&layout=modal');?>" rel="{handler: 'iframe', size: {x: 1024, y: 450}}"  title="<?php echo MolajoText::_('COM_MENUS_EDIT_MODULE_SETTINGS');?>">
								<?php echo MolajoText::sprintf('COM_MENUS_MODULE_ACCESS_POSITION', $this->escape($module->title), $this->escape($module->access_title), $this->escape($module->position)); ?></a>
							<?php else :?>
								<?php echo MolajoText::sprintf('COM_MENUS_MODULE_ACCESS_POSITION', $this->escape($module->title), $this->escape($module->access_title), $this->escape($module->position)); ?>
							<?php endif; ?>
						</li>
						<?php
						endforeach;
					endif;
					?>
				</ul>
				</td>
				<td class="center">
					<?php echo $item->id; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo MolajoHTML::_('form.token'); ?>
	</div>
</form>