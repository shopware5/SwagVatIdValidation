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

namespace SwagVatIdValidation\Components;

use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Model\ModelManager;
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
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var CachedReader|CachedConfigReader|null
     */
    private $cachedConfigReader;

    public function __construct(
        string $pluginName,
        ContextServiceInterface $contextService,
        ModelManager $modelManager,
        CachedReader $cachedConfigReader = null,
        CachedConfigReader $legacyCachedConfigReader = null
    ) {
        $this->pluginName = $pluginName;
        $this->contextService = $contextService;
        $this->modelManager = $modelManager;

        if ($cachedConfigReader === null) {
            $this->cachedConfigReader = $legacyCachedConfigReader;
        } else {
            $this->cachedConfigReader = $cachedConfigReader;
        }
    }

    public function getPluginConfig(): array
    {
        if ($this->cachedConfigReader instanceof CachedConfigReader) {
            $shopId = $this->contextService->getShopContext()->getShop()->getId();
            $shop = $this->modelManager->getRepository(Shop::class)->getActiveById($shopId);

            return $this->cachedConfigReader->getByPluginName(
                $this->pluginName,
                $shop
            );
        }

        if ($this->cachedConfigReader instanceof CachedReader) {
            return $this->cachedConfigReader->getByPluginName(
                $this->pluginName,
                $this->contextService->getShopContext()->getShop()->getId()
            );
        }

        throw new \RuntimeException('No valid plugin config reader in DI container');
    }
}
