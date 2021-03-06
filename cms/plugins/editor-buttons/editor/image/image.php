<?php
/**
 * @version        $Id: image.php 17998 2010-07-01 19:39:08Z eddieajau $
 * @package        Joomla
 * @copyright    Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;


/**
 * Editor Image buton
 *
 * @package Editors-xtd
 * @since 1.5
 */
class plgButtonImage extends MolajoPlugin
{
    /**
     * Display the button
     *
     * @return array A two element array of (imageName, textToInsert)
     */
    function onDisplay($name)
    {
        $app = MolajoFactory::getApplication();
        $parameters = MolajoComponent::getParameters('media');
        $ranks = array('publisher', 'editor', 'author', 'registered');
        $acl = MolajoFactory::getACL();

        // TODO: Fix this ACL call
        //for($i = 0; $i < $parameters->get('allowed_media_usergroup', 3); $i++)
        //{
        //	$acl->addACL('media', 'popup', 'users', $ranks[$i]);
        //}


        // TODO: Fix this ACL call
        //Make sure the user is authorized to view this page
        $user = MolajoFactory::getUser();
        if (!$user->authorise('media.popup')) {
            //return;
        }
        $doc = MolajoFactory::getDocument();
        $template = $app->getTemplate();

        $link = 'index.php?option=media&amp;view=images&amp;layout=component&amp;e_name=' . $name;

        JHtml::_('behavior.modal');

        $button = new JObject;
        $button->set('modal', true);
        $button->set('link', $link);
        $button->set('text', MolajoTextHelper::_('PLG_IMAGE_BUTTON_IMAGE'));
        $button->set('name', 'image');
        $button->set('options', "{handler: 'iframe', size: {x: 800, y: 500}}");

        return $button;
    }
}
