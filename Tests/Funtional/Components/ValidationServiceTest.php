<?php
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
