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
