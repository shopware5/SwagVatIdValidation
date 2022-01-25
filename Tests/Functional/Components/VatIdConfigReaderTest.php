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
