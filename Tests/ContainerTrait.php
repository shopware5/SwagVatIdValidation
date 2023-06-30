<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Tests;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait ContainerTrait
{
    public function getContainer(): ContainerInterface
    {
        $container = \SwagVatIdValidationTestKernel::getKernel()->getContainer();

        if (!$container instanceof ContainerInterface) {
            throw new \UnexpectedValueException('Container not found');
        }

        return $container;
    }
}
