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
use Shopware\Components\Plugin\Configuration\CachedReader;
use SwagVatIdValidation\Components\VatIdConfigReader;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Tests\PluginConfigCacheTrait;

class VatIdConfigReaderTest extends TestCase
{
    use PluginConfigCacheTrait;

    public function testGetPluginConfigShopware57andAbove()
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

    public function testGetPluginConfigLegacy()
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

        $isLegacyReflectionProperty = (new \ReflectionClass(VatIdConfigReader::class))->getProperty('isLegacy');
        $isLegacyReflectionProperty->setAccessible(true);
        $isLegacyReflectionProperty->setValue($configReader, true);

        $result = $configReader->getPluginConfig();

        static::assertTrue(\is_array($result));
        static::assertSame('AT', $result[VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST]);
    }

    private function getVatIdConfigReader(): VatIdConfigReaderInterface
    {
        return Shopware()->Container()->get('swag_vat_id_validation.config_reader');
    }
}
