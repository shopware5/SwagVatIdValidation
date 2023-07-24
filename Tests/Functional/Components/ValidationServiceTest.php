<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Tests\Functional\Components;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use SwagVatIdValidation\Components\ValidationService;
use SwagVatIdValidation\Components\Validators\DummyVatIdValidator;
use SwagVatIdValidation\Components\Validators\ValidatorFactory;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Tests\ContainerTrait;
use SwagVatIdValidation\Tests\Functional\Components\Mock\MockValidator;

class ValidationServiceTest extends TestCase
{
    use ContainerTrait;

    private const DEFAULT_CONFIG_EMAIL_NOTIFICATION = 1;

    public function testIsVatIdRequiredNoVatCheckRequiredShouldBeFalse(): void
    {
        $validationService = $this->getValidationService(false);

        $countryId = 2; // Germany

        $result = $validationService->isVatIdRequired('Mayer', $countryId);

        static::assertFalse($result);
    }

    public function testIsVatIdRequiredShouldBeFalse(): void
    {
        $validationService = $this->getValidationService();

        $countryId = 2; // Germany

        $result = $validationService->isVatIdRequired(null, $countryId);

        static::assertFalse($result);
    }

    public function testIsVatIdRequiredNoEuCountryShouldBeFalse(): void
    {
        $validationService = $this->getValidationService();

        $countryId = 16; // Canada

        $result = $validationService->isVatIdRequired('Mayer', $countryId);

        static::assertFalse($result);
    }

    public function testIsVatIdRequiredShouldBeTrue(): void
    {
        $validationService = $this->getValidationService();

        $countryId = 2; // Germany

        $result = $validationService->isVatIdRequired('Mayer', $countryId);

        static::assertTrue($result);
    }

    public function testValidateVatIdApiNotAvailable(): void
    {
        $validationService = $this->getValidationService();

        $address = new Address();
        $address->setVatId('DE261679493');
        $address->setCompany('shopware AG');
        $address->setStreet('Ebbinghoff 10');
        $address->setZipcode('48624');
        $address->setCity('Schöppingen');

        $country = new Country();
        $country->setIso('DE');
        $address->setCountry($country);

        $result = $validationService->validateVatId($address, false);

        static::assertFalse($result->isValid());
        static::assertTrue($result->isApiUnavailable());
    }

    /**
     * @dataProvider provideEmailNotificationConfig
     *
     * @param string|int $expectedResult
     */
    public function testGetEmailAddress(string $configSettings, $expectedResult): void
    {
        $config = $this->getContainer()->get('config');

        if ($configSettings !== '') {
            $config->offsetSet(VatIdConfigReaderInterface::EMAIL_NOTIFICATION, $configSettings);
        }

        $validationService = $this->getValidationService();

        $reflectionClass = new \ReflectionClass($validationService);
        $privateMethod = $reflectionClass->getMethod('getEmailAddress');
        $privateMethod->setAccessible(true);

        $result = $privateMethod->invoke($validationService);

        $config->offsetSet(VatIdConfigReaderInterface::EMAIL_NOTIFICATION, self::DEFAULT_CONFIG_EMAIL_NOTIFICATION);

        static::assertSame($expectedResult, $result);
    }

    /**
     * @return \Generator<array{setConfig: string, expectedResult: string|bool}>
     */
    public function provideEmailNotificationConfig(): \Generator
    {
        yield 'default setting = 1 = send email to shopowner email address' => [
            'setConfig' => '',
            'expectedResult' => 'info@example.com',
        ];
        yield 'send no email' => [
            'setConfig' => '0',
            'expectedResult' => false,
        ];
        yield 'send email to shopowner email address' => [
            'setConfig' => '1',
            'expectedResult' => 'info@example.com',
        ];
        yield 'send email to valid custom email address' => [
            'setConfig' => 'test@shopware.com',
            'expectedResult' => 'test@shopware.com',
        ];
        yield 'send email to invalid custom email address' => [
            'setConfig' => 'test@shopware.com||',
            'expectedResult' => false,
        ];
    }

    private function getValidationService(bool $vatCheckRequired = true): ValidationService
    {
        $config = $this->getContainer()->get('config');
        if ($vatCheckRequired) {
            $config->offsetSet('vatcheckrequired', true);
        }

        $snippetManager = $this->getContainer()->get('snippets');
        $modelManager = $this->getContainer()->get('models');
        $templateMail = $this->getContainer()->get('templatemail');
        $pluginLogger = $this->getContainer()->get('pluginlogger');
        $dependencyProvider = $this->getContainer()->get('swag_vat_id_validation.dependency_provider');

        $validatorFactory = $this->createMock(ValidatorFactory::class);
        $validatorFactory->method('createDummyValidator')->willReturn(new DummyVatIdValidator($snippetManager, $config));
        $validatorFactory->method('getValidator')->willReturn(new MockValidator($snippetManager, $config));

        return new ValidationService(
            $config,
            $snippetManager,
            $modelManager,
            $templateMail,
            $pluginLogger,
            $validatorFactory,
            $dependencyProvider
        );
    }
}
