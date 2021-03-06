<?php
/**
 * @version        $Id: menus.php 20196 2011-01-09 02:40:25Z ian $
 * @copyright    Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Menus component helper.
 *
 * @package      Joomla.Administrator
 * @subpackage   menus
 * @since        1.6
 */
class MolajoMenuFORCOMPONENT
{
    /**
     * Defines the valid request variables for the reverse lookup.
     */
    protected static $_filter = array('option', 'view', 'layout');

    /**
     * Configure the Linkbar.
     *
     * @param    string    The name of the active view.
     */
    public static function addSubmenu($vName)
    {
        JSubMenuHelper::addEntry(
            MolajoTextHelper::_('MENU_SUBMENU_MENUS'),
            'index.php?option=menus&view=menus',
            $vName == 'menus'
        );
        JSubMenuHelper::addEntry(
            MolajoTextHelper::_('MENU_SUBMENU_ITEMS'),
            'index.php?option=menus&view=items',
            $vName == 'items'
        );
    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @param    int        The menu ID.
     *
     * @return    JObject
     * @since    1.6
     */
    public static function getActions($parentId = 0)
    {
        $user = MolajoFactory::getUser();
        $result = new JObject;

        if (empty($parentId)) {
            $assetName = 'menus';
        } else {
            $assetName = 'menus.item.' . (int)$parentId;
        }

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }

    /**
     * Gets a standard form of a link for lookups.
     *
     * @param    mixed    A link string or array of request variables.
     *
     * @return    mixed    A link in standard option-view-layout form, or false if the supplied response is invalid.
     */
    public static function getLinkKey($request)
    {
        if (empty($request)) {
            return false;
        }

        // Check if the link is in the form of index.php?...
        if (is_string($request)) {
            $args = array();
            if (strpos($request, 'index.php') === 0) {
                parse_str(parse_url(htmlspecialchars_decode($request), PHP_URL_QUERY), $args);
            }
            else {
                parse_str($request, $args);
            }
            $request = $args;
        }

        // Only take the option, view and layout parts.
        foreach ($request as $name => $value)
        {
            if (!in_array($name, self::$_filter)) {
                // Remove the variables we want to ignore.
                unset($request[$name]);
            }
        }

        ksort($request);

        return 'index.php?' . http_build_query($request, '', '&');
    }

    /**
     * Get the menu list for create a menu module
     *
     * @return        array    The menu array list
     * @since        1.6
     */
    public static function getMenuTypes()
    {
        $db = MolajoFactory::getDbo();
        $db->setQuery('SELECT a.menu_id FROM #__menus AS a');
        return $db->loadResultArray();
    }

    /**
     * Get a list of menu links for one or all menus.
     *
     * @param    string    An option menu to filter the list on, otherwise all menu links are returned as a grouped array.
     * @param    int        An optional parent ID to pivot results around.
     * @param    int        An optional mode. If parent ID is set and mode=2, the parent and children are excluded from the list.
     * @param    array    An optional array of states
     */
    public static function getMenuLinks($menuType = null, $parentId = 0, $mode = 0, $published = array())
    {
        $db = MolajoFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id AS value, a.title AS text, a.lvl, a.menu_id, a.type');
        $query->select('a.template_id, a.checked_out');
        $query->from('#__content AS a');
        $query->join('LEFT', '`#__content` AS b ON a.lft > b.lft AND a.rgt < b.rgt');

        // Filter by the type
        if ($menuType) {
            $query->where('(a.menu_id = ' . $db->quote($menuType) . ' OR a.parent_id = 0)');
        }

        if ($parentId) {
            if ($mode == 2) {
                // Prevent the parent and children from showing.
                $query->join('LEFT', '`#__content` AS p ON p.id = ' . (int)$parentId);
                $query->where('(a.lft <= p.lft OR a.rgt >= p.rgt)');
            }
        }

        if (empty($published)) {
        } else {
            if (is_array($published)) {
                $published = '(' . implode(',', $published) . ')';
            }
            $query->where('a.published IN ' . $published);
        }

        $query->group('a.id');
        $query->order('a.lft ASC');

        // Get the options.
        $db->setQuery($query);

        $links = $db->loadObjectList();

        // Check for a database error.
        if ($error = $db->getErrorMsg()) {
            MolajoError::raiseWarning(500, $error);
            return false;
        }

        // Pad the option text with spaces using depth lvl as a multiplier.
        foreach ($links as &$link) {
            $link->text = str_repeat('- ', $link->lvl) . $link->text;
        }

        if (empty($menuType)) {
            // If the menu_id is empty, group the items by menu_id.
            $query->clear();
            $query->select('*');
            $query->from('#__menus');
            $query->where('menu_id <> ' . $db->quote(''));
            $query->order('title, menu_id');
            $db->setQuery($query);

            $menuTypes = $db->loadObjectList();

            // Check for a database error.
            if ($error = $db->getErrorMsg()) {
                MolajoError::raiseWarning(500, $error);
                return false;
            }

            // Create a reverse lookup and aggregate the links.
            $rlu = array();
            foreach ($menuTypes as &$type) {
                $rlu[$type->menu_id] = &$type;
                $type->links = array();
            }

            // Loop through the list of menu links.
            foreach ($links as &$link) {
                if (isset($rlu[$link->menu_id])) {
                    $rlu[$link->menu_id]->links[] = &$link;

                    // Cleanup garbage.
                    unset($link->menu_id);
                }
            }

            return $menuTypes;
        } else {
            return $links;
        }
    }
}