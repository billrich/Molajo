<?php
/**
 * @package     Molajo
 * @subpackage  System Plugin
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

/**
 * Molajo System Plugin
 */
class plgSystemMolajo extends MolajoPlugin
{
    /**
     * System Event: onAfterInitialise
     *
     * @return    string
     */
    public function __construct(& $subject, $config = array())
    {
        parent::__construct($subject, $config);
    }

    /**
     * System Event: onAfterInitialise
     * @return    bool
     */
    function onAfterInitialise()
    {
    }

    /**
     * onContentPrepareData
     *
     * Not needed since all parameter data is stored in the database in the parameters column
     *
     * @param    string    $context    The context for the content passed to the plugin.
     * @param    object    $data       The data relating to the content that is being prepared for save.
     * @return    boolean
     * @since    1.6
     */
    public function onContentPrepareData($context, $data)
    {
    }

    /**
     * onContentPrepareForm
     *
     * Save Method: augments primary form fields with additional custom data
     *
     * @param    object    $form  Form object to be used during save
     * @param    object    $data  Data returned from the model as validated
     * @return    boolean
     * @return    string
     * @since    1.6
     */
    public function onContentPrepareForm($form, $data)
    {
        /** Only run for Component Configuration */
        if (($form instanceof JForm)) {
        } else {
            return false;
        }

        /** initialise */
        $loadParameterSetsArray = array();

        /** retrieve parameter sets required for this component */
        if ($form->getName() == 'config.component') {
            $loadParameterSetsArray = $this->getComponentParameterSets();
            if ($loadParameterSetsArray === false || count($loadParameterSetsArray) == 0) {
                return true;
            }

            /** retrieve parameter sets required for this menu item */
        } else if ($form->getName() == 'menus.item') {
            $loadParameterSetsArray = $this->getMenuItemParameterSets($data);
            if ($loadParameterSetsArray === false || count($loadParameterSetsArray) == 0) {
                return true;
            }

        } else if ($form->getName() == JRequest::getVar('option') . '.' . JRequest::getCmd('view') . '.' . JRequest::getCmd('layout') . '.' . JRequest::getCmd('task') . '.' . JRequest::getInt('id') . '.' . JRequest::getVar('datakey')) {
            $loadParameterSetsArray = $this->getDetailItemParameterSets($data);
            if ($loadParameterSetsArray === false || count($loadParameterSetsArray) == 0) {
                return true;
            }
        }

        /** load each parameter set one at a time  */
        $parameterSetAdded = false;
        foreach ($loadParameterSetsArray as $parameterSet) {
            $results = $this->loadParameterSetsToForm($parameterSet, $form);
            if ($results === true) {
                $parameterSetAdded = true;
            }
        }

        /** if any parameter sets were loaded, bind data to form (data stored in database and therefore already available) */
        if ($parameterSetAdded) {
            $form->bind($data);
        }

        return true;
    }

    /**
     * getComponentParameterSets
     *
     * Retrieve the set of Layouts for which Parameter sets are needed
     *
     * @return object
     */
    function getComponentParameterSets()
    {
        $parameters = JComponentHelper::getParameters(JRequest::getVar('component'));
        $layoutParameters = $this->getSiteLayouts(JRequest::getVar('component'));
        return $this->getLayoutParameterOptions($layoutParameters, $parameters);
    }

    /**
     * getMenuItemParameterSets
     *
     * Retrieve the set of Layouts for which Parameter sets are needed
     * @param $parameters
     * @param $data
     * @return object
     */
    function getDetailItemParameterSets($data)
    {
        $parameters = JComponentHelper::getParameters(JRequest::getVar('option'));
        return $this->getLayoutParameterOptions(array('single_item_parameter'), $parameters);
    }

    /**
     * getMenuItemParameterSets
     *
     * Retrieve the set of Layouts for which Parameter sets are needed
     * @param $parameters
     * @param $data
     * @return object
     */
    function getMenuItemParameterSets($data)
    {
        $option = '';
        $view = '';
        $layout = 'default';
        foreach ($data['request'] as $name => $value) {
            if ($name == 'option') {
                $option = $value;
            } else if ($name == 'view') {
                $view = $value;
            } else if ($name == 'layout') {
                $layout = $value;
            }
        }
        if ($option == '') {
            return true;
        }
        $this->getSiteLayouts($option);

        $typeArray = array('' . $view . '_' . $layout . '_parameter');
        $parameters = JComponentHelper::getParameters($option);

        return $this->getLayoutParameterOptions($typeArray, $parameters);
    }

