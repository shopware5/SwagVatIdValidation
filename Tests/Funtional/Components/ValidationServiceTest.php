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

namespace SwagVatIdValidation\Tests\Functional\Components;

use PHPUnit\Framework\TestCase;
use SwagVatIdValidation\Components\ValidationService;

class ValidationServiceTest extends TestCase
{
    public function testIsVatIdRequiredNoVatCheckRequredShouldBeFalse(): void
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

    private function getValidationService(bool $vatCheckRequired = true): ValidationService
    {
        $service = Shopware()->Container()->get('swag_vat_id_validation.validation_service');

        $reflectionPropertyCountryIso = (new \ReflectionClass(ValidationService::class))->getProperty('countryIso');
        $reflectionPropertyCountryIso->setAccessible(true);

        $reflectionPropertyCountryIso->setValue($service, null);

        if ($vatCheckRequired === false) {
            return $service;
        }

        $reflectionPropertyConfig = (new \ReflectionClass(ValidationService::class))->getProperty('config');
        $reflectionPropertyConfig->setAccessible(true);

        /** @var \Shopware_Components_Config $config */
        $config = $reflectionPropertyConfig->getValue($service);
        $config->offsetSet('vatcheckrequired', true);

        return $service;
    }
}
