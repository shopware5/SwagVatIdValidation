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

namespace Shopware\Plugins\SwagVatIdValidation\Bundle\AccountBundle\Service\Validator;

use Shopware\Bundle\AccountBundle\Service\Validator\AddressValidatorInterface;
use Shopware\Components\Api\Exception\ValidationException;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Plugins\SwagVatIdValidation\Bundle\AccountBundle\Constraints\AdvancedVatId;
use Shopware\Plugins\SwagVatIdValidation\Components\ValidationService;
use Shopware_Components_Config as ShopwareConfig;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @package Shopware\Plugins\SwagVatIdValidation\Components
 * @copyright Copyright (c) shopware AG (http://www.shopware.com)
 */
class AddressValidatorDecorator implements AddressValidatorInterface
{
    /** @var ShopwareConfig $config */
    private $config;

    /** @var AddressValidatorInterface $coreAddressValidator */
    private $coreAddressValidator;

    /** @var ValidationService $validationService */
    private $validationService;

    /** @var ValidatorInterface $validator */
    private $validator;

    /**
     * @param AddressValidatorInterface $coreAddressValidator
     * @param ShopwareConfig $config
     * @param ValidationService $validationService
     * @param ValidatorInterface $validator
     */
    public function __construct(
        AddressValidatorInterface $coreAddressValidator,
        ShopwareConfig $config,
        ValidationService $validationService,
        ValidatorInterface $validator
    ) {
        $this->coreAddressValidator = $coreAddressValidator;
        $this->config = $config;
        $this->validationService = $validationService;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function validate(Address $address)
    {
        $this->coreAddressValidator->validate($address);

        /** @var ContextualValidatorInterface $validationContext */
        $validationContext = $this->validator->startContext();

        $additional = $address->getAdditional();
        $customerType = !empty($additional['customer_type']) ? $additional['customer_type'] : null;

        if ($customerType === Customer::CUSTOMER_TYPE_BUSINESS && $this->config->get('vatcheckrequired')) {
            $this->addVatIdValidation($address, $validationContext);
        }

        if ($validationContext->getViolations()->count()) {
            throw new ValidationException($validationContext->getViolations());
        }
    }

    /**
     * @inheritdoc
     */
    public function isValid(Address $address)
    {
        return $this->coreAddressValidator->isValid($address);
    }

    /**
     * @param Address $address
     * @param ContextualValidatorInterface $validationContext
     */
    private function addVatIdValidation(Address $address, $validationContext)
    {
        $company = $address->getCompany();
        $countryId = $address->getCountry()->getId();
        if ($this->validationService->isVatIdRequired($company, $countryId)) {
            $validationContext->atPath('vatId')->validate(
                $address->getVatId(),
                [new AdvancedVatId(['address' => $address])]
            );
        }
    }
}
