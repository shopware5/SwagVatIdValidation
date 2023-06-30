<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
