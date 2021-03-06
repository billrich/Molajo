<?php
/**
 * @version        $Id: application.php 20196 2011-01-09 02:40:25Z ian $
 * @copyright    Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License, see LICENSE.php
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.form.formfield');

/**
 * Form Field Place class.
 *
 * @package        Joomla.Administrator
 * @subpackage    installer
 * * * @since        1.0
 */
class JFormFieldApplication extends JFormField
{
    /**
     * The field type.
     *
     * @var        string
     */
    protected $type = 'Application';

    /**
     * Method to get the field input.
     *
     * @return    string        The field input.
     * @since    1.0
     */
    protected function getInput()
    {
        $onchange = $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';
        $options = array();
        foreach ($this->element->children() as $option) {
            $options[] = MolajoHTML::_('select.option', $option->attributes('value'), MolajoTextHelper::_(trim($option->data())));
        }
        $options[] = MolajoHTML::_('select.option', '0', MolajoTextHelper::sprintf('JSITE'));
        $options[] = MolajoHTML::_('select.option', '1', MolajoTextHelper::sprintf('JADMINISTRATOR'));
        $return = MolajoHTML::_('select.genericlist', $options, $this->name, $onchange, 'value', 'text', $this->value, $this->id);
        return $return;
    }
}