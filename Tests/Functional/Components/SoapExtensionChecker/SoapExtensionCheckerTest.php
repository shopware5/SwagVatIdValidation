<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
