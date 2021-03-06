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
 * Template installer
 *
 * @package     Joomla.Platform
 * @subpackage  Installer
 * @since       11.1
 */
class MolajoInstallerAdapterTemplate extends MolajoAdapterInstance
{
    protected $name = null;

    protected $element = null;

    protected $route = 'install';

    /**
     * Custom loadLanguage method
     *
     * @param   string  $path  The path where to find language files.
     *
     * @return  MolajoInstallerAdapterTemplate
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
                ($this->parent->extension->application_id ? MOLAJO_BASE_FOLDER
                        : MOLAJO_BASE_FOLDER) . '/templates/' . $this->parent->extension->element
            );
        }

        $clientId = isset($this->parent->extension) ? $this->parent->extension->application_id : 0;
        $this->manifest = $this->parent->getManifest();
        $name = strtolower(JFilterInput::getInstance()->clean((string)$this->manifest->name, 'cmd'));
        $client = (string)$this->manifest->attributes()->client;

        // Load administrator language if not set.
        if (!$client) {
            $client = 'ADMINISTRATOR';
        }

        $extension = "template_$name";
        $lang = MolajoFactory::getLanguage();
        $source = $path ? $path : ($this->parent->extension->application_id ? MOLAJO_BASE_FOLDER
                : MOLAJO_BASE_FOLDER) . '/templates/' . $name;
        $lang->load($extension . '.sys', $source, null, false, false)
        || $lang->load($extension . '.sys', constant('MOLAJO_SITE_' . strtoupper($client)), null, false, false)
        || $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
        || $lang->load($extension . '.sys', constant('MOLAJO_SITE_' . strtoupper($client)), $lang->getDefault(), false, false);
    }

    /**
     * Custom install method
     *
     * @return  boolean  True on success
     *
     * @since   11.1
     */
    public function install()
    {
        $lang = MolajoFactory::getLanguage();
        $xml = $this->parent->getManifest();

        // Get the client application target
        if ($cname = (string)$xml->attributes()->client) {
            // Attempt to map the client to a base path
            jimport('joomla.application.helper');
            $client = MolajoApplicationHelper::getApplicationInfo($cname, true);
            if ($client === false) {
                $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_UNKNOWN_CLIENT', $cname));
                return false;
            }
            $basePath = $client->path;
            $clientId = $client->id;
        }
        else
        {
            // No client attribute was found so we assume the site as the client
            $cname = 'site';
            $basePath = MOLAJO_BASE_FOLDER;
            $clientId = 0;
        }

        // Set the extension's name
        $name = JFilterInput::getInstance()->clean((string)$xml->name, 'cmd');

        $element = strtolower(str_replace(" ", "_", $name));
        $this->set('name', $name);
        $this->set('element', $element);

        $db = $this->parent->getDbo();
        $db->setQuery('SELECT extension_id FROM #__extensions WHERE type="template" AND element = "' . $element . '"');
        $id = $db->loadResult();

        // Set the template root path
        $this->parent->setPath('extension_root', $basePath . '/templates/' . $element);

        // if it's on the fs...
        if (file_exists($this->parent->getPath('extension_root')) && (!$this->parent->getOverwrite() || $this->parent->getUpgrade())) {
            $updateElement = $xml->update;
            // Upgrade manually set or
            // Update function available or
            // Update tag detected
            if ($this->parent->getUpgrade() || ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'update'))
                || is_a($updateElement, 'JXMLElement')
            ) {
                // Force this one
                $this->parent->setOverwrite(true);
                $this->parent->setUpgrade(true);
                if ($id) { // if there is a matching extension mark this as an update; semantics really
                    $this->route = 'update';
                }
            }
            elseif (!$this->parent->getOverwrite())
            {
                // Overwrite is not set
                // If we didn't have overwrite set, find an udpate function or find an update tag so let's call it safe
                $this->parent
                        ->abort(
                    MolajoTextHelper::sprintf(
                        'JLIB_INSTALLER_ABORT_PLG_INSTALL_DIRECTORY', MolajoTextHelper::_('JLIB_INSTALLER_' . $this->route),
                        $this->parent->getPath('extension_root')
                    )
                );
                return false;
            }
        }

        /*
           * If the template directory already exists, then we will assume that the template is already
           * installed or another template is using that directory.
           */
        if (file_exists($this->parent->getPath('extension_root')) && !$this->parent->getOverwrite()) {
            MolajoError::raiseWarning(
                100,
                MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_ANOTHER_TEMPLATE_USING_DIRECTORY', $this->parent->getPath('extension_root'))
            );
            return false;
        }

        // If the template directory does not exist, let's create it
        $created = false;
        if (!file_exists($this->parent->getPath('extension_root'))) {
            if (!$created = JFolder::create($this->parent->getPath('extension_root'))) {
                $this->parent
                        ->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_FAILED_CREATE_DIRECTORY', $this->parent->getPath('extension_root')));

                return false;
            }
        }

        // If we created the template directory and will want to remove it if we have to roll back
        // the installation, let's add it to the installation step stack
        if ($created) {
            $this->parent->pushStep(array('type' => 'folder', 'path' => $this->parent->getPath('extension_root')));
        }

        // Copy all the necessary files
        if ($this->parent->parseFiles($xml->files, -1) === false) {
            // Install failed, rollback changes
            $this->parent->abort();

            return false;
        }

        if ($this->parent->parseFiles($xml->images, -1) === false) {
            // Install failed, rollback changes
            $this->parent->abort();

            return false;
        }

        if ($this->parent->parseFiles($xml->css, -1) === false) {
            // Install failed, rollback changes
            $this->parent->abort();

            return false;
        }

        // Parse optional tags
        $this->parent->parseMedia($xml->media);
        $this->parent->parseLanguages($xml->languages, $clientId);

        // Get the template description
        $this->parent->set('message', MolajoTextHelper::_((string)$xml->description));

        // Lastly, we will copy the manifest file to its appropriate place.
        if (!$this->parent->copyManifest(-1)) {
            // Install failed, rollback changes
            $this->parent->abort(MolajoTextHelper::_('JLIB_INSTALLER_ABORT_TPL_INSTALL_COPY_SETUP'));

            return false;
        }

        // Extension Registration

        $row = MolajoTable::getInstance('extension');

        if ($this->route == 'update' && $id) {
            $row->load($id);
        }
        else
        {
            $row->type = 'template';
            $row->element = $this->get('element');
            // There is no folder for templates
            $row->folder = '';
            $row->enabled = 1;
            $row->protected = 0;
            $row->access = 1;
            $row->application_id = $clientId;
            $row->parameters = $this->parent->getParameters();
            $row->custom_data = ''; // custom data
        }
        $row->name = $this->get('name'); // name might change in an update
        $row->manifest_cache = $this->parent->generateManifestCache();

        if (!$row->store()) {
            // Install failed, roll back changes
            $this->parent->abort(MolajoTextHelper::sprintf('JLIB_INSTALLER_ABORT_TPL_INSTALL_ROLLBACK', $db->stderr(true)));

            return false;
        }

        if ($this->route == 'install') {
            //insert record in #__template_styles
            $query = $db->getQuery(true);
            $query->insert('#__template_styles');
            $query->set('template=' . $db->Quote($row->element));
            $query->set('application_id=' . $db->Quote($clientId));
            $query->set('home=0');
            $debug = $lang->setDebug(false);
            $query->set('title=' . $db->Quote(MolajoTextHelper::sprintf('JLIB_INSTALLER_DEFAULT_STYLE', MolajoTextHelper::_($this->get('name')))));
            $lang->setDebug($debug);
            $query->set('parameters=' . $db->Quote($row->parameters));
            $db->setQuery($query);
            // There is a chance this could fail but we don't care...
            $db->query();
        }

        return $row->get('extension_id');
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
        return $this->install();
    }

    /**
     * Custom uninstall method
     *
     * @param   integer  $id  The extension ID
     *
     * @return  boolean  True on success
     *
     * @since   11.1
     */
    public function uninstall($id)
    {
        // Initialise variables.
        $retval = true;

        // First order of business will be to load the template object table from the database.
        // This should give us the necessary information to proceed.
        $row = MolajoTable::getInstance('extension');

        if (!$row->load((int)$id) || !strlen($row->element)) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_ERRORUNKOWNEXTENSION'));
            return false;
        }

        // Is the template we are trying to uninstall a core one?
        // Because that is not a good idea...
        if ($row->protected) {
            MolajoError::raiseWarning(100, MolajoTextHelper::sprintf('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_WARNCORETEMPLATE', $row->name));
            return false;
        }

        $name = $row->element;
        $clientId = $row->application_id;

        // For a template the id will be the template name which represents the subfolder of the templates folder that the template resides in.
        if (!$name) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_TEMPLATE_ID_EMPTY'));

            return false;
        }

        // Deny remove default template
        $db = $this->parent->getDbo();
        $query = 'SELECT COUNT(*) FROM #__template_styles' . ' WHERE home = 1 AND template = ' . $db->Quote($name);
        $db->setQuery($query);

        if ($db->loadResult() != 0) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_TEMPLATE_DEFAULT'));

            return false;
        }

        // Get the template root path
        $client = MolajoApplicationHelper::getApplicationInfo($clientId);

        if (!$client) {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_INVALID_CLIENT'));
            return false;
        }

        $this->parent->setPath('extension_root', $client->path . '/templates/' . strtolower($name));
        $this->parent->setPath('source', $this->parent->getPath('extension_root'));

        // We do findManifest to avoid problem when uninstalling a list of extensions: getManifest cache its manifest file
        $this->parent->findManifest();
        $manifest = $this->parent->getManifest();
        if (!($manifest instanceof JXMLElement)) {
            // Kill the extension entry
            $row->delete($row->extension_id);
            unset($row);
            // Make sure we delete the folders
            JFolder::delete($this->parent->getPath('extension_root'));
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_INVALID_NOTFOUND_MANIFEST'));

            return false;
        }

        // Remove files
        $this->parent->removeFiles($manifest->media);
        $this->parent->removeFiles($manifest->languages, $clientId);

        // Delete the template directory
        if (JFolder::exists($this->parent->getPath('extension_root'))) {
            $retval = JFolder::delete($this->parent->getPath('extension_root'));
        }
        else
        {
            MolajoError::raiseWarning(100, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_UNINSTALL_TEMPLATE_DIRECTORY'));
            $retval = false;
        }

        // Set menu that assigned to the template back to default template
        $query = 'UPDATE #__menu INNER JOIN #__template_styles' . ' ON #__template_styles.id = #__menu.template_id'
                 . ' SET #__menu.template_id = 0' . ' WHERE #__template_styles.template = ' . $db->Quote(strtolower($name))
                 . ' AND #__template_styles.application_id = ' . $db->Quote($clientId);
        $db->setQuery($query);
        $db->Query();

        $query = 'DELETE FROM #__template_styles' . ' WHERE template = ' . $db->Quote($name) . ' AND application_id = ' . $db->Quote($clientId);
        $db->setQuery($query);
        $db->Query();

        $row->delete($row->extension_id);
        unset($row);

        return $retval;
    }

    /**
     * Discover existing but uninstalled templates
     *
     * @return  array  JExtensionTable list
     */
    function discover()
    {
        $results = array();
        $site_list = JFolder::folders(MOLAJO_BASE_FOLDER . '/templates');
        $admin_list = JFolder::folders(MOLAJO_BASE_FOLDER . '/templates');
        $site_info = MolajoApplicationHelper::getApplicationInfo('site', true);
        $admin_info = MolajoApplicationHelper::getApplicationInfo('administrator', true);

        foreach ($site_list as $template)
        {
            if ($template == 'system') {
                continue;

                // Ignore special system template
            }
            $manifest_details = MolajoApplicationHelper::parseXMLInstallFile(MOLAJO_BASE_FOLDER . "/templates/$template/templateDetails.xml");
            $extension = MolajoTable::getInstance('extension');
            $extension->set('type', 'template');
            $extension->set('application_id', $site_info->id);
            $extension->set('element', $template);
            $extension->set('name', $template);
            $extension->set('state', -1);
            $extension->set('manifest_cache', json_encode($manifest_details));
            $results[] = $extension;
        }

        foreach ($admin_list as $template)
        {
            if ($template == 'system') {
                continue;

                // Ignore special system template
            }

            $manifest_details = MolajoApplicationHelper::parseXMLInstallFile(MOLAJO_BASE_FOLDER . "/templates/$template/templateDetails.xml");
            $extension = MolajoTable::getInstance('extension');
            $extension->set('type', 'template');
            $extension->set('application_id', $admin_info->id);
            $extension->set('element', $template);
            $extension->set('name', $template);
            $extension->set('state', -1);
            $extension->set('manifest_cache', json_encode($manifest_details));
            $results[] = $extension;
        }

        return $results;
    }

    /**
     * Discover_install
     * Perform an install for a discovered extension
     *
     * @return boolean
     *
     * @since 11.1
     */
    function discover_install()
    {
        // Templates are one of the easiest
        // If its not in the extensions table we just add it
        $client = MolajoApplicationHelper::getApplicationInfo($this->parent->extension->application_id);
        $manifestPath = $client->path . '/templates/' . $this->parent->extension->element . '/templateDetails.xml';
        $this->parent->manifest = $this->parent->isManifest($manifestPath);
        $description = (string)$this->parent->manifest->description;

        if ($description) {
            $this->parent->set('message', MolajoTextHelper::_($description));
        }
        else
        {
            $this->parent->set('message', '');
        }

        $this->parent->setPath('manifest', $manifestPath);
        $manifest_details = MolajoApplicationHelper::parseXMLInstallFile($this->parent->getPath('manifest'));
        $this->parent->extension->manifest_cache = json_encode($manifest_details);
        $this->parent->extension->state = 0;
        $this->parent->extension->name = $manifest_details['name'];
        $this->parent->extension->enabled = 1;

        $data = new JObject;

        foreach ($manifest_details as $key => $value)
        {
            $data->set($key, $value);
        }

        $this->parent->extension->parameters = $this->parent->getParameters();

        if ($this->parent->extension->store()) {
            //insert record in #__template_styles
            $db = $this->parent->getDbo();
            $query = $db->getQuery(true);
            $query->insert('#__template_styles');
            $query->set('template=' . $db->Quote($this->parent->extension->name));
            $query->set('application_id=' . $db->Quote($this->parent->extension->application_id));
            $query->set('home=0');
            $query->set('title=' . $db->Quote(MolajoTextHelper::sprintf('JLIB_INSTALLER_DEFAULT_STYLE', $this->parent->extension->name)));
            $query->set('parameters=' . $db->Quote($this->parent->extension->parameters));
            $db->setQuery($query);
            $db->query();

            return $this->parent->extension->get('extension_id');
        }
        else
        {
            MolajoError::raiseWarning(101, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_DISCOVER_STORE_DETAILS'));

            return false;
        }
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
        // Need to find to find where the XML file is since we don't store this normally.
        $client = MolajoApplicationHelper::getApplicationInfo($this->parent->extension->application_id);
        $manifestPath = $client->path . '/templates/' . $this->parent->extension->element . '/templateDetails.xml';
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
            MolajoError::raiseWarning(101, MolajoTextHelper::_('JLIB_INSTALLER_ERROR_TPL_REFRESH_MANIFEST_CACHE'));
            return false;
        }
    }
}
