<?php
/**
 * @package     Molajo
 * @subpackage  Molajo Protect Mollom Plugin
 * @copyright   Copyright (C) 2012 Amy Stephen. All rights reserved.
 * @license     GNU General Public License Version 2, or later http://www.gnu.org/licenses/gpl.html
 */
defined('MOLAJO') or die;

class ProtectRecaptcha
{


    function invokeSpamProtectionCheck($comment_captcha,
        $referer,
        $recaptcha_challenge_field,
        $recaptcha_response_field,
        $mollomresponse)
    {

        /**
         *     Retrieve User Group Parameter for Auto Publish
         */
        $tamkaLibraryPlugin =& MolajoPlugin::getPlugin('system', 'tamka');
        $tamkaLibraryPluginParameters = new JParameter($tamkaLibraryPlugin->parameters);
        $spamProtectionOption = $tamkaLibraryPluginParameters->def('spamprevention', '1');

        if ($spamProtectionOption == '0') {
            return 0;
        }

        /**
         *     1 - Recaptcha
         */
        if ($spamProtectionOption == '1') {

            tamkaimport('tamka.spam.recaptcha.recaptchalib');

            $recaptchaprivatekey = $tamkaLibraryPluginParameters->def('recaptchaprivatekey', '');
            $response = recaptcha_check_answer($recaptchaprivatekey,
                                               $referer,
                                               $recaptcha_challenge_field,
                                               $recaptcha_response_field);

            if ($response->is_valid) {
                return 0;
            } else {
                global $mainframe;
                $mainframe->enqueueMessage(MolajoTextHelper::_('Invalid Re-captcha code entered. Please try, again.'));
                return 3;
            }

            /**
             *     2 - Akismet
             */
        } else if ($spamProtectionOption == '2') {

            tamkaimport('tamka.spam.akismet.Akismetclass');

            $akismetkey = $tamkaLibraryPluginParameters->get('akismetkey');

            $uri = &MolajoFactory::getURI();
            $url = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path'));
            $akismet = new Akismet($url, $akismetkey);

            $session =& MolajoFactory::getSession();

            $akismet->setCommentAuthor($session->get('comment_author_name', null, 'responses'));
            $akismet->setCommentAuthorEmail($session->get('comment_author_email', null, 'responses'));
            $akismet->setCommentAuthorURL($session->get('comment_author_url', null, 'responses'));
            $akismet->setCommentContent($session->get('comment_body', null, 'responses'));
            $akismet->setPermalink($session->get('component_url', null, 'responses'));

            if ($akismet->isCommentSpam()) {
                global $mainframe;
                $mainframe->enqueueMessage(MolajoTextHelper::_('Comment identified as Spam by Akismet.'));
                $published = 2;
            }

            /**
             *     3 - Mollom
             */
        } else if ($spamProtectionOption == '3') {
            tamkaimport('tamka.spam.mollom.mollom');
            $session =& MolajoFactory::getSession();

            $mollompublickey = $tamkaLibraryPluginParameters->get('mollompublickey');
            $mollomprivatekey = $tamkaLibraryPluginParameters->get('mollomprivatekey');

            Mollom::setPublicKey($mollompublickey);
            Mollom::setPrivateKey($mollomprivatekey);
            $servers = Mollom::getServerList();
            Mollom::setServerList($servers);

            //			$mollom_challenge_response = $session->get('mollom_challenge_response', false, 'responses');
            $session->set('mollom_challenge_response', false, 'responses');

            //			if ($mollom_challenge_response) {
            //				if (Mollom::checkCaptcha(null, $mollomresponse) == true) {
            // echo 'the answer is correct, you may proceed!';
            //					global $mainframe;
            //					$mainframe->enqueueMessage(MolajoTextHelper::_('Good answer'));
            //				} else {
            //					global $mainframe;
            //					$mainframe->enqueueMessage(MolajoTextHelper::_('Incorrect Captcha value. Please try, again.'));
            //					$session->set('mollom_challenge_response', true, 'responses');
            //					$published = 3;
            //				}
            //			} else {

            $feedback = Mollom::checkContent(null, null,
                                             $session->get('comment_body', null, 'responses'),
                                             $session->get('comment_author_name', null, 'responses'),
                                             $session->get('comment_author_email', null, 'responses'),
                                             $session->get('component_url', null, 'responses'));

            if (in_array($feedback['spam'], array('unsure', 'unknown'))) {
                //						$session->set('mollom_challenge_response', true, 'responses');

            } else if ($feedback['spam'] == 'spam') {
                global $mainframe;
                $mainframe->enqueueMessage(MolajoTextHelper::_('Comment identified as Spam by Mollom'));
                $published = 3;
            } else {
                //				echo 'must be ham '.$feedback['spam'];
            }
        }
    }
}

/**
 *
 *     Function: invokeBadWordCheck
 *         Strip out Bad Words
 *
 */
function invokeBadWordCheck($cleanString)
{
    /**
     *     Retrieve User Group Parameter for Auto Publish
     */
    $tamkaLibraryPlugin =& MolajoPlugin::getPlugin('system', 'tamka');
    $tamkaLibraryPluginParameters = new JParameter($tamkaLibraryPlugin->parameters);

    /**
     *     Filter content through array of Bad Words
     */
    $badWords = explode(",", $tamkaLibraryPluginParameters->def('badword', ''));
    return str_replace($badWords, '', $cleanString);

}
}
?>