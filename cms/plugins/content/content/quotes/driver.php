<?php
/**
 * @package     Molajo
 * @subpackage  Content Plugin
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

class MolajoContentQuotes
{

    /**
     * Excerpt
     *
     * @var    string
     * @access    public
     */
    protected $excerpt;

    /**
     * Cite
     *
     * @var    string
     * @access    public
     */
    protected $cite;

    /**
     * Unique
     *
     * @var    string
     * @access    public
     */
    protected $unique;

    /**
     * MolajoContentQuotes::driver
     *
     * Implements pull quotes and blockquotes/cites

     * @param    string        The context for the content passed to the plugin.
     * @param    object        The content object.
     * @param    object        The content parameters
     * @param    string        The 'page' number
     * @param   string          Then name of the text field in the content object
     * @return    string
     * @since    1.6
     */
    function driver($context, &$content, &$parameters, $page, $location)
    {
        /** parameters **/
        $molajoSystemPlugin =& MolajoPlugin::getPlugin('system', 'molajo');
        $systemParameters = new JParameter($molajoSystemPlugin->parameters);

        /** Block Quotes **/
        if ($systemParameters->def('enable_blockquotes', 0) == 1) {
            MolajoContentQuotes::blockQuotes($context, &$content, &$parameters, $page, $location);
        }

        /** Pull Quotes **/
        if ($systemParameters->def('enable_pullquotes', 0) == 1) {
            MolajoContentQuotes::pullQuotes($context, &$content, &$parameters, $page, $location);
        }
        return;
    }

    /**
     * blockQuotes - quote stays within text
     * @param string $context
     * @param array $content
     * @param array $parameters
     * @param int $page
     * @param string $location
     * @return
     */
    function blockQuotes($context, &$content, &$parameters, $page, $location)
    {
        /** search for pullquotes **/
        preg_match_all("#{blockquote}(.*?){/blockquote}#s", $content->$location, $matches);
        if (count($matches[1]) == 0) {
            return;
        }
        $workText = $content->$location;

        for ($i = 0; $i < count($matches); $i++) {

            /** model **/
            $this->excerpt = substr($matches[0][$i], 12, strlen($matches[0][$i]) - 25);
            $this->unique = $i;

            /** cite: extract from blockquote **/
            preg_match("#{cite}(.*?){/cite}#s", $this->excerpt, $matchCite);

            if (count($matchCite) > 0) {
                $this->cite = $matchCite[1];
                $this->excerpt = str_replace($matchCite[0], '', $this->excerpt);
            } else {
                $this->cite = '';
            }

            /** layout **/
            $layoutPath = MolajoPlugin::getLayoutPath(array('type' => 'molajo', 'name' => 'content'), $layout = 'blockquote');
            $renderedLayout = MolajoPlugin::generateLayout($layoutPath);

            /** replace **/
            $workText = str_replace($matches[0][$i], $renderedLayout, $workText);
        }
        /** update source **/
        $content->$location = $workText;

        return;
    }

    /**
     * pullQuotes - quote pulled out and stays within text
     * @param string $context
     * @param array $content
     * @param array $parameters
     * @param int $page
     * @param string $location
     * @return
     */
    function pullQuotes($context, &$content, &$parameters, $page, $location)
    {
        /** search for pullquotes **/
        preg_match_all("#{pullquote}(.*?){/pullquote}#s", $content->$location, $matches);
        if (count($matches[1]) == 0) {
            return;
        }
        $workText = $content->$location;

        for ($i = 0; $i < count($matches); $i++) {

            /** model **/
            $this->excerpt = substr($matches[0][$i], 11, strlen($matches[0][$i]) - 23);
            $this->unique = $i;

            /** layout **/
            $layoutPath = MolajoPlugin::getLayoutPath(array('type' => 'molajo', 'name' => 'content'), $layout = 'pullquote');
            $renderedLayout = MolajoPlugin::generateLayout($layoutPath);

            /** replace (pullquote stay within article) **/
            $workText = str_replace($matches[0][$i], ($this->excerpt . ' ' . $renderedLayout), $workText);
        }
        /** update source **/
        $content->$location = $workText;

        return;
    }
}