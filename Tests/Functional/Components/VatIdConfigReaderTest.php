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
use Shopware\Components\Plugin\Configuration\CachedReader;
use SwagVatIdValidation\Components\VatIdConfigReader;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Tests\PluginConfigCacheTrait;

class VatIdConfigReaderTest extends TestCase
{
    use PluginConfigCacheTrait;

    public function testGetPluginConfigShopware57andAbove(): void
    {
        if (!Shopware()->Container()->initialized(CachedReader::class)) {
            static::markTestSkipped('Test is only for Shopware 5.7 and above.');

            return;
        }

        $this->clearCache();

        $result = $this->getVatIdConfigReader()->getPluginConfig();

        static::assertTrue(\is_array($result));
        static::assertSame('AT', $result[VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST]);
    }

    public function testGetPluginConfigLegacy(): void
    {
        if (!Shopware()->Container()->initialized('shopware.plugin.cached_config_reader')) {
            static::markTestSkipped('legacy shopware.plugin.cached_config_reader is removed.');

            return;
        }

        $this->clearCache();

        $configReader = $this->getVatIdConfigReader();

        $configReaderReflectionProperty = (new \ReflectionClass(VatIdConfigReader::class))->getProperty('cachedConfigReader');
        $configReaderReflectionProperty->setAccessible(true);
        $configReaderReflectionProperty->setValue($configReader, Shopware()->Container()->get('shopware.plugin.cached_config_reader'));

        $result = $configReader->getPluginConfig();

        static::assertTrue(\is_array($result));
        static::assertSame('AT', $result[VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST]);
    }

    private function getVatIdConfigReader(): VatIdConfigReaderInterface
    {
        return Shopware()->Container()->get('swag_vat_id_validation.config_reader');
    }
}
