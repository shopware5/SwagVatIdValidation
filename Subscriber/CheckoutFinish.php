<?php
declare(strict_types=1);
/**
 * Shopware Plugins
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this plugin can be used under
 * a proprietary license as set forth in our Terms and Conditions,
 * section 2.1.2.2 (Conditions of Usage).
 *
 * The text of our proprietary license additionally can be found at and
 * in the LICENSE file you have received along with this plugin.
 *
 * This plugin is distributed in the hope that it will be useful,
 * with LIMITED WARRANTY AND LIABILITY as set forth in our
 * Terms and Conditions, sections 9 (Warranty) and 10 (Liability).
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the plugin does not imply a trademark license.
 * Therefore any rights, title and interest in our trademarks
 * remain entirely with us.
 */

namespace SwagVatIdValidation\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
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
     * @return void
     */
    public function onPreDispatchCheckout(ActionEventArgs $arguments)
    {
        $subject = $arguments->getSubject();

        $request = $subject->Request();

        $response = $subject->Response();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getActionName() !== 'finish'
            || !$subject->View()->hasTemplate()
        ) {
            return;
        }

        $orderDetails = $this->dependencyProvider->getSession()->get('sOrderVariables');

        // The user might have been logged out during the last request.
        // If so, the orderDetails object won't be available.
        if (!$orderDetails) {
            return;
        }

        $orderDetails = $orderDetails->getArrayCopy();
        $billing = $orderDetails['sUserData']['billingaddress'];

        $required = $this->validationService->isVatIdRequired($billing['company'], $billing['country']['id']);

        if ($required && !$billing['vatId']) {
            $subject->forward('confirm', 'checkout', null, ['vatIdRequiredButEmpty' => true]);
        }
    }

    /**
     * Listener to show the requirement error message
     *
     * @return void
     */
    public function onPostDispatchCheckout(ActionEventArgs $arguments)
    {
        $subject = $arguments->getSubject();

        $request = $subject->Request();

        if ($request->getActionName() !== 'confirm') {
            return;
        }

        if ($request->getParam('vatIdRequiredButEmpty')) {
            $errorMessages = $this->validationService->getRequirementErrorResult()->getErrorMessages();
            $subject->View()->assign('sBasketInfo', \current($errorMessages));
        }
    }
}
