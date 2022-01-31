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
