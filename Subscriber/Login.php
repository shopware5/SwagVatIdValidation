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
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use SwagVatIdValidation\Components\DependencyProviderInterface;
use SwagVatIdValidation\Components\ValidationServiceInterface;

class Login implements SubscriberInterface
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
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(
        DependencyProviderInterface $dependencyProvider,
        ValidationServiceInterface $validationService,
        ModelManager $modelManager
    ) {
        $this->dependencyProvider = $dependencyProvider;
        $this->validationService = $validationService;
        $this->modelManager = $modelManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_Login_Successful' => 'onLoginSuccessful',
        ];
    }

    /**
     * @return void
     */
    public function onLoginSuccessful(\Enlight_Event_EventArgs $arguments)
    {
        // After successfully registration, this would be a second validation. The first on save, the second on login.
        $request = $this->dependencyProvider->getFront()->Request();
        if (!$request instanceof \Enlight_Controller_Request_Request || $request->getActionName() === 'saveRegister') {
            return;
        }

        $user = $arguments->get('user');
        $customer = $this->modelManager->getRepository(Customer::class)->find($user['id']);
        if (!$customer instanceof Customer) {
            return;
        }

        $billingAddress = $customer->getDefaultBillingAddress();
        if (!$billingAddress instanceof Address) {
            return;
        }

        if (!$billingAddress->getCountry() instanceof Country) {
            return;
        }

        /** If the VAT ID is required, but empty, set the requirement error */
        $required = $this->validationService->isVatIdRequired(
            $billingAddress->getCompany(),
            $billingAddress->getCountry()->getId()
        );

        $session = $this->dependencyProvider->getSession();

        if ($required && (!\trim((string) $billingAddress->getVatId()))) {
            $result = $this->validationService->getRequirementErrorResult();
            $session->offsetSet('vatIdValidationStatus', $result->serialize());

            return;
        }

        if (!$required) {
            // The check is not required, no validation required
            return;
        }

        $result = $this->validationService->validateVatId($billingAddress);

        $session->offsetSet('vatIdValidationStatus', $result->serialize());
    }
}
