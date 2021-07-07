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

namespace SwagVatIdValidation\Tests\Functional\Components\SoapExtensionChecker;

use PHPUnit\Framework\TestCase;
use SwagVatIdValidation\Components\SoapExtensionChecker\SoapExtensionChecker;

class SoapExtensionCheckerTest extends TestCase
{
    public function testCheckShouldReturnTrue(): void
    {
        $soapExtensionChecker = new SoapExtensionChecker();
        $reflectionProperty = (new \ReflectionClass(SoapExtensionChecker::class))->getProperty('isSoapExtensionInstalled');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($soapExtensionChecker, true);

        static::assertTrue($soapExtensionChecker->check());
    }

    public function testCheckShouldThrowException(): void
    {
        $soapExtensionChecker = new SoapExtensionChecker();
        $reflectionProperty = (new \ReflectionClass(SoapExtensionChecker::class))->getProperty('isSoapExtensionInstalled');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($soapExtensionChecker, false);

        static::expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PHP soap extension is not installed');

        $soapExtensionChecker->check();
    }
}
