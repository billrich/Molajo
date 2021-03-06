<?php
/**
 * @package     Molajo
 * @subpackage  Load Joomla Framework
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

/**
 * Joomla Defines
 */
if (defined('JPATH_PLATFORM')) {
} else {
    define('JPATH_PLATFORM', PLATFORM . '/jplatform/');
}

require_once JPATH_PLATFORM . '/platform.php';
require_once JPATH_PLATFORM . '/loader.php';
require_once MOLAJO_APPLICATIONS . '/molajo/helpers/file.php';

if (defined('_JEXEC')) {
} else {
    define('_JEXEC', 1);
}
if (defined('JPATH_BASE')) {
} else {
    define('JPATH_BASE', MOLAJO_BASE_FOLDER);
}
if (defined('JPATH_ROOT')) {
} else {
    define('JPATH_ROOT', MOLAJO_BASE_FOLDER);
}
if (defined('JPATH_CONFIGURATION')) {
} else {
    define('JPATH_CONFIGURATION', MOLAJO_SITE_PATH);
}
if (defined('JPATH_LIBRARIES')) {
} else {
    define('JPATH_LIBRARIES', PLATFORM);
}
if (defined('JOOMLA_LIBRARY')) {
} else {
    define('JOOMLA_LIBRARY', PLATFORM . '/jplatform/joomla');
}
if (defined('JPATH_SITE')) {
} else {
    define('JPATH_SITE', MOLAJO_BASE_FOLDER);
}
if (defined('JPATH_ADMINISTRATOR')) {
} else {
    define('JPATH_ADMINISTRATOR', MOLAJO_BASE_FOLDER);
}
if (defined('JPATH_PLUGINS')) {
} else {
    define('JPATH_PLUGINS', MOLAJO_CMS_PLUGINS);
}
if (defined('JPATH_CACHE')) {
} else {
    define('JPATH_CACHE', MOLAJO_SITE_PATH . '/cache');
}
if (defined('JPATH_MANIFESTS')) {
} else {
    define('JPATH_MANIFESTS', MOLAJO_CMS_MANIFESTS);
}
if (defined('JPATH_THEMES')) {
} else {
    define('JPATH_THEMES', MOLAJO_CMS_TEMPLATES);
}
if (defined('JPATH_COMPONENT')) {
} else {
    define('JPATH_COMPONENT', MOLAJO_CMS_COMPONENTS);
}

/**
 * File Subsystem
 */
require_once MOLAJO_PLATFORM . '/exceptions/error.php';
require_once MOLAJO_PLATFORM . '/exceptions/exception.php';
require_once MOLAJO_APPLICATIONS . '/molajo/helpers/text.php';
if (class_exists('JText')) {
} else {
    class JText extends MolajoTextHelper
    {
    }
}
require_once JOOMLA_LIBRARY . '/registry/registry.php';

$filehelper = new MolajoFileHelper();

$filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/path.php', 'JPath');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/file.php', 'JFile');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/folder.php', 'JFolder');

/**
 *  Base
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/base/object.php', 'JObject');
$files = JFolder::files(JOOMLA_LIBRARY . '/base', '\.php$', false, false);
foreach ($files as $file) {
    if ($file == 'adapter.php' || $file == 'adapterinstance.php' || $file == 'object.php') {
    } else {
        $filehelper->requireClassFile(JOOMLA_LIBRARY . '/base/' . $file, 'J' . ucfirst(substr($file, 0, strpos($file, '.'))));
    }
}
$filehelper->requireClassFile(MOLAJO_APPLICATIONS . '/molajo/application/language.php', 'MolajoLanguage');

/**
 *  Application
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/application/component/controller.php', 'JController');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/application/component/model.php', 'JModel');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/application/component/view.php', 'JView');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/application/input.php', 'JInput');

/**
 *  Cache
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/cache/controller.php', 'JCacheController');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/cache/storage.php', 'JCacheStorage');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/cache/cache.php', 'JCache');

$files = JFolder::files(JOOMLA_LIBRARY . '/cache/controller', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/cache/controller/' . $file, 'JCacheController' . ucfirst(substr($file, 0, strpos($file, '.'))));
}
$files = JFolder::files(JOOMLA_LIBRARY . '/cache/storage', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/cache/storage/' . $file, 'JCacheStorage' . ucfirst(substr($file, 0, strpos($file, '.'))));
}

/**
 *  Client
 *
 */
$files = JFolder::files(JOOMLA_LIBRARY . '/client', '\.php$', false, false);
foreach ($files as $file) {
    if ($file == 'ftp.php') {   /** babs cannot run this require statement - not sure why yet */
    } else if ($file == 'helper.php') {
        $filehelper->requireClassFile(JOOMLA_LIBRARY . '/client/' . $file, 'JClientHelper');
    } else {
        $filehelper->requireClassFile(JOOMLA_LIBRARY . '/client/' . $file, 'J' . ucfirst(substr($file, 0, strpos($file, '.'))));
    }
}

/**
 *  Database
 */