    /**
     * getLayoutParameterOptions
     *
     * Given values specified in $typeArray, retrieve the parameter form object file names
     * @param  $typeArray
     * @return
     */
    function getLayoutParameterOptions($typeArray, $parameters)
    {
        $loadParameterSetsArray = array();

        /** loop through layout parameter types */
        foreach ($typeArray as $layoutParameterType) {

            /** loop thru ParameterSet options **/
            for ($i = 1; $i < 1000; $i++) {

                $parameterSetName = $parameters->def($layoutParameterType . $i);

                /** encountered end of ParameterSets **/
                if ($parameterSetName == null) {
                    break;
                }
                /** no ParameterSet was selected for configuration option **/
                if (in_array($parameterSetName, $loadParameterSetsArray)) {

                    /** no ParameterSet was selected for configuration option **/
                } else if ($parameterSetName == '0') {

                    /** configuration option set for ParameterSet list **/
                } else {
                    /** save so it does not get added multiple times **/
                    $loadParameterSetsArray[] = $parameterSetName;
                }
            }
        }

        return $loadParameterSetsArray;
    }

    /**
     * loadParameterSetToForm
     *
     * Loads Parameter Sets into the Form
     *
     * @param string $parameterSet
     * @param object $form
     * @param object $content
     * @return boolean
     */
    function loadParameterSetsToForm($parameterSet, $form)
    {
        $path = $this->getParameterSetPath($parameterSet);
        if ($path === false) {
            return false;
        }
        $form->loadFile($path, false);

        return true;
    }

    /**
     * getParameterSetPath
     *
     * Loads Custom Fields into the Form for a specific Content Type
     *
     * @param string $parameterSet
     * @param object $form
     * @param object $content
     * @return boolean
     */
    function getParameterSetPath($parameterSet)
    {
        /** Amy_TODO: figure this out. site template parameters */
        $path = JPATH_SITE . '/templates/' . MolajoFactory::getApplication('site')->getTemplate() . '/' . 'parameters/' . $parameterSet . '.xml';
        if (is_file($path)) {
            return $path;
        }
        /** admin template parameters */
        $path = JPATH_ADMINISTRATOR . '/templates/' . MolajoFactory::getApplication('administrator')->getTemplate() . '/' . 'parameters/' . $parameterSet . '.xml';
        if (is_file($path)) {
            return $path;
        }
        /** component parameters */
        $path = JPATH_SITE . '/components/' . JRequest::getVar('component') . '/' . 'parameters/' . $parameterSet . '.xml';
        if (is_file($path)) {
            return $path;
        }
        /** administrator component */
        $path = JPATH_ADMINISTRATOR . '/components/' . JRequest::getVar('component') . '/' . 'parameters/' . $parameterSet . '.xml';
        if (is_file($path)) {
            return $path;
        }
        /** library */
        $path = JPATH_ROOT . MOLAJO_APPLICATIONS_DATA_XML . '/' . $parameterSet . '.xml';
        if (is_file($path)) {
            return $path;
        }
    }

    /**
     * getSiteLayouts
     * @param  $option
     * @return void
     */
    function getSiteLayouts($option)
    {
        /** component view location */
        $path = JPATH_SITE . '/components/' . $option . '/views';

        /** retrieve all folder names for destination **/
        $folders = JFolder::folders($path, $filter = '', $recurse = true, $fullpath = true, $exclude = array('.svn', 'CVS'));

        $view = '';
        $layout = '';
        $viewLayout = array();
        /** process files in each folder **/
        foreach ($folders as $folder) {

            /** rename files that do not fit the pattern **/
            if (basename($folder) == 'layout') {
                $files = JFolder::files($folder, $filter = '.php', $recurse = false, $full = false, $exclude = array(), $excludefilter = array('^\..*', '.*~', '*_*.php'));

                /** process each file **/
                foreach ($files as $file) {
                    $layout = substr($file, 0, strlen($file) - 4);
                    $viewLayout[] = '' . $view . '_' . $layout . '_parameter';
                }
            } else {
                $view = basename($folder);
            }
        }
        return array_unique($viewLayout);
    }
}