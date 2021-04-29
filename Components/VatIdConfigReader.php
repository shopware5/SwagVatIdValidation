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

namespace SwagVatIdValidation\Components;

use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Components\Plugin\Configuration\CachedReader;
use Shopware\Models\Shop\Shop;

class VatIdConfigReader implements VatIdConfigReaderInterface
{
    /**
     * @var string
     */
    private $pluginName;

    /**
     * @var ContextServiceInterface
     */
    private $contextService;

    /**
     * @var CachedReader|null
     */
    private $cachedConfigReader;

    /**
     * @var bool
     */
    private $isLegacy = false;

    public function __construct(
        string $pluginName,
        ContextServiceInterface $contextService,
        CachedReader $cachedConfigReader = null,
        CachedConfigReader $legacyCachedConfigReader = null
    ) {
        $this->pluginName = $pluginName;
        $this->contextService = $contextService;

        if ($cachedConfigReader === null) {
            $this->cachedConfigReader = $legacyCachedConfigReader;
            $this->isLegacy = true;
        } else {
            $this->cachedConfigReader = $cachedConfigReader;
        }
    }

    public function getPluginConfig(): array
    {
        if ($this->isLegacy) {
            $shop = new Shop(['id' => $this->contextService->getShopContext()->getShop()->getId()]);

            return $this->cachedConfigReader->getByPluginName(
                $this->pluginName,
                $shop
            );
        }

        return $this->cachedConfigReader->getByPluginName(
            $this->pluginName,
            $this->contextService->getShopContext()->getShop()->getId()
        );
    }
}
