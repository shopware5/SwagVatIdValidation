<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Initialize the shopware kernel
 */
require __DIR__ . '/../../../../autoload.php';

use Shopware\Kernel;
use Shopware\Models\Shop\Shop;

class SwagVatIdValidationTestKernel extends Kernel
{
    /**
     * @var SwagVatIdValidationTestKernel
     */
    private static $kernel;

    public static function start(): void
    {
        self::$kernel = new self((string) \getenv('SHOPWARE_ENV') ?: 'testing', true);
        self::$kernel->boot();

        $container = self::$kernel->getContainer();
        $container->get('plugins')->Core()->ErrorHandler()->registerErrorHandler(\E_ALL | \E_STRICT);

        $shop = $container->get('models')->getRepository(Shop::class)->getActiveDefault();
        $shopRegistrationService = $container->get('shopware.components.shop_registration_service');
        $shopRegistrationService->registerResources($shop);

        $_SERVER['HTTP_HOST'] = $shop->getHost();

        if (!self::assertPlugin()) {
            throw new \Exception('Plugin SwagVatIdValidation is not installed or activated.');
        }
    }

    public static function getKernel(): SwagVatIdValidationTestKernel
    {
        return self::$kernel;
    }

    private static function assertPlugin(): bool
    {
        $sql = 'SELECT 1 FROM s_core_plugins WHERE name = ? AND active = 1';

        return (bool) Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, ['SwagVatIdValidation']);
    }
}

SwagVatIdValidationTestKernel::start();
