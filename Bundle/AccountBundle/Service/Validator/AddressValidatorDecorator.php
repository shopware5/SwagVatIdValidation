<?php
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

namespace SwagVatIdValidation\Bundle\AccountBundle\Service\Validator;

use Shopware\Bundle\AccountBundle\Service\Validator\AddressValidatorInterface;
use Shopware\Components\Api\Exception\ValidationException;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware_Components_Config as ShopwareConfig;
use SwagVatIdValidation\Bundle\AccountBundle\Constraints\AdvancedVatId;
use SwagVatIdValidation\Components\ValidationServiceInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AddressValidatorDecorator implements AddressValidatorInterface
{
    /**
     * @var ShopwareConfig
     */
    private $config;

    /**
     * @var AddressValidatorInterface
     */
    private $coreAddressValidator;

    /**
     * @var ValidationServiceInterface
     */
    private $validationService;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        AddressValidatorInterface $coreAddressValidator,
        ShopwareConfig $config,
        ValidationServiceInterface $validationService,
        ValidatorInterface $validator
    ) {
        $this->coreAddressValidator = $coreAddressValidator;
        $this->config = $config;
        $this->validationService = $validationService;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isValid(Address $address)
    {
        return $this->coreAddressValidator->isValid($address);
    }

    /**
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
