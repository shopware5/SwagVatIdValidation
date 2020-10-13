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

namespace SwagVatIdValidation\Subscriber;

use ArrayObject;
use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Controller_Request_RequestHttp as Request;
use Enlight_Controller_Response_ResponseHttp as Response;
use Shopware_Controllers_Frontend_Checkout as CheckoutController;
use SwagVatIdValidation\Components\DependencyProviderInterface;
use SwagVatIdValidation\Components\ValidationServiceInterface;

class CheckoutFinish implements SubscriberInterface
{
    /**
     * @var DependencyProviderInterface
     */
    private $dependencyProvider;

    /**
     * @var ValidationServiceInterface
     */
    private $validationService;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        DependencyProviderInterface $dependencyProvider,
        ValidationServiceInterface $validationService
    ) {
        $this->dependencyProvider = $dependencyProvider;
        $this->validationService = $validationService;
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
     */
    public function onPreDispatchCheckout(ActionEventArgs $arguments)
    {
        /** @var CheckoutController $subject */
        $subject = $arguments->getSubject();

        /** @var Request $request */
        $request = $subject->Request();

        /** @var Response $response */
        $response = $subject->Response();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getActionName() !== 'finish'
            || !$subject->View()->hasTemplate()
        ) {
            return;
        }

        /** @var ArrayObject $orderDetails */
        $orderDetails = $this->dependencyProvider->getSession()->get('sOrderVariables');

        //The user might have been logged out during the last request.
        //If so, the orderDetails object won't be available.
        if (!$orderDetails) {
            return;
        }

        $orderDetails = $orderDetails->getArrayCopy();
        $billing = $orderDetails['sUserData']['billingaddress'];

        $required = $this->validationService->isVatIdRequired($billing['company'], $billing['country']['id']);

        if ($required && !$billing['vatId']) {
            $subject->forward('confirm', 'checkout', null, ['vatIdRequiredButEmpty' => true]);

            return;
        }
    }

    /**
     * Listener to show the requirement error message
     */
    public function onPostDispatchCheckout(ActionEventArgs $arguments)
    {
        /** @var CheckoutController $subject */
        $subject = $arguments->getSubject();

        /** @var Request $request */
        $request = $subject->Request();

        if ($request->getActionName() !== 'confirm') {
            return;
        }

        if ($request->getParam('vatIdRequiredButEmpty')) {
            $errorMessages = $this->validationService->getRequirementErrorResult()->getErrorMessages();
            $subject->View()->assign('sBasketInfo', current($errorMessages));
        }
    }
}
