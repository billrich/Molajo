<?php
/**
 * @version        $Id: joomla.php 21097 2011-04-07 15:38:03Z dextercowley $
 * @copyright    Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('MOLAJO') or die;

/**
 * Example Content Plugin
 *
 * @package        Joomla.Plugin
 * @subpackage    Content.joomla
 * @since        1.6
 */
class plgContentJoomla extends MolajoPlugin
{
    /**
     * Example after save content method
     * Article is passed by reference, but after the save, so no changes will be saved.
     * Method is called right after the content is saved
     *
     * @param    string        The context of the content passed to the plugin (added in 1.6)
     * @param    object        A JTableContent object
     * @param    bool        If the content is just about to be created
     * @since    1.6
     */
    public function onContentAfterSave($context, &$article, $isNew)
    {
        // Check we are handling the frontend edit form.
        if ($context != 'articles.form') {
            return true;
        }

        // Check if this function is enabled.
        if (!$this->parameters->def('email_new_fe', 1)) {
            return true;
        }

        // Check this is a new article.
        if (!$isNew) {
            return true;
        }

        $user = MolajoFactory::getUser();

        // Messaging for new items
        JModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/messages/models', 'MessagesModel');
        JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/messages/tables');

        $db = MolajoFactory::getDbo();
        $db->setQuery('SELECT id FROM #__users WHERE send_email = 1');
        $users = (array)$db->loadResultArray();

        $default_language = MolajoComponent::getParameters('languages')->get('administrator');
        $debug = MolajoFactory::getConfig()->get('debug_language');

        foreach ($users as $user_id)
        {
            if ($user_id != $user->id) {
                // Load language for messaging
                $receiver = MolajoUser::getInstance($user_id);
                $lang = JLanguage::getInstance($receiver->getParam('admin_language', $default_language), $debug);
                $lang->load('articles');
                $message = array(
                    'user_id_to' => $user_id,
                    'subject' => $lang->_('CONTENT_NEW_ARTICLE'),
                    'message' => sprintf($lang->_('CONTENT_ON_NEW_CONTENT'), $user->get('name'), $article->title)
                );
                $model_message = JModel::getInstance('Message', 'MessagesModel');
                $model_message->save($message);
            }
        }

        return true;
    }

    /**
     * Don't allow categories to be deleted if they contain items or subcategories with items
     *
     * @param    string    The context for the content passed to the plugin.
     * @param    object    The data relating to the content that was deleted.
     * @return    boolean
     * @since    1.6
     */
    public function onContentBeforeDelete($context, $data)
    {
        // Skip plugin if we are deleting something other than categories
        if ($context != 'categories.category') {
            return true;
        }

        // Check if this function is enabled.
        if (!$this->parameters->def('check_categories', 1)) {
            return true;
        }

        $extension = JRequest::getString('extension');

        // Default to true if not a core extension
        $result = true;

        $tableInfo = array(
            'banners' => array('table_name' => '#__banners'),
            'contact' => array('table_name' => '#__contact_details'),
            'articles' => array('table_name' => '#__content'),
            'newsfeeds' => array('table_name' => '#__newsfeeds'),
            'weblinks' => array('table_name' => '#__weblinks')
        );

        // Now check to see if this is a known core extension
        if (isset($tableInfo[$extension])) {
            // Get table name for known core extensions
            $table = $tableInfo[$extension]['table_name'];
            // See if this category has any content items
            $count = $this->_countItemsInCategory($table, $data->get('id'));
            // Return false if db error
            if ($count === false) {
                $result = false;
            }
            else
            {
                // Show error if items are found in the category
                if ($count > 0) {
                    $msg = MolajoTextHelper::sprintf('CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
                           MolajoTextHelper::plural('CATEGORIES_N_ITEMS_ASSIGNED', $count);
                    JError::raiseWarning(403, $msg);
                    $result = false;
                }
                // Check for items in any child categories (if it is a leaf, there are no child categories)
                if (!$data->isLeaf()) {
                    $count = $this->_countItemsInChildren($table, $data->get('id'), $data);
                    if ($count === false) {
                        $result = false;
                    }
                    elseif ($count > 0)
                    {
                        $msg = MolajoTextHelper::sprintf('CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
                               MolajoTextHelper::plural('CATEGORIES_HAS_SUBCATEGORY_ITEMS', $count);
                        JError::raiseWarning(403, $msg);
                        $result = false;
                    }
                }
            }

            return $result;
        }
    }

    /**
     * Get count of items in a category
     *
     * @param    string    table name of component table (column is catid)
     * @param    int        id of the category to check
     * @return    mixed    count of items found or false if db error
     * @since    1.6
     */
    private function _countItemsInCategory($table, $catid)
    {
        $db = MolajoFactory::getDbo();
        $query = $db->getQuery(true);
        // Count the items in this category
        $query->select('COUNT(id)');
        $query->from($table);
        $query->where('catid = ' . $catid);
        $db->setQuery($query);
        $count = $db->loadResult();

        // Check for DB error.
        if ($error = $db->getErrorMsg()) {
            JError::raiseWarning(500, $error);
            return false;
        }
        else {
            return $count;
        }
    }

    /**
     * Get count of items in a category's child categories
     *
     * @param    string    table name of component table (column is catid)
     * @param    int        id of the category to check
     * @return    mixed    count of items found or false if db error
     * @since    1.6
     */
    private function _countItemsInChildren($table, $catid, $data)
    {
        $db = MolajoFactory::getDbo();
        // Create subquery for list of child categories
        $childCategoryTree = $data->getTree();
        // First element in tree is the current category, so we can skip that one
        unset($childCategoryTree[0]);
        $childCategoryIds = array();
        foreach ($childCategoryTree as $node) {
            $childCategoryIds[] = $node->id;
        }

        // Make sure we only do the query if we have some categories to look in
        if (count($childCategoryIds)) {
            // Count the items in this category
            $query = $db->getQuery(true);
            $query->select('COUNT(id)');
            $query->from($table);
            $query->where('catid IN (' . implode(',', $childCategoryIds) . ')');
            $db->setQuery($query);
            $count = $db->loadResult();

            // Check for DB error.
            if ($error = $db->getErrorMsg()) {
                JError::raiseWarning(500, $error);
                return false;
            }
            else
            {
                return $count;
            }
        }
        else
            // If we didn't have any categories to check, return 0
        {
            return 0;
        }
    }
}
