<?php
/**
 * @package     Molajo
 * @subpackage  Application
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @copyright   Copyright (C) 2011 Individual Molajo Contributors. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

/**
 * Component installer
 *
 * @package     Joomla.Platform
 * @subpackage  Installer
 * @since       11.1
 */
class MolajoInstallerAdapterComponent extends MolajoAdapterInstance
{
    /**
     * Copy of the XML manifest file
     *
     * @var    string
     * @since  11.1
     */
    protected $manifest = null;

    /**
     * Name of the extension
     *
     * @var    string
     * @since  11.1
     * */
    protected $name = null;

    /**
     * The unique identifier for the extension (e.g. login)
     *
     * @var    string
     * @since  11.1
     * */
    protected $element = null;

    /**
     *
     * The list of current files fo the Joomla! CMS adminisrator that are installed and is read
     * from the manifest on disk in the update area to handle doing a diff
     * and deleting files that are in the old files list and not in the new
     * files list.
     *
     * @var    array
     * @since  11.1
     * */
    protected $oldAdminFiles = null;

    /**
     * The list of current files that are installed and is read
     * from the manifest on disk in the update area to handle doing a diff
     * and deleting files that are in the old files list and not in the new
     * files list.
     *
     * @var    array
     * @since  11.1
     * */
    protected $oldFiles = null;

    /**
     * A path to the PHP file that the scriptfile declaration in
     * the manifest refers to.
     *
     * @var    string
     * @since  11.1
     * */
    protected $manifest_script = null;

    /**
     * For legacy installations this is a path to the PHP file that the scriptfile declaration in the
     * manifest refers to.
     *
     * @var    string
     * @since  11.1
     * */
    protected $install_script = null;

    /**
     * Custom loadLanguage method
     *
     * @param   string  $path  The path language files are on.
     *
     * @return  void
     *
     * @since   11.1
     */
    public function loadLanguage($path = null)
    {
        $source = $this->parent->getPath('source');

        if (!$source) {
            $this->parent
                    ->setPath(
                'source',
                ($this->parent->extension->application_id ? MOLAJO_BASE_FOLDER : MOLAJO_BASE_FOLDER) .
                '/components/' . $this->parent->extension->element
            );
        }

        $this->manifest = $this->parent->getManifest();
        $name = strtolower(JFilterInput::getInstance()->clean((string)$this->manifest->name, 'cmd'));

        if (substr($name, 0, 4) == "") {
            $extension = $name;
        }
        else
        {
            $extension = "$name";
        }

        $lang = MolajoFactory::getLanguage();
        $source = $path ? $path : ($this->parent->extension->application_id ? MOLAJO_BASE_FOLDER
                : MOLAJO_BASE_FOLDER) . '/components/' . $extension;

        if ($this->manifest->administration->files) {
            $element = $this->manifest->administration->files;
        }
        elseif ($this->manifest->files)
        {
            $element = $this->manifest->files;
        }
        else
        {
            $element = null;
        }

        if ($element) {
            $folder = (string)$element->attributes()->folder;

            if ($folder && file_exists("$path/$folder")) {
                $source = "$path/$folder";
            }
        }
        $lang->load($extension . '.sys', $source, null, false, false) || $lang->load($extension . '.sys', MOLAJO_BASE_FOLDER, null, false, false)
        || $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
        || $lang->load($extension . '.sys', MOLAJO_BASE_FOLDER, $lang->getDefault(), false, false);
    }

