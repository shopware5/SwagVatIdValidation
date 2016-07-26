<?php

/**
 * Shopware 5
 * Copyright (c) shopware AG
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

use ArrayObject;
use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Controller_Request_RequestHttp as Request;
use Enlight_Controller_Response_ResponseHttp as Response;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Plugins\SwagVatIdValidation\Components\ValidationService;
use Shopware_Controllers_Frontend_Checkout as CheckoutController;

/**
 * Class CheckoutFinish
 *
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class CheckoutFinish implements SubscriberInterface
{
    /**
     * @var Container $container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the events we need to subscribe to
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'onPreDispatchCheckout',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
        ];
    }

    /**
     * Listener to check on checkout finish, whether the VAT ID is stated when required
     *
     * @param ActionEventArgs $arguments
     */
    public function onPreDispatchCheckout(ActionEventArgs $arguments)
    {
        /**@var CheckoutController $subject */
        $subject = $arguments->getSubject();

        /** @var Request $request */
        $request = $subject->Request();

        /** @var Response $response */
        $response = $subject->Response();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getActionName() != 'finish'
            || !$subject->View()->hasTemplate()
        ) {
            return;
        }

        /** @var ArrayObject $orderDetails */
        $orderDetails = $this->container->get('session')->get('sOrderVariables');
        $orderDetails = $orderDetails->getArrayCopy();
        $billing = $orderDetails['sUserData']['billingaddress'];

        /** @var ValidationService $validationService */
        $validationService = $this->container->get('vat_id.validation_service');

        $required = $validationService->isVatIdRequired($billing['company'], $billing['country']['id']);

        if ($required && !$billing['vatId']) {
            $subject->forward('confirm', 'checkout', null, ['vatIdRequiredButEmpty' => true]);
            return;
        }
    }

    /**
     * Listener to show the requirement error message
     *
     * @param ActionEventArgs $arguments
     */
    public function onPostDispatchCheckout(ActionEventArgs $arguments)
    {
        /**@var CheckoutController $subject */
        $subject = $arguments->getSubject();

        /** @var Request $request */
        $request = $subject->Request();

        if ($request->getActionName() != 'confirm') {
            return;
        }

        /** @var ValidationService $validationService */
        $validationService = $this->container->get('vat_id.validation_service');

        if ($request->getParam('vatIdRequiredButEmpty')) {
            $result = $validationService->getRequirementErrorResult();
            $subject->View()->assign('sBasketInfo', current($result->getErrorMessages()));
        }
    }
}
