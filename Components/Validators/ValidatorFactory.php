<?php
declare(strict_types=1);
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

namespace SwagVatIdValidation\Components\Validators;

use SwagVatIdValidation\Components\APIValidationType;

class ValidatorFactory implements ValidatorFactoryInterface
{
    /**
     * @var \IteratorAggregate
     */
    private $validators;

    public function __construct(\IteratorAggregate $validators)
    {
        $this->validators = $validators;
    }

    public function getValidator(string $customerCountryCode, string $shopCountryCode, int $validationType): VatIdValidatorInterface
    {
        $validatorName = $this->createValidatorName($customerCountryCode, $shopCountryCode, $validationType);

        return $this->getValidatorByName($validatorName);
    }

    public function createDummyValidator(): VatIdValidatorInterface
    {
        return $this->getValidatorByName(DummyVatIdValidator::class);
    }

    private function getValidatorByName(string $validatorName): VatIdValidatorInterface
    {
        foreach ($this->validators as $validator) {
            if ($validator instanceof $validatorName && $validator instanceof VatIdValidatorInterface) {
                return $validator;
            }
        }

        throw new \InvalidArgumentException(sprintf('Validator with name %s not found', $validatorName));
    }

    private function createValidatorName(string $customerCountryCode, string $shopCountryCode, int $validationType): string
    {
        $isMiasValidator = $this->isMiasValidator($customerCountryCode, $shopCountryCode);

        if ($validationType === APIValidationType::EXTENDED) {
            if ($isMiasValidator) {
                return ExtendedMiasVatIdValidator::class;
            }

            return ExtendedBffVatIdValidator::class;
        }

        if ($isMiasValidator) {
            return SimpleMiasVatIdValidator::class;
        }

        return BffVatIdValidator::class;
    }

    private function isMiasValidator(string $customerCountryCode, string $shopCountryCode): bool
    {
        return $customerCountryCode === 'DE' || $shopCountryCode !== 'DE';
    }
}
