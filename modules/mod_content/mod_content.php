<?php
/**
 * @version		$Id: mod_articles.php 20806 2011-02-21 19:44:59Z dextercowley $
 * @package		Joomla.Site
 * @subpackage	mod_articles
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once dirname(__FILE__).'/helper.php';

$list = modArticlesHelper::getList($params);
$layout_class_suffix = htmlspecialchars($params->get('layout_class_suffix'));

require MolajoModuleHelper::getLayoutPath('mod_articles', $params->get('layout', 'default'));