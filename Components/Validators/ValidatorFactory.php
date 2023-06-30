<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
