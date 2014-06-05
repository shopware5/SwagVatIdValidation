<?php
/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Plugins\SwagVatIdValidation\Subscriber;

/**
 * This example is going to show how to test your methods without global shopware state
 *
 * Class Account
 * @package Shopware\Plugins\SwagScdExample\Subscriber
 */
class Account extends TemplateExtension
{
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Account' => 'onPostDispatchFrontendAccount'
        );
    }

    /**
     * Listener to FrontendAccount (index and billing), shows the vatId and an info, if the validator was not available
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendAccount(\Enlight_Event_EventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), array('index', 'billing'));
    }
}