JLoader::register('JDatabaseQueryMySQL', JOOMLA_LIBRARY . '/database/database/mysqlquery.php');
JLoader::register('JDatabaseExporterMySQL', JOOMLA_LIBRARY . '/database/database/mysqlexporter.php');
JLoader::register('JDatabaseImporterMySQL', JOOMLA_LIBRARY . '/database/database/mysqlimporter.php');
JLoader::register('JDatabaseMySQL', JOOMLA_LIBRARY . '/database/database/mysql.php');

JLoader::register('JDatabaseQueryMySQLi', JOOMLA_LIBRARY . '/database/database/mysqliquery.php');
JLoader::register('JDatabaseExporterMySQLi', JOOMLA_LIBRARY . '/database/database/mysqlexporter.php');
JLoader::register('JDatabaseImporterMySQLi', JOOMLA_LIBRARY . '/database/database/mysqliimporter.php');
JLoader::register('JDatabaseMySQLi', JOOMLA_LIBRARY . '/database/database/mysqli.php');

JLoader::register('JDatabaseInterface', JOOMLA_LIBRARY . '/database/database.php');
JLoader::register('JDatabase', JOOMLA_LIBRARY . '/database/database.php');
JLoader::register('JDatabaseQueryElement', JOOMLA_LIBRARY . '/database/databasequery.php');
JLoader::register('JDatabaseQuery', JOOMLA_LIBRARY . '/database/databasequery.php');

/**
 *  Environment
 */
$files = JFolder::files(JOOMLA_LIBRARY . '/environment', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/environment/' . $file, 'J' . ucfirst(substr($file, 0, strpos($file, '.'))));
}

/**
 *  Error - JError deprecated; Exception classes loaded in Molajo; Log moved
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/error/profiler.php', 'JProfiler');

/**
 *  Event
 */
$files = JFolder::files(JOOMLA_LIBRARY . '/event', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/event/' . $file, 'J' . ucfirst(substr($file, 0, strpos($file, '.'))));
}

/**
 *  Filesystem (continued)
 */
$files = JFolder::files(JOOMLA_LIBRARY . '/filesystem', '\.php$', false, false);
foreach ($files as $file) {
    if ($file == 'helper.php') {
        $filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/' . $file, 'JFilesystemHelper');
    } elseif ($file == 'path.php' || $file == 'file.php' || $file == 'folder.php') {
    } elseif ($file == 'stream.php') {
    } else {
        $filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/' . $file, 'J' . ucfirst(substr($file, 0, strpos($file, '.'))));
    }
}
$files = JFolder::files(JOOMLA_LIBRARY . '/filesystem/archive', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/archive/' . $file, 'JArchive' . ucfirst(substr($file, 0, strpos($file, '.'))));
}
$files = JFolder::files(JOOMLA_LIBRARY . '/filesystem/streams', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/streams/' . $file, 'JStream' . ucfirst(substr($file, 0, strpos($file, '.'))));
}
$files = JFolder::files(JOOMLA_LIBRARY . '/filesystem/support', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/filesystem/support/' . $file, 'J' . ucfirst(substr($file, 0, strpos($file, '.'))));
}

/**
 *  Filter
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/filter/filterinput.php', 'JFilterInput');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/filter/filteroutput.php', 'JFilterOutput');

/**
 *  Log
 */
$files = JFolder::files(JOOMLA_LIBRARY . '/log', '\.php$', false, false);
foreach ($files as $file) {
    if ($file == 'logexception.php') {
    } else {
        $filehelper->requireClassFile(JOOMLA_LIBRARY . '/log/' . $file, 'J' . ucfirst(substr($file, 0, strpos($file, '.'))));
    }
}
$files = JFolder::files(JOOMLA_LIBRARY . '/log/loggers', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/log/loggers/' . $file, 'JLogger' . ucfirst(substr($file, 0, strpos($file, '.'))));
}

/**
 *  Registry
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/registry/format.php', 'JRegistryFormat');
$files = JFolder::files(JOOMLA_LIBRARY . '/registry/format', '\.php$', false, false);
foreach ($files as $file) {
    $filehelper->requireClassFile(JOOMLA_LIBRARY . '/registry/format/' . $file, 'JRegistryFormat' . strtoupper(substr($file, 0, strpos($file, '.'))));
}

/**
 *  String
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/string/string.php', 'JString');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/string/stringnormalize.php', 'JStringNormalize');

/**
 *  Utilities
 */
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/utilities/arrayhelper.php', 'JArrayHelper');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/utilities/buffer.php', 'JBuffer');
$filehelper->requireClassFile(JOOMLA_LIBRARY . '/utilities/date.php', 'JDate');

/**
 *  PHPMailer
 */
$filehelper->requireClassFile(PLATFORM . '/jplatform/phpmailer/phpmailer.php', 'PHPMailer');

/**
 *  JRequest Clean
 */
if (isset($_SERVER['HTTP_HOST'])) {
    if (defined('_JREQUEST_NO_CLEAN')) {
    } else {
        JRequest::clean();
    }
}
