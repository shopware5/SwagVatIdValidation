<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
