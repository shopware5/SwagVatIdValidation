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

namespace SwagVatIdValidation\Tests\Functional\Components;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use SwagVatIdValidation\Components\ValidationService;
use SwagVatIdValidation\Components\Validators\DummyVatIdValidator;
use SwagVatIdValidation\Components\Validators\ValidatorFactory;
use SwagVatIdValidation\Tests\ContainerTrait;
use SwagVatIdValidation\Tests\Functional\Components\Mock\MockValidator;

class ValidationServiceTest extends TestCase
{
    use ContainerTrait;

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
