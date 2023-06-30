<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Bundle\AccountBundle\Service\Validator;

use Shopware\Bundle\AccountBundle\Service\Validator\AddressValidatorInterface;
use Shopware\Components\Api\Exception\ValidationException;
use Shopware\Models\Country\Country;
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
     *
     * @return void
     */
    public function validate(Address $address)
    {
        $this->coreAddressValidator->validate($address);

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

    private function addVatIdValidation(Address $address, ContextualValidatorInterface $validationContext): void
    {
        $country = $address->getCountry();
        if (!$country instanceof Country) {
            return;
        }

        $company = $address->getCompany();
        $countryId = $country->getId();
        if ($this->validationService->isVatIdRequired($company, $countryId)) {
            $validationContext->atPath('vatId')->validate(
                $address->getVatId(),
                [new AdvancedVatId(['address' => $address])]
            );
        }
    }
}
