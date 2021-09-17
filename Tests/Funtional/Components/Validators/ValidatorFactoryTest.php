<?php
declare(strict_types=1);
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

namespace SwagVatIdValidation\Tests\Functional\Components\Validators;

use PHPUnit\Framework\TestCase;
use SwagVatIdValidation\Components\APIValidationType;
use SwagVatIdValidation\Components\Validators\DummyVatIdValidator;
use SwagVatIdValidation\Components\Validators\ExtendedBffVatIdValidator;
use SwagVatIdValidation\Components\Validators\ExtendedMiasVatIdValidator;
use SwagVatIdValidation\Components\Validators\SimpleBffVatIdValidator;
use SwagVatIdValidation\Components\Validators\SimpleMiasVatIdValidator;
use SwagVatIdValidation\Components\Validators\ValidatorFactory;
use SwagVatIdValidation\Components\Validators\ValidatorFactoryInterface;
use SwagVatIdValidation\Components\Validators\VatIdValidatorInterface;
use SwagVatIdValidation\Tests\ContainerTrait;

class ValidatorFactoryTest extends TestCase
{
    use ContainerTrait;

    /**
     * @dataProvider createValidatorTestDataProvider
     */
    public function testCreateValidator(string $customerCountryCode, string $shopCountryCode, int $validationType, string $expectedValidator): void
    {
        $validatorFactory = $this->getContainer()->get(ValidatorFactory::class);
        static::assertInstanceOf(ValidatorFactoryInterface::class, $validatorFactory);

        $validator = $validatorFactory->getValidator($customerCountryCode, $shopCountryCode, $validationType);

        static::assertSame($expectedValidator, \get_class($validator));
    }

    public function createValidatorTestDataProvider(): array
    {
        return [
            ['DE', 'DE', APIValidationType::NONE, SimpleMiasVatIdValidator::class],
            ['DE', 'EN', APIValidationType::NONE, SimpleMiasVatIdValidator::class],
            ['EN', 'EN', APIValidationType::NONE, SimpleMiasVatIdValidator::class],
            ['EN', 'DE', APIValidationType::NONE, SimpleBffVatIdValidator::class],
            ['EN', 'EN', APIValidationType::SIMPLE, SimpleMiasVatIdValidator::class],
            ['DE', 'DE', APIValidationType::SIMPLE, SimpleMiasVatIdValidator::class],
            ['DE', 'EN', APIValidationType::SIMPLE, SimpleMiasVatIdValidator::class],
            ['EN', 'DE', APIValidationType::SIMPLE, SimpleBffVatIdValidator::class],
            ['EN', 'DE', APIValidationType::EXTENDED, ExtendedBffVatIdValidator::class],
            ['DE', 'EN', APIValidationType::EXTENDED, ExtendedMiasVatIdValidator::class],
            ['EN', 'DE', APIValidationType::EXTENDED, ExtendedBffVatIdValidator::class],
        ];
    }

    public function testCreateDummyValidator(): void
    {
        $validatorFactory = $this->getContainer()->get(ValidatorFactory::class);
        static::assertInstanceOf(ValidatorFactoryInterface::class, $validatorFactory);

        $dummyValidator = $validatorFactory->createDummyValidator();

        static::assertInstanceOf(DummyVatIdValidator::class, $dummyValidator);
        static::assertInstanceOf(VatIdValidatorInterface::class, $dummyValidator);
    }
}
