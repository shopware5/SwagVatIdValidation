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

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace as Session;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Customer\Customer;
use Shopware\Plugins\SwagVatIdValidation\Components\ValidationService;

/**
 * Class Login
 *
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class Login implements SubscriberInterface
{
    /** @var  string */
    private static $action;

    /** @var Container $container */
    private $container;

    /**
     * @param string $action
     * @param Container $container
     */
    public function __construct($action, Container $container)
    {
        self::$action = $action;
        $this->container = $container;
    }

    /**
     * Returns the events we need to subscribe to
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        //After successfully registration, this would be a second validation. The first on save, the second on login.
        if (self::$action === 'saveRegister') {
            return [];
        }

        return [
            'Shopware_Modules_Admin_Login_Successful' => 'onLoginSuccessful'
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onLoginSuccessful(\Enlight_Event_EventArgs $arguments)
    {
        $user = $arguments->get('user');

        /** @var Customer $customer */
        $customer = $this->container->get('models')->getRepository(Customer::class)->find($user['id']);

        $billingAddress = $customer->getDefaultBillingAddress();

        if (!$billingAddress) {
            return;
        }

        /** @var ValidationService $validationService */
        $validationService = $this->container->get('vat_id.validation_service');

        /** If the VAT ID is required, but empty, set the requirement error */
        $required = $validationService->isVatIdRequired(
            $billingAddress->getCompany(),
            $billingAddress->getCountry()->getId()
        );

        if ($required && (!trim($billingAddress->getVatId()))) {
            $result = $validationService->getRequirementErrorResult();
            $this->container->get('session')->offsetSet('vatIdValidationStatus', $result->serialize());

            return;
        }

        $result = $validationService->validateVatId($billingAddress);

        $this->container->get('session')->offsetSet('vatIdValidationStatus', $result->serialize());
    }
}
