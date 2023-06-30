<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Tests;

use SwagVatIdValidation\Components\Validators\ValidatorFactory;
use SwagVatIdValidation\Components\Validators\ValidatorFactoryInterface;
use SwagVatIdValidation\Components\Validators\VatIdValidatorInterface;

trait ValidatorTrait
{
    use ContainerTrait;

    public function getValidator(string $customerCountryCode, string $shopCountryCode, int $validationType): VatIdValidatorInterface
    {
        $factory = $this->getContainer()->get(ValidatorFactory::class);
        static::assertInstanceOf(ValidatorFactoryInterface::class, $factory);

        return $factory->getValidator($customerCountryCode, $shopCountryCode, $validationType);
    }

    public function getDummyValidator(): VatIdValidatorInterface
    {
        $factory = $this->getContainer()->get(ValidatorFactory::class);
        static::assertInstanceOf(ValidatorFactoryInterface::class, $factory);

        return $factory->createDummyValidator();
    }
}