    /**
     * Custom install method for components
     *
     * @return  boolean  True on success
     *
     * @since   11.1
     */
    public function install()
    {
        // Get a database connector object
        $db = $this->parent->getDbo();

        // Get the extension manifest object
        $this->manifest = $this->parent->getManifest();

        // Manifest Document Setup Section

        // Set the extension's name
        $name = strtolower(JFilterInput::getInstance()->clean((string)$this->manifest->name, 'cmd'));
        if (substr($name, 0, 4) == "") {
            $element = $name;
        }
        else
        {
            $element = "$name";
        }

        $this->set('name', $name);
        $this->set('element', $element);

        // Get the component description
        $this->parent->set('message', MolajoTextHelper::_((string)$this->manifest->description));

        // Set the installation target paths
        $this->parent->setPath('extension_site', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $this->get('element')));
        $this->parent->setPath('extension_administrator', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $this->get('element')));

        // copy this as its used as a common base
        $this->parent->setPath('extension_root', $this->parent->getPath('extension_administrator'));

        // Basic Checks Section

        // Make sure that we have an admin element
        if (!$this->manifest->administration) {
            MolajoError::raiseWarning(1, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_INSTALL_ADMINISTER_ELEMENT'));
            return false;
        }

        // Filesystem Processing Section

        // If the component site or admin directory already exists, then we will assume that the component is already
        // installed or another component is using that directory.

        if (file_exists($this->parent->getPath('extension_site')) || file_exists($this->parent->getPath('extension_administrator'))) {
            // Look for an update function or update tag
            $updateElement = $this->manifest->update;
            // Upgrade manually set or
            // Update function available or
            // Update tag detected

            if ($this->parent->getUpgrade() || ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'update'))
                || $updateElement
            ) {
                return $this->update(); // transfer control to the update function
            }
            elseif (!$this->parent->getOverwrite())
            {
                // Overwrite is set.
                // We didn't have overwrite set, find an update function or find an update tag so lets call it safe
                if (file_exists($this->parent->getPath('extension_site'))) {
                    // If the site exists say so.
                    MolajoError::raiseWarning(1, MolajoTextHelper::sprintf('JLIB_INSTALLER_ERROR_COMP_INSTALL_DIR_SITE', $this->parent->getPath('extension_site')));
                }
                else
                {
                    // If the admin exists say so
                    MolajoError::raiseWarning(
                        1,
                        MolajoTextHelper::sprintf('JLIB_INSTALLER_ERROR_COMP_INSTALL_DIR_ADMIN', $this->parent->getPath('extension_administrator'))
                    );
                }
                return false;
            }
        }

        // Installer Trigger Loading

        // If there is an manifest class file, lets load it; we'll copy it later (don't have dest yet)
        $manifestScript = (string)$this->manifest->scriptfile;

        if ($manifestScript) {
            $manifestScriptFile = $this->parent->getPath('source') . '/' . $manifestScript;

            if (is_file($manifestScriptFile)) {
                // Load the file
                include_once $manifestScriptFile;
            }

            // Set the class name
            $classname = $this->get('element') . 'InstallerScript';

            if (class_exists($classname)) {
                // Create a new instance
                $this->parent->manifestClass = new $classname($this);
                // And set this so we can copy it later
                $this->set('manifest_script', $manifestScript);

                // Note: if we don't find the class, don't bother to copy the file
            }
        }

        // Run preflight if possible (since we know we're not an update)
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'preflight')) {
            if ($this->parent->manifestClass->preflight('install', $this) === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
                return false;
            }
        }

        // Create msg object; first use here
        $msg = ob_get_contents();
        ob_end_clean();

        // If the component directory does not exist, let's create it
        $created = false;

        if (!file_exists($this->parent->getPath('extension_site'))) {
            if (!$created = JFolder::create($this->parent->getPath('extension_site'))) {
                MolajoError::raiseWarning(
                    1,
                    MolajoTextHelper::sprintf('JLIB_INSTALLER_ERROR_COMP_INSTALL_FAILED_TO_CREATE_DIRECTORY_SITE', $this->parent->getPath('extension_site'))
                );
                return false;
            }
        }

        // Since we created the component directory and will want to remove it if we have to roll back
        // the installation, let's add it to the installation step stack

        if ($created) {
            $this->parent->pushStep(array('type' => 'folder', 'path' => $this->parent->getPath('extension_site')));
        }

        // If the component admin directory does not exist, let's create it
        $created = false;

        if (!file_exists($this->parent->getPath('extension_administrator'))) {
            if (!$created = JFolder::create($this->parent->getPath('extension_administrator'))) {
                MolajoError::raiseWarning(
                    1,
                    MolajoTextHelper::sprintf(
                        'JLIB_INSTALLER_ERROR_COMP_INSTALL_FAILED_TO_CREATE_DIRECTORY_ADMIN',
                        $this->parent->getPath('extension_administrator')
                    )
                );
                // Install failed, rollback any changes
                $this->parent->abort();

                return false;
            }
        }

        /*
           * Since we created the component admin directory and we will want to remove it if we have to roll
           * back the installation, let's add it to the installation step stack
           */
        if ($created) {
            $this->parent->pushStep(array('type' => 'folder', 'path' => $this->parent->getPath('extension_administrator')));
        }

        // Copy site files
        if ($this->manifest->files) {
            if ($this->parent->parseFiles($this->manifest->files) === false) {
                // Install failed, rollback any changes
                $this->parent->abort();

                return false;
            }
        }

        // Copy admin files
        if ($this->manifest->administration->files) {
            if ($this->parent->parseFiles($this->manifest->administration->files, 1) === false) {
                // Install failed, rollback any changes
                $this->parent->abort();

                return false;
            }
        }

        // Parse optional tags
        $this->parent->parseMedia($this->manifest->media);
        $this->parent->parseLanguages($this->manifest->languages);
        $this->parent->parseLanguages($this->manifest->administration->languages, 1);

        // Deprecated install, remove after 1.6
        // If there is an install file, lets copy it.
        $installFile = (string)$this->manifest->installfile;

        if ($installFile) {
            // Make sure it hasn't already been copied (this would be an error in the XML install file)
            if (!file_exists($this->parent->getPath('extension_administrator') . '/' . $installFile) || $this->parent->getOverwrite()) {
                $path['src'] = $this->parent->getPath('source') . '/' . $installFile;
                $path['dest'] = $this->parent->getPath('extension_administrator') . '/' . $installFile;

                if (!$this->parent->copyFiles(array($path))) {
                    // Install failed, rollback changes
                    $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_PHP_INSTALL'));

                    return false;
                }
            }

            $this->set('install_script', $installFile);
        }

        // Deprecated uninstall, remove after 1.6
        // If there is an uninstall file, let's copy it.
        $uninstallFile = (string)$this->manifest->uninstallfile;

        if ($uninstallFile) {
            // Make sure it hasn't already been copied (this would be an error in the XML install file)
            if (!file_exists($this->parent->getPath('extension_administrator') . '/' . $uninstallFile) || $this->parent->getOverwrite()) {
                $path['src'] = $this->parent->getPath('source') . '/' . $uninstallFile;
                $path['dest'] = $this->parent->getPath('extension_administrator') . '/' . $uninstallFile;

                if (!$this->parent->copyFiles(array($path))) {
                    // Install failed, rollback changes
                    $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_PHP_UNINSTALL'));
                    return false;
                }
            }
        }

        // If there is a manifest script, let's copy it.
        if ($this->get('manifest_script')) {
            $path['src'] = $this->parent->getPath('source') . '/' . $this->get('manifest_script');
            $path['dest'] = $this->parent->getPath('extension_administrator') . '/' . $this->get('manifest_script');

            if (!file_exists($path['dest']) || $this->parent->getOverwrite()) {
                if (!$this->parent->copyFiles(array($path))) {
                    // Install failed, rollback changes
                    $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_MANIFEST'));

                    return false;
                }
            }
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Database Processing Section
         * ---------------------------------------------------------------------------------------------
         */

        /*
           * Let's run the install queries for the component
           * If Joomla 1.5 compatible, with discreet sql files - execute appropriate
           * file for utf-8 support or non-utf-8 support
           */
        // Try for Joomla 1.5 type queries
        // Second argument is the utf compatible version attribute
        if (isset($this->manifest->install->sql)) {
            $utfresult = $this->parent->parseSQLFiles($this->manifest->install->sql);

            if ($utfresult === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_SQL_ERROR', $db->stderr(true)));

                return false;
            }
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Custom Installation Script Section
         * ---------------------------------------------------------------------------------------------
         */

        /*
           * If we have an install script, let's include it, execute the custom
           * install method, and append the return value from the custom install
           * method to the installation message.
           */
        // Start legacy support
        if ($this->get('install_script')) {
            if (is_file($this->parent->getPath('extension_administrator') . '/' . $this->get('install_script')) || $this->parent->getOverwrite()) {
                $notdef = false;
                $ranwell = false;
                ob_start();
                ob_implicit_flush(false);

                require_once $this->parent->getPath('extension_administrator') . '/' . $this->get('install_script');

                if (function_exists('install')) {
                    if (install() === false) {
                        $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));

                        return false;
                    }
                }

                $msg .= ob_get_contents(); // append messages
                ob_end_clean();
            }
        }

        // End legacy support
        // Start Joomla! 1.6
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'install')) {
            if ($this->parent->manifestClass->install($this) === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));

                return false;
            }
        }

        // Append messages
        $msg .= ob_get_contents();
        ob_end_clean();

        /**
         * ---------------------------------------------------------------------------------------------
         * Finalization and Cleanup Section
         * ---------------------------------------------------------------------------------------------
         */

        // Add an entry to the extension table with a whole heap of defaults
        $row = MolajoTable::getInstance('extension');
        $row->set('name', $this->get('name'));
        $row->set('type', 'component');
        $row->set('element', $this->get('element'));
        $row->set('folder', ''); // There is no folder for components
        $row->set('enabled', 1);
        $row->set('protected', 0);
        $row->set('access', 0);
        $row->set('application_id', 1);
        $row->set('parameters', $this->parent->getParameters());
        $row->set('manifest_cache', $this->parent->generateManifestCache());

        if (!$row->store()) {
            // Install failed, roll back changes
            $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_ROLLBACK', $db->stderr(true)));
            return false;
        }

        $eid = $db->insertid();

        // Clobber any possible pending updates
        $update = MolajoTable::getInstance('update');
        $uid = $update->find(array('element' => $this->get('element'), 'type' => 'component', 'application_id' => '', 'folder' => ''));

        if ($uid) {
            $update->delete($uid);
        }

        // We will copy the manifest file to its appropriate place.
        if (!$this->parent->copyManifest()) {
            // Install failed, rollback changes
            $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_COPY_SETUP'));
            return false;
        }

        // Time to build the admin menus
        if (!$this->_buildAdminMenus($row->extension_id)) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_BUILDADMINMENUS_FAILED'));

            //$this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_ROLLBACK', $db->stderr(true)));
            //return false;
        }

        // Set the schema version to be the latest update version
        if ($this->manifest->update) {
            $this->parent->setSchemaVersion($this->manifest->update->schemas, $eid);
        }

        // Register the component container just under root in the assets table.
        $asset = MolajoTable::getInstance('Asset');
        $asset->name = $row->element;
        $asset->parent_id = 1;
        $asset->rules = '{}';
        $asset->title = $row->name;
        $asset->setLocation(1, 'last-child');
        if (!$asset->store()) {
            // Install failed, roll back changes
            $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_ROLLBACK', $db->stderr(true)));
            return false;
        }

        // And now we run the postflight
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'postflight')) {
            $this->parent->manifestClass->postflight('install', $this);
        }

        // Append messages
        $msg .= ob_get_contents();
        ob_end_clean();

        if ($msg != '') {
            $this->parent->set('extension_message', $msg);
        }

        return $row->extension_id;
    }

    /**
     * Custom update method for components
     *
     * @return  boolean  True on success
     *
     * @since   11.1
     */
    public function update()
    {
        // Get a database connector object
        $db = $this->parent->getDbo();

        // Set the overwrite setting
        $this->parent->setOverwrite(true);

        // Get the extension manifest object
        $this->manifest = $this->parent->getManifest();

        /**
         * ---------------------------------------------------------------------------------------------
         * Manifest Document Setup Section
         * ---------------------------------------------------------------------------------------------
         */

        // Set the extension's name
        $name = strtolower(JFilterInput::getInstance()->clean((string)$this->manifest->name, 'cmd'));
        if (substr($name, 0, 4) == "") {
            $element = $name;
        }
        else
        {
            $element = "$name";
        }

        $this->set('name', $name);
        $this->set('element', $element);

        // Get the component description
        $description = (string)$this->manifest->description;

        if ($description) {
            $this->parent->set('message', MolajoTextHelper::_($description));
        }
        else
        {
            $this->parent->set('message', '');
        }

        // Set the installation target paths
        $this->parent->setPath('extension_site', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $this->get('element')));
        $this->parent->setPath('extension_administrator', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $this->get('element')));
        $this->parent->setPath('extension_root', $this->parent->getPath('extension_administrator')); // copy this as its used as a common base

        /**
         * Hunt for the original XML file
         */
        $old_manifest = null;
        // Create a new installer because findManifest sets stuff
        // Look in the administrator first
        $tmpInstaller = new MolajoInstaller;
        $tmpInstaller->setPath('source', $this->parent->getPath('extension_administrator'));

        if (!$tmpInstaller->findManifest()) {
            // Then the site
            $tmpInstaller->setPath('source', $this->parent->getPath('extension_site'));
            if ($tmpInstaller->findManifest()) {
                $old_manifest = $tmpInstaller->getManifest();
            }
        }
        else
        {
            $old_manifest = $tmpInstaller->getManifest();
        }

        // Should do this above perhaps?
        if ($old_manifest) {
            $this->oldAdminFiles = $old_manifest->administration->files;
            $this->oldFiles = $old_manifest->files;
        }
        else
        {
            $this->oldAdminFiles = null;
            $this->oldFiles = null;
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Basic Checks Section
         * ---------------------------------------------------------------------------------------------
         */

        // Make sure that we have an admin element
        if (!$this->manifest->administration) {
            MolajoError::raiseWarning(1, MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_ADMINISTER_ELEMENT'));
            return false;
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Installer Trigger Loading
         * ---------------------------------------------------------------------------------------------
         */
        // If there is an manifest class file, lets load it; we'll copy it later (don't have dest yet)
        $manifestScript = (string)$this->manifest->scriptfile;

        if ($manifestScript) {
            $manifestScriptFile = $this->parent->getPath('source') . '/' . $manifestScript;

            if (is_file($manifestScriptFile)) {
                // Load the file
                include_once $manifestScriptFile;
            }

            // Set the class name
            $classname = $element . 'InstallerScript';

            if (class_exists($classname)) {
                // Create a new instance
                $this->parent->manifestClass = new $classname($this);
                // And set this so we can copy it later
                $this->set('manifest_script', $manifestScript);

                // Note: if we don't find the class, don't bother to copy the file
            }
        }

        // Run preflight if possible (since we know we're not an update)
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'preflight')) {
            if ($this->parent->manifestClass->preflight('update', $this) === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));

                return false;
            }
        }

        // Create msg object; first use here
        $msg = ob_get_contents();
        ob_end_clean();

        /**
         * ---------------------------------------------------------------------------------------------
         * Filesystem Processing Section
         * ---------------------------------------------------------------------------------------------
         */

        // If the component directory does not exist, let's create it
        $created = false;

        if (!file_exists($this->parent->getPath('extension_site'))) {
            if (!$created = JFolder::create($this->parent->getPath('extension_site'))) {
                MolajoError::raiseWarning(
                    1,
                    MolajoTextHelper::sprintf('JLIB_INSTALLER_ERROR_COMP_UPDATE_FAILED_TO_CREATE_DIRECTORY_SITE', $this->parent->getPath('extension_site'))
                );

                return false;
            }
        }

        /*
           * Since we created the component directory and will want to remove it if we have to roll back
           * the installation, lets add it to the installation step stack
           */
        if ($created) {
            $this->parent->pushStep(array('type' => 'folder', 'path' => $this->parent->getPath('extension_site')));
        }

        // If the component admin directory does not exist, let's create it
        $created = false;

        if (!file_exists($this->parent->getPath('extension_administrator'))) {
            if (!$created = JFolder::create($this->parent->getPath('extension_administrator'))) {
                MolajoError::raiseWarning(
                    1,
                    MolajoTextHelper::sprintf(
                        'JLIB_INSTALLER_ERROR_COMP_UPDATE_FAILED_TO_CREATE_DIRECTORY_ADMIN',
                        $this->parent->getPath('extension_administrator')
                    )
                );
                // Install failed, rollback any changes
                $this->parent->abort();

                return false;
            }
        }

        /*
           * Since we created the component admin directory and we will want to remove it if we have to roll
           * back the installation, let's add it to the installation step stack
           */
        if ($created) {
            $this->parent->pushStep(array('type' => 'folder', 'path' => $this->parent->getPath('extension_administrator')));
        }

        // Find files to copy
        if ($this->manifest->files) {
            if ($this->parent->parseFiles($this->manifest->files, 0, $this->oldFiles) === false) {
                // Install failed, rollback any changes
                $this->parent->abort();

                return false;
            }
        }

        if ($this->manifest->administration->files) {
            if ($this->parent->parseFiles($this->manifest->administration->files, 1, $this->oldAdminFiles) === false) {
                // Install failed, rollback any changes
                $this->parent->abort();

                return false;
            }
        }

        // Parse optional tags
        $this->parent->parseMedia($this->manifest->media);
        $this->parent->parseLanguages($this->manifest->languages);
        $this->parent->parseLanguages($this->manifest->administration->languages, 1);

        // Deprecated install, remove after 1.6
        // If there is an install file, lets copy it.
        $installFile = (string)$this->manifest->installfile;

        if ($installFile) {
            // Make sure it hasn't already been copied (this would be an error in the XML install file)
            if (!file_exists($this->parent->getPath('extension_administrator') . '/' . $installFile) || $this->parent->getOverwrite()) {
                $path['src'] = $this->parent->getPath('source') . '/' . $installFile;
                $path['dest'] = $this->parent->getPath('extension_administrator') . '/' . $installFile;

                if (!$this->parent->copyFiles(array($path))) {
                    // Install failed, rollback changes
                    $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_PHP_INSTALL'));
                    return false;
                }
            }

            $this->set('install_script', $installFile);
        }

        // Deprecated uninstall, remove after 1.6
        // If there is an uninstall file, lets copy it.
        $uninstallFile = (string)$this->manifest->uninstallfile;

        if ($uninstallFile) {
            // Make sure it hasn't already been copied (this would be an error in the XML install file)
            if (!file_exists($this->parent->getPath('extension_administrator') . '/' . $uninstallFile) || $this->parent->getOverwrite()) {
                $path['src'] = $this->parent->getPath('source') . '/' . $uninstallFile;
                $path['dest'] = $this->parent->getPath('extension_administrator') . '/' . $uninstallFile;

                if (!$this->parent->copyFiles(array($path))) {
                    // Install failed, rollback changes
                    $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_PHP_UNINSTALL'));

                    return false;
                }
            }
        }

        // If there is a manifest script, let's copy it.
        if ($this->get('manifest_script')) {
            $path['src'] = $this->parent->getPath('source') . '/' . $this->get('manifest_script');
            $path['dest'] = $this->parent->getPath('extension_administrator') . '/' . $this->get('manifest_script');

            if (!file_exists($path['dest']) || $this->parent->getOverwrite()) {
                if (!$this->parent->copyFiles(array($path))) {
                    // Install failed, rollback changes
                    $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_MANIFEST'));

                    return false;
                }
            }
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Database Processing Section
         * ---------------------------------------------------------------------------------------------
         */

        /*
           * Let's run the update queries for the component
           */
        $row = MolajoTable::getInstance('extension');
        $eid = $row->find(array('element' => strtolower($this->get('element')), 'type' => 'component'));

        if ($this->manifest->update) {
            $result = $this->parent->parseSchemaUpdates($this->manifest->update->schemas, $eid);

            if ($result === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_UPDATE_SQL_ERROR', $db->stderr(true)));

                return false;
            }
        }

        // Time to build the admin menus
        if (!$this->_buildAdminMenus($eid)) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_BUILDADMINMENUS_FAILED'));

            // $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_ROLLBACK', $db->stderr(true)));

            // Return false;
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Custom Installation Script Section
         * ---------------------------------------------------------------------------------------------
         */

        /*
           * If we have an install script, let's include it, execute the custom
           * install method, and append the return value from the custom install
           * method to the installation message.
           */
        // Start legacy support
        if ($this->get('install_script')) {
            if (is_file($this->parent->getPath('extension_administrator') . '/' . $this->get('install_script')) || $this->parent->getOverwrite()) {
                $notdef = false;
                $ranwell = false;
                ob_start();
                ob_implicit_flush(false);

                require_once $this->parent->getPath('extension_administrator') . '/' . $this->get('install_script');

                if (function_exists('install')) {
                    if (install() === false) {
                        $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));

                        return false;
                    }
                }

                $msg .= ob_get_contents(); // append messages
                ob_end_clean();
            }
        }

        /*
           * If we have an update script, let's include it, execute the custom
           * update method, and append the return value from the custom update
           * method to the installation message.
           */
        // Start Joomla! 1.6
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'update')) {
            if ($this->parent->manifestClass->update($this) === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));

                return false;
            }
        }

        // Append messages
        $msg .= ob_get_contents();
        ob_end_clean();

        /**
         * ---------------------------------------------------------------------------------------------
         * Finalization and Cleanup Section
         * ---------------------------------------------------------------------------------------------
         */

        // Clobber any possible pending updates
        $update = MolajoTable::getInstance('update');
        $uid = $update->find(array('element' => $this->get('element'), 'type' => 'component', 'application_id' => '', 'folder' => ''));

        if ($uid) {
            $update->delete($uid);
        }

        // Update an entry to the extension table
        if ($eid) {
            $row->load($eid);
        }
        else
        {
            // Set the defaults
            // There is no folder for components
            $row->folder = '';
            $row->enabled = 1;
            $row->protected = 0;
            $row->access = 1;
            $row->application_id = 1;
            $row->parameters = $this->parent->getParameters();
        }

        $row->name = $this->get('name');
        $row->type = 'component';
        $row->element = $this->get('element');
        $row->manifest_cache = $this->parent->generateManifestCache();

        if (!$row->store()) {
            // Install failed, roll back changes
            $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_UPDATE_ROLLBACK', $db->stderr(true)));

            return false;
        }

        // We will copy the manifest file to its appropriate place.
        if (!$this->parent->copyManifest()) {
            // Install failed, rollback changes
            $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_UPDATE_COPY_SETUP'));

            return false;
        }

        // And now we run the postflight
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'postflight')) {
            $this->parent->manifestClass->postflight('update', $this);
        }
        // Append messages
        $msg .= ob_get_contents();
        ob_end_clean();

        if ($msg != '') {
            $this->parent->set('extension_message', $msg);
        }

        return $row->extension_id;
    }

    /**
     * Custom uninstall method for components
     *
     * @param   integer  $id  The unique extension id of the component to uninstall
     *
     * @return  mixed  Return value for uninstall method in component uninstall file
     *
     * @since   11.1
     */
    public function uninstall($id)
    {
        // Initialise variables.
        $db = $this->parent->getDbo();
        $row = null;
        $retval = true;

        // First order of business will be to load the component object table from the database.
        // This should give us the necessary information to proceed.
        $row = MolajoTable::getInstance('extension');
        if (!$row->load((int)$id)) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_ERRORUNKOWNEXTENSION'));
            return false;
        }

        // Is the component we are trying to uninstall a core one?
        // Because that is not a good idea...
        if ($row->protected) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_WARNCORECOMPONENT'));
            return false;
        }

        // Get the admin and site paths for the component
        $this->parent->setPath('extension_administrator', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $row->element));
        $this->parent->setPath('extension_site', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $row->element));
        $this->parent->setPath('extension_root', $this->parent->getPath('extension_administrator')); // copy this as its used as a common base

        /**
         * ---------------------------------------------------------------------------------------------
         * Manifest Document Setup Section
         * ---------------------------------------------------------------------------------------------
         */

        // Find and load the XML install file for the component
        $this->parent->setPath('source', $this->parent->getPath('extension_administrator'));

        // Get the package manifest object
        // We do findManifest to avoid problem when uninstalling a list of extension: getManifest cache its manifest file
        $this->parent->findManifest();
        $this->manifest = $this->parent->getManifest();

        if (!$this->manifest) {
            // Make sure we delete the folders if no manifest exists
            JFolder::delete($this->parent->getPath('extension_administrator'));
            JFolder::delete($this->parent->getPath('extension_site'));

            // Remove the menu
            $this->_removeAdminMenus($row);

            // Raise a warning
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_ERRORREMOVEMANUALLY'));

            // Return
            return false;
        }

        // Set the extensions name
        $name = strtolower(JFilterInput::getInstance()->clean((string)$this->manifest->name, 'cmd'));
        if (substr($name, 0, 4) == "") {
            $element = $name;
        }
        else
        {
            $element = "$name";
        }

        $this->set('name', $name);
        $this->set('element', $element);

        // Attempt to load the admin language file; might have uninstall strings
        $this->loadLanguage(MOLAJO_BASE_FOLDER . '/components/' . $element);

        /**
         * ---------------------------------------------------------------------------------------------
         * Installer Trigger Loading and Uninstall
         * ---------------------------------------------------------------------------------------------
         */
        // If there is an manifest class file, lets load it; we'll copy it later (don't have dest yet)
        $scriptFile = (string)$this->manifest->scriptfile;

        if ($scriptFile) {
            $manifestScriptFile = $this->parent->getPath('source') . '/' . $scriptFile;

            if (is_file($manifestScriptFile)) {
                // load the file
                include_once $manifestScriptFile;
            }

            // Set the class name
            $classname = $row->element . 'InstallerScript';

            if (class_exists($classname)) {
                // create a new instance
                $this->parent->manifestClass = new $classname($this);
                // and set this so we can copy it later
                $this->set('manifest_script', $scriptFile);

                // Note: if we don't find the class, don't bother to copy the file
            }
        }

        ob_start();
        ob_implicit_flush(false);

        // run uninstall if possible
        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'uninstall')) {
            $this->parent->manifestClass->uninstall($this);
        }

        $msg = ob_get_contents();
        ob_end_clean();

        /**
         * ---------------------------------------------------------------------------------------------
         * Custom Uninstallation Script Section; Legacy CMS 1.5 Support
         * ---------------------------------------------------------------------------------------------
         */

        // Now let's load the uninstall file if there is one and execute the uninstall function if it exists.
        $uninstallFile = (string)$this->manifest->uninstallfile;

        if ($uninstallFile) {
            // Element exists, does the file exist?
            if (is_file($this->parent->getPath('extension_administrator') . '/' . $uninstallFile)) {
                ob_start();
                ob_implicit_flush(false);

                require_once $this->parent->getPath('extension_administrator') . '/' . $uninstallFile;

                if (function_exists('uninstall')) {
                    if (uninstall() === false) {
                        MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_CUSTOM'));
                        $retval = false;
                    }
                }

                // append this in case there was something else
                $msg .= ob_get_contents();
                ob_end_clean();
            }
        }

        if ($msg != '') {
            $this->parent->set('extension_message', $msg);
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Database Processing Section
         * ---------------------------------------------------------------------------------------------
         */

        /*
           * Let's run the uninstall queries for the component
           * If Joomla CMS 1.5 compatible, with discrete sql files - execute appropriate
           * file for utf-8 support or non-utf support
           */
        // Try for Joomla 1.5 type queries
        // Second argument is the utf compatible version attribute
        if (isset($this->manifest->uninstall->sql)) {
            $utfresult = $this->parent->parseSQLFiles($this->manifest->uninstall->sql);

            if ($utfresult === false) {
                // Install failed, rollback changes
                MolajoError::raiseWarning(100, MolajoTextHelper::sprintf('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_SQL_ERROR', $db->stderr(true)));
                $retval = false;
            }
        }

        $this->_removeAdminMenus($row);

        /**
         * ---------------------------------------------------------------------------------------------
         * Filesystem Processing Section
         * ---------------------------------------------------------------------------------------------
         */

        // Let's remove those language files and media in the JROOT/images/ folder that are
        // associated with the component we are uninstalling
        $this->parent->removeFiles($this->manifest->media);
        $this->parent->removeFiles($this->manifest->languages);
        $this->parent->removeFiles($this->manifest->administration->languages, 1);

        // Remove the schema version
        $query = $db->getQuery(true);
        $query->delete()->from('#__schemas')->where('extension_id = ' . $id);
        $db->setQuery($query);
        $db->query();

        // Remove the component container in the assets table.
        $asset = MolajoTable::getInstance('Asset');
        if ($asset->loadByName($element)) {
            $asset->delete();
        }

        // Remove categories for this component
        $query = $db->getQuery(true);
        $query->delete()->from('#__categories')->where('extension=' . $db->quote($element), 'OR')
                ->where('extension LIKE ' . $db->quote($element . '.%'));
        $db->setQuery($query);
        $db->query();
        // Check for errors.
        if ($db->getErrorNum()) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_FAILED_DELETE_CATEGORIES'));
            $this->setError($db->getErrorMsg());
            $retval = false;
        }

        // Clobber any possible pending updates
        $update = MolajoTable::getInstance('update');
        $uid = $update->find(array('element' => $row->element, 'type' => 'component', 'application_id' => '', 'folder' => ''));

        if ($uid) {
            $update->delete($uid);
        }

        // Now we need to delete the installation directories. This is the final step in uninstalling the component.
        if (trim($row->element)) {
            // Delete the component site directory
            if (is_dir($this->parent->getPath('extension_site'))) {
                if (!JFolder::delete($this->parent->getPath('extension_site'))) {
                    MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_FAILED_REMOVE_DIRECTORY_SITE'));
                    $retval = false;
                }
            }

            // Delete the component admin directory
            if (is_dir($this->parent->getPath('extension_administrator'))) {
                if (!JFolder::delete($this->parent->getPath('extension_administrator'))) {
                    MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_FAILED_REMOVE_DIRECTORY_ADMIN'));
                    $retval = false;
                }
            }

            // Now we will no longer need the extension object, so let's delete it and free up memory
            $row->delete($row->extension_id);
            unset($row);

            return $retval;
        }
        else
        {
            // No component option defined... cannot delete what we don't know about
            MolajoError::raiseWarning(100, 'JLIB_INSTALLER_ERROR_COMP_UNINSTALL_NO_OPTION');
            return false;
        }
    }

    /**
     * Method to build menu database entries for a component
     *
     * @return  boolean  True if successful
     *
     * @since   11.1
     */
    protected function _buildAdminMenus()
    {
        // Initialise variables.
        $db = $this->parent->getDbo();
        $table = MolajoTable::getInstance('menu');
        $option = $this->get('element');

        // If a component exists with this option in the table then we don't need to add menus
        $query = $db->getQuery(true);
        $query->select('m.id, e.extension_id');
        $query->from('#__menu AS m');
        $query->leftJoin('#__extensions AS e ON m.component_id = e.extension_id');
        $query->where('m.parent_id = 1');
        $query->where("m.application_id = 1");
        $query->where('e.element = ' . $db->quote($option));

        $db->setQuery($query);

        $componentrow = $db->loadObject();

        // Check if menu items exist
        if ($componentrow) {

            // Don't do anything if overwrite has not been enabled
            if (!$this->parent->getOverwrite()) {
                return true;
            }

            // Remove existing menu items if overwrite has been enabled
            if ($option) {
                $this->_removeAdminMenus($componentrow); // If something goes wrong, theres no way to rollback TODO: Search for better solution
            }

            $component_id = $componentrow->extension_id;
        }
        else
        {
            // Lets Find the extension id
            $query->clear();
            $query->select('e.extension_id');
            $query->from('#__extensions AS e');
            $query->where('e.element = ' . $db->quote($option));

            $db->setQuery($query);

            $component_id = $db->loadResult(); // TODO Find Some better way to discover the component_id
        }

        // Ok, now its time to handle the menus.  Start with the component root menu, then handle submenus.
        $menuElement = $this->manifest->administration->menu;

        if ($menuElement) {
            $data = array();
            $data['menutype'] = 'main';
            $data['application_id'] = 1;
            $data['title'] = (string)$menuElement;
            $data['alias'] = (string)$menuElement;
            $data['link'] = 'index.php?option=' . $option;
            $data['type'] = 'component';
            $data['published'] = 0;
            $data['parent_id'] = 1;
            $data['component_id'] = $component_id;
            $data['img'] = ((string)$menuElement->attributes()->img) ? (string)$menuElement->attributes()->img
                    : 'class:component';
            $data['home'] = 0;

            if (!$table->setLocation(1, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store()) {
                // Install failed, warn user and rollback changes
                MolajoError::raiseWarning(1, $table->getError());
                return false;
            }

            /*
                * Since we have created a menu item, we add it to the installation step stack
                * so that if we have to rollback the changes we can undo it.
                */
            $this->parent->pushStep(array('type' => 'menu', 'id' => $component_id));
        }
            // No menu element was specified, Let's make a generic menu item
        else
        {
            $data = array();
            $data['menutype'] = 'main';
            $data['application_id'] = 1;
            $data['title'] = $option;
            $data['alias'] = $option;
            $data['link'] = 'index.php?option=' . $option;
            $data['type'] = 'component';
            $data['published'] = 0;
            $data['parent_id'] = 1;
            $data['component_id'] = $component_id;
            $data['img'] = 'class:component';
            $data['home'] = 0;

            if (!$table->setLocation(1, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store()) {
                // Install failed, warn user and rollback changes
                MolajoError::raiseWarning(1, $table->getError());
                return false;
            }

            /*
                * Since we have created a menu item, we add it to the installation step stack
                * so that if we have to rollback the changes we can undo it.
                */
            $this->parent->pushStep(array('type' => 'menu', 'id' => $component_id));
        }

        $parent_id = $table->id;

        /*
           * Process SubMenus
           */

        if (!$this->manifest->administration->submenu) {
            return true;
        }

        $parent_id = $table->id;

        foreach ($this->manifest->administration->submenu->menu as $child)
        {
            $data = array();
            $data['menutype'] = 'main';
            $data['application_id'] = 1;
            $data['title'] = (string)$child;
            $data['alias'] = (string)$child;
            $data['type'] = 'component';
            $data['published'] = 0;
            $data['parent_id'] = $parent_id;
            $data['component_id'] = $component_id;
            $data['img'] = ((string)$child->attributes()->img) ? (string)$child->attributes()->img : 'class:component';
            $data['home'] = 0;

            // Set the sub menu link
            if ((string)$child->attributes()->link) {
                $data['link'] = 'index.php?' . $child->attributes()->link;
            }
            else
            {
                $request = array();

                if ((string)$child->attributes()->act) {
                    $request[] = 'act=' . $child->attributes()->act;
                }

                if ((string)$child->attributes()->task) {
                    $request[] = 'task=' . $child->attributes()->task;
                }

                if ((string)$child->attributes()->controller) {
                    $request[] = 'controller=' . $child->attributes()->controller;
                }

                if ((string)$child->attributes()->view) {
                    $request[] = 'view=' . $child->attributes()->view;
                }

                if ((string)$child->attributes()->layout) {
                    $request[] = 'layout=' . $child->attributes()->layout;
                }

                if ((string)$child->attributes()->sub) {
                    $request[] = 'sub=' . $child->attributes()->sub;
                }

                $qstring = (count($request)) ? '&' . implode('&', $request) : '';
                $data['link'] = 'index.php?option=' . $option . $qstring;
            }

            $table = MolajoTable::getInstance('menu');

            if (!$table->setLocation($parent_id, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store()) {
                // Install failed, rollback changes
                return false;
            }

            /*
                * Since we have created a menu item, we add it to the installation step stack
                * so that if we have to rollback the changes we can undo it.
                */
            $this->parent->pushStep(array('type' => 'menu', 'id' => $component_id));
        }

        return true;
    }

    /**
     * Method to remove admin menu references to a component
     *
     * @param   object  &$row  Component table object.
     *
     * @return  boolean  True if successful.
     *
     * @since   11.1
     */
    protected function _removeAdminMenus(&$row)
    {
        // Initialise Variables
        $db = $this->parent->getDbo();
        $table = MolajoTable::getInstance('menu');
        $id = $row->extension_id;

        // Get the ids of the menu items
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__menu');
        $query->where($query->qn('application_id') . ' = 1');
        $query->where($query->qn('component_id') . ' = ' . (int)$id);

        $db->setQuery($query);

        $ids = $db->loadColumn();

        // Check for error
        if ($error = $db->getErrorMsg()) {
            MolajoError::raiseWarning('', MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_REMOVING_ADMINISTER_MENUS_FAILED'));

            if ($error && $error != 1) {
                MolajoError::raiseWarning(100, $error);
            }

            return false;
        }
        elseif (!empty($ids))
        {
            // Iterate the items to delete each one.
            foreach ($ids as $menuid)
            {
                if (!$table->delete((int)$menuid)) {
                    $this->setError($table->getError());
                    return false;
                }
            }
            // Rebuild the whole tree
            $table->rebuild();

        }
        return true;
    }

    /**
     * Custom rollback method
     * - Roll back the component menu item
     *
     * @param   array  $step  Installation step to rollback.
     *
     * @return  boolean  True on success
     *
     * @since   11.1
     */
    protected function _rollback_menu($step)
    {
        return $this->_removeAdminMenus((object)array('extension_id' => $step['id']));
    }

    /**
     * Discover unregistered extensions.
     *
     * @return  array  A list of extensions.
     *
     * @since   11.1
     */
    public function discover()
    {
        $results = array();
        $site_components = JFolder::folders(MOLAJO_BASE_FOLDER . '/components');
        $admin_components = JFolder::folders(MOLAJO_BASE_FOLDER . '/components');

        foreach ($site_components as $component)
        {
            if (file_exists(MOLAJO_BASE_FOLDER . '/components/' . $component . '/' . str_replace('', '', $component) . '.xml')) {
                $manifest_details = MolajoApplicationHelper::parseXMLInstallFile(
                    MOLAJO_BASE_FOLDER . '/components/' . $component . '/' . str_replace('', '', $component) . '.xml'
                );
                $extension = MolajoTable::getInstance('extension');
                $extension->set('type', 'component');
                $extension->set('application_id', 0);
                $extension->set('element', $component);
                $extension->set('name', $component);
                $extension->set('state', -1);
                $extension->set('manifest_cache', json_encode($manifest_details));
                $results[] = $extension;
            }
        }

        foreach ($admin_components as $component)
        {
            if (file_exists(MOLAJO_BASE_FOLDER . '/components/' . $component . '/' . str_replace('', '', $component) . '.xml')) {
                $manifest_details = MolajoApplicationHelper::parseXMLInstallFile(
                    MOLAJO_BASE_FOLDER . '/components/' . $component . '/' . str_replace('', '', $component) . '.xml'
                );
                $extension = MolajoTable::getInstance('extension');
                $extension->set('type', 'component');
                $extension->set('application_id', 1);
                $extension->set('element', $component);
                $extension->set('name', $component);
                $extension->set('state', -1);
                $extension->set('manifest_cache', json_encode($manifest_details));
                $results[] = $extension;
            }
        }
        return $results;
    }

    /**
     * Install unregistered extensions that have been discovered.
     *
     * @return  mixed
     *
     * @since   11.1
     */
    public function discover_install()
    {
        // Need to find to find where the XML file is since we don't store this normally
        $client = MolajoApplicationHelper::getApplicationInfo($this->parent->extension->application_id);
        $short_element = str_replace('', '', $this->parent->extension->element);
        $manifestPath = $client->path . '/components/' . $this->parent->extension->element . '/' . $short_element . '.xml';
        $this->parent->manifest = $this->parent->isManifest($manifestPath);
        $this->parent->setPath('manifest', $manifestPath);
        $this->parent->setPath('source', $client->path . '/components/' . $this->parent->extension->element);
        $this->parent->setPath('extension_root', $this->parent->getPath('source'));

        $manifest_details = MolajoApplicationHelper::parseXMLInstallFile($this->parent->getPath('manifest'));
        $this->parent->extension->manifest_cache = json_encode($manifest_details);
        $this->parent->extension->state = 0;
        $this->parent->extension->name = $manifest_details['name'];
        $this->parent->extension->enabled = 1;
        $this->parent->extension->parameters = $this->parent->getParameters();

        try
        {
            $this->parent->extension->store();
        }
        catch (MolajoException $e)
        {
            MolajoError::raiseWarning(101, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_DISCOVER_STORE_DETAILS'));
            return false;
        }

        // now we need to run any SQL it has, languages, media or menu stuff

        // Get a database connector object
        $db = $this->parent->getDbo();

        // Get the extension manifest object
        $this->manifest = $this->parent->getManifest();

        /**
         * ---------------------------------------------------------------------------------------------
         * Manifest Document Setup Section
         * ---------------------------------------------------------------------------------------------
         */

        // Set the extensions name
        $name = strtolower(JFilterInput::getInstance()->clean((string)$this->manifest->name, 'cmd'));
        if (substr($name, 0, 4) == "") {
            $element = $name;
        }
        else
        {
            $element = "$name";
        }

        $this->set('name', $name);
        $this->set('element', $element);

        // Get the component description
        $description = (string)$this->manifest->description;

        if ($description) {
            $this->parent->set('message', MolajoTextHelper::_((string)$description));
        }
        else
        {
            $this->parent->set('message', '');
        }

        // Set the installation target paths
        $this->parent->setPath('extension_site', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $this->get('element')));
        $this->parent->setPath('extension_administrator', JPath::clean(MOLAJO_BASE_FOLDER . '/components/' . $this->get('element')));
        $this->parent->setPath('extension_root', $this->parent->getPath('extension_administrator')); // copy this as its used as a common base

        /**
         * ---------------------------------------------------------------------------------------------
         * Basic Checks Section
         * ---------------------------------------------------------------------------------------------
         */

        // Make sure that we have an admin element
        if (!$this->manifest->administration) {
            MolajoError::raiseWarning(1, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_INSTALL_ADMINISTER_ELEMENT'));
            return false;
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Installer Trigger Loading
         * ---------------------------------------------------------------------------------------------
         */
        // If there is an manifest class file, lets load it; we'll copy it later (don't have dest yet)
        $manifestScript = (string)$this->manifest->scriptfile;

        if ($manifestScript) {
            $manifestScriptFile = $this->parent->getPath('source') . '/' . $manifestScript;

            if (is_file($manifestScriptFile)) {
                // load the file
                include_once $manifestScriptFile;
            }

            // Set the class name
            $classname = $element . 'InstallerScript';

            if (class_exists($classname)) {
                // create a new instance
                $this->parent->manifestClass = new $classname($this);
                // and set this so we can copy it later
                $this->set('manifest_script', $manifestScript);

                // Note: if we don't find the class, don't bother to copy the file
            }
        }

        // Run preflight if possible (since we know we're not an update)
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'preflight')) {

            if ($this->parent->manifestClass->preflight('discover_install', $this) === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
                return false;
            }
        }

        $msg = ob_get_contents(); // create msg object; first use here
        ob_end_clean();

        // Normally we would copy files and create directories, lets skip to the optional files
        // Note: need to dereference things!
        // Parse optional tags
        //$this->parent->parseMedia($this->manifest->media);

        // We don't do language because 1.6 suggests moving to extension based languages
        //$this->parent->parseLanguages($this->manifest->languages);
        //$this->parent->parseLanguages($this->manifest->administration->languages, 1);

        /**
         * ---------------------------------------------------------------------------------------------
         * Database Processing Section
         * ---------------------------------------------------------------------------------------------
         */

        /*
           * Let's run the install queries for the component
           *	If Joomla 1.5 compatible, with discreet sql files - execute appropriate
           *	file for utf-8 support or non-utf-8 support
           */
        // Try for Joomla 1.5 type queries
        // second argument is the utf compatible version attribute
        if (isset($this->manifest->install->sql)) {
            $utfresult = $this->parent->parseSQLFiles($this->manifest->install->sql);

            if ($utfresult === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_SQL_ERROR', $db->stderr(true)));

                return false;
            }
        }

        // Time to build the admin menus
        if (!$this->_buildAdminMenus($this->parent->extension->extension_id)) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_BUILDADMINMENUS_FAILED'));

            //$this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_COMP_INSTALL_ROLLBACK', $db->stderr(true)));

            //return false;
        }

        /**
         * ---------------------------------------------------------------------------------------------
         * Custom Installation Script Section
         * ---------------------------------------------------------------------------------------------
         */

        /*
           * If we have an install script, lets include it, execute the custom
           * install method, and append the return value from the custom install
           * method to the installation message.
           */
        // start legacy support
        if ($this->get('install_script')) {

            if (is_file($this->parent->getPath('extension_administrator') . '/' . $this->get('install_script'))) {
                ob_start();
                ob_implicit_flush(false);

                require_once $this->parent->getPath('extension_administrator') . '/' . $this->get('install_script');

                if (function_exists('install')) {

                    if (install() === false) {
                        $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));
                        return false;
                    }
                }
                // Append messages
                $msg .= ob_get_contents();
                ob_end_clean();
            }
        }
        // End legacy support

        // Start Joomla! 1.6
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'discover_install')) {

            if ($this->parent->manifestClass->install($this) === false) {
                // Install failed, rollback changes
                $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_COMP_INSTALL_CUSTOM_INSTALL_FAILURE'));

                return false;
            }
        }

        $msg .= ob_get_contents(); // append messages
        ob_end_clean();

        /**
         * ---------------------------------------------------------------------------------------------
         * Finalization and Cleanup Section
         * ---------------------------------------------------------------------------------------------
         */

        // Clobber any possible pending updates
        $update = MolajoTable::getInstance('update');
        $uid = $update->find(array('element' => $this->get('element'), 'type' => 'component', 'application_id' => '', 'folder' => ''));

        if ($uid) {
            $update->delete($uid);
        }

        // And now we run the postflight
        ob_start();
        ob_implicit_flush(false);

        if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'postflight')) {
            $this->parent->manifestClass->postflight('discover_install', $this);
        }

        $msg .= ob_get_contents(); // append messages
        ob_end_clean();

        if ($msg != '') {
            $this->parent->set('extension_message', $msg);
        }

        return $this->parent->extension->extension_id;
    }

    /**
     * Refreshes the extension table cache
     *
     * @return  boolean  Result of operation, true if updated, false on failure
     *
     * @since   11.1
     */
    public function refreshManifestCache()
    {
        // Need to find to find where the XML file is since we don't store this normally
        $client = MolajoApplicationHelper::getApplicationInfo($this->parent->extension->application_id);
        $short_element = str_replace('', '', $this->parent->extension->element);
        $manifestPath = $client->path . '/components/' . $this->parent->extension->element . '/' . $short_element . '.xml';
        $this->parent->manifest = $this->parent->isManifest($manifestPath);
        $this->parent->setPath('manifest', $manifestPath);

        $manifest_details = MolajoApplicationHelper::parseXMLInstallFile($this->parent->getPath('manifest'));
        $this->parent->extension->manifest_cache = json_encode($manifest_details);
        $this->parent->extension->name = $manifest_details['name'];

        try
        {
            return $this->parent->extension->store();
        }
        catch (MolajoException $e)
        {
            MolajoError::raiseWarning(101, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_COMP_REFRESH_MANIFEST_CACHE'));
            return false;
        }
    }
}
