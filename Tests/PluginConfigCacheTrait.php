<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Tests;

trait PluginConfigCacheTrait
{
    public function clearCache(): void
    {
        Shopware()->Container()->get('cache')->remove('1SwagVatIdValidation');
        Shopware()->Container()->get('cache')->remove('SwagVatIdValidation1');
        Shopware()->Container()->get('cache')->remove('SwagVatIdValidation');
    }
}
