<?php
/**
 * @version     $id: molajosample.php
 * @package     Molajo
 * @subpackage  Molajosample Plugin
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

/**
 * Molajosample Query Plugin
 *
 * @package        Molajo
 * @subpackage    Query Plugin
 * @since        1.6
 */
class plgQueryMolajosample extends MolajoPlugin
{

    /**
     * Query Events - Events in order of occurrence
     *
     * - onQueryPopulateState - passes in full filterset, can add or modify
     *
     * - create query (called from View)
     *      - triggers onQueryBeforeQuery event, passing in the Query object
     *
     * - run query
     *      - triggers onQueryAfterQuery event, passing in the full query resultset
     *
     * - loops through the recordset
     *
     *      - triggers onQueryBeforeItem event, passing in the new item in the recordset
     *
     *      - creates 'added value' fields, like author, permanent URL, etc.
     *
     *      - remove items due to post query examination
     *
     *      - triggers onQueryAfterItem event, passing in the current item with added value fields
     *
     * - loop complete
     *      - triggers onQueryComplete event, passing in the resultset, less items removed, with augmented data
     *
     *      - Returns resultset to the View
     *
     */

    /**
     * 1. onQueryPopulateState
     *
     * passes in full filter set, can add or modify
     *
     * @param    object    $state               Array of request variables, filters, list objects
     * @param    object    $parameters              Array of parameters
     *
     *  echo $state->get('request.layout');
     *
     *  foreach ($request_variables as $name => $value) {
     *      echo $value.'<br />';
     *  }
     *
     *  $state->set('request.layout', 'manager');
     *
     * @since    1.0
     *
     */
    public function onQueryPopulateState(&$state, &$parameters)
    {
        return true;
    }

    /**
     * 2. onQueryBeforeQuery
     *
     * passes in query object to be modified,
     *
     * @param    object    $state      Array of request variables, filters, list objects
     * @param    object    $query      Model Query Object prior to executing the query
     * @param    object    $parameters     The content parameters
     *
     * @since    1.6
     */
    public function onQueryBeforeQuery(&$state, &$query, &$parameters)
    {
        return true;
    }

    /**
     * 3. onQueryAfterQuery
     *
     * after query has been executed, full recordset passed into this event
     *
     * @param    string    $state      Array of request variables, filters, list objects
     * @param    object    $resultset  Full query resultset
     * @param    object    $parameters     The content parameters
     *
     * foreach ($resultset as $item) {
     *      echo $item->access;
     *      $item->access = 9;
     *  }
     *
     * @since    1.6
     */
    public function onQueryAfterQuery(&$state, &$resultset, &$parameters)
    {
        return true;
    }

    /**
     * 4. onQueryBeforeItem
     *
     * single item from query resultset, can add columns or modify values
     *
     * @param    string    $state      Array of request variables, filters, list objects
     * @param    object    $item       Single resultset item
     * @param    object    $parameters     The content parameters
     * @param    boolean    $keep       Will the row be returned to the layout?
     *
     * @since    1.6
     */
    public function onQueryBeforeItem(&$state, &$item, &$parameters, &$keep)
    {
        return true;
    }

    /**
     * 5. onQueryAfterItem
     *
     * single item from query resultset, after processed by (possibly) adding values
     *  and determining if it should be passed to view, or not
     *
     * Method is called by the model
     *
     * @param    string    $state      Array of request variables, filters, list objects
     * @param    object    $item       Single resultset item
     * @param    object    $parameters     The content parameters
     * @param    boolean    $keep       Will the row be returned to the layout?
     *
     * @since    1.6
     */
    public function onQueryAfterItem(&$state, &$item, &$parameters, &$keep)
    {
        return true;
    }

    /**
     * 6. onQueryComplete
     *
     * passes in full filter set, can add or modify
     *
     * Method is called by the model
     *
     * @param    string    $state      Array of request variables, filters, list objects
     * @param    object    $resultset  Full query resultset
     * @param    object    $parameters     The content parameters
     *
     * @since    1.6
     */
    public function onQueryComplete(&$state, &$resultset, &$parameters)
    {
        return true;
    }
}
