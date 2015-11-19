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
 * Class CheckoutFinish
 *
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class CheckoutFinish extends ValidationPoint
{
    /**
     * Returns the events we need to subscribe to
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'onPreDispatchFrontend',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchFrontend',
        );
    }

    /**
     * Listener to check on checkout finish, whether the VAT ID is stated when required
     *
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onPreDispatchFrontend(\Enlight_Event_EventArgs $arguments)
    {
        /**@var \Enlight_Controller_Action $subject */
        $subject = $arguments->getSubject();

        /** @var \Enlight_Controller_Request_RequestHttp $request */
        $request = $subject->Request();

        /** @var \Enlight_Controller_Response_ResponseHttp $response */
        $response = $subject->Response();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getActionName() != 'finish'
            || !$subject->View()->hasTemplate()
        ) {
            return;
        }

        $orderDetails = Shopware()->Session()->get('sOrderVariables')->getArrayCopy();
        $billing = $orderDetails['sUserData']['billingaddress'];

        $required = $this->isVatIdRequired('business', $billing['company'], $billing['countryID']);

        if (($required) && (!$billing['ustid'])) {
            return $subject->forward('confirm', 'checkout', null, array('vatIdRequiredButEmpty' => true));
        }
    }

    /**
     * Listener to show the requirement error message
     *
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontend(\Enlight_Event_EventArgs $arguments)
    {
        /**@var \Enlight_Controller_Action $subject */
        $subject = $arguments->getSubject();

        /** @var \Enlight_Controller_Request_RequestHttp $request */
        $request = $subject->Request();

        if ($request->getActionName() != 'confirm') {
            return;
        }

        if ($request->getParam('vatIdRequiredButEmpty')) {
            $result = $this->getRequirementErrorResult();
            $subject->View()->sBasketInfo = current($result->getErrorMessages());
        }
    }
}
