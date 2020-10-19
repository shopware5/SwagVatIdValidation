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

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Model\ModelManager;
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

    public function onLoginSuccessful(\Enlight_Event_EventArgs $arguments)
    {
        //After successfully registration, this would be a second validation. The first on save, the second on login.
        if ($this->dependencyProvider->getFront()->Request()->getActionName() === 'saveRegister') {
            return;
        }

        $user = $arguments->get('user');
        /** @var Customer $customer */
        $customer = $this->modelManager->getRepository(Customer::class)->find($user['id']);
        if (!$customer) {
            return;
        }

        $billingAddress = $customer->getDefaultBillingAddress();
        if (!$billingAddress) {
            return;
        }

        /** If the VAT ID is required, but empty, set the requirement error */
        $required = $this->validationService->isVatIdRequired(
            $billingAddress->getCompany(),
            $billingAddress->getCountry()->getId()
        );

        $session = $this->dependencyProvider->getSession();

        if ($required && (!trim($billingAddress->getVatId()))) {
            $result = $this->validationService->getRequirementErrorResult();
            $session->offsetSet('vatIdValidationStatus', $result->serialize());

            return;
        }

        if (!$required) {
            //The check is not required, no validation required
            return;
        }

        $result = $this->validationService->validateVatId($billingAddress);

        $session->offsetSet('vatIdValidationStatus', $result->serialize());
    }
}
