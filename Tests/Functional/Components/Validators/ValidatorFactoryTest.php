<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

    /**
     * @return array<array{0: 'DE'|'EN', 1: 'DE'|'EN', 2: APIValidationType::*, 3: class-string<VatIdValidatorInterface>}>
     */
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
