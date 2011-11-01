<?php
/**
 * @version		$Id: modal.php 20899 2011-03-07 20:56:09Z ian $
 * @package		Joomla.Administrator
 * @subpackage	com_modules
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

MolajoHTML::addIncludePath(JPATH_COMPONENT.'/helpers/html');
MolajoHTML::_('behavior.tooltip');

$function	= JRequest::getCmd('function', 'jSelectPosition');
$lang		= MolajoFactory::getLanguage();
$ordering	= $this->escape($this->state->get('list.ordering'));
$direction	= $this->escape($this->state->get('list.direction'));
$applicationId	= $this->state->get('filter.application_id');
$state		= $this->state->get('filter.state');
$template	= $this->state->get('filter.template');
$type		= $this->state->get('filter.type');
?>
<form action="<?php echo MolajoRoute::_('index.php?option=com_modules&view=positions&layout=modal&tmpl=component&function='.$function.'&application_id=' .$applicationId);?>" method="post" name="adminForm" id="adminForm">
	<fieldset class="filter clearfix">
		<div class="left">
			<label for="filter_search">
				<?php echo MolajoText::_('JSearch_Filter_Label'); ?>
			</label>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" size="30" title="<?php echo MolajoText::_('COM_MODULES_FILTER_SEARCH_DESC'); ?>" />

			<button type="submit">
				<?php echo MolajoText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();">
				<?php echo MolajoText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>

		<div class="right">
			<select name="filter_state" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo MolajoText::_('JOPTION_SELECT_PUBLISHED');?></option>
				<?php echo MolajoHTML::_('select.options', MolajoHTML::_('modules.templateStates'), 'value', 'text', $state, true);?>
			</select>

			<select name="filter_type" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo MolajoText::_('COM_MODULES_OPTION_SELECT_TYPE');?></option>
				<?php echo MolajoHTML::_('select.options', MolajoHTML::_('modules.types'), 'value', 'text', $type, true);?>
			</select>

			<select name="filter_template" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo MolajoText::_('JOPTION_SELECT_TEMPLATE');?></option>
				<?php echo MolajoHTML::_('select.options', MolajoHTML::_('modules.templates', $applicationId), 'value', 'text', $template, true);?>
			</select>
		</div>
	</fieldset>

	<table class="adminlist">
		<thead>
			<tr>
				<th class="title" width="20%">
					<?php echo MolajoHTML::_('grid.sort', 'JGLOBAL_TITLE', 'value', $direction, $ordering); ?>
				</th>
				<th>
					<?php echo MolajoHTML::_('grid.sort', 'COM_MODULES_HEADING_TEMPLATES', 'templates', $direction, $ordering); ?>
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
		<?php $i=1; foreach ($this->items as $value=>$templates) : ?>
			<tr class="row<?php echo $i=1-$i;?>">
				<td>
					<a class="pointer" onclick="if (window.parent) window.parent.<?php echo $function;?>('<?php echo $value; ?>');"><?php echo $this->escape($value); ?></a>
				</td>
				<td>
					<?php if (!empty($templates)):?>
					<a class="pointer" onclick="if (window.parent) window.parent.<?php echo $function;?>('<?php echo $value; ?>');">
						<ul>
						<?php foreach ($templates as $template => $label):?>
							<li><?php echo $lang->hasKey($label) ? MolajoText::sprintf('COM_MODULES_MODULE_TEMPLATE_POSITION', MolajoText::_($template), MolajoText::_($label)) : MolajoText::_($template);?></li>
						<?php endforeach;?>
						</ul>
					</a>
					<?php endif;?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $ordering; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $direction; ?>" />
		<?php echo MolajoHTML::_('form.token'); ?>
	</div>
</form>