<?php
declare(strict_types=1);
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
use Shopware\Components\DependencyInjection\Container;
use Shopware_Components_Config as ShopwareConfig;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Components\VatIdValidatorResult;
use SwagVatIdValidation\Tests\ContainerTrait;

class VatIdValidatorResultTest extends TestCase
{
    use ContainerTrait;

    public function testSetServiceUnavailable(): void
    {
        $container = $this->getContainer();
        static::assertInstanceOf(Container::class, $container);

        $config = $container->get('config');
        static::assertInstanceOf(ShopwareConfig::class, $config);

        $config->offsetSet(VatIdConfigReaderInterface::ALLOW_REGISTER_ON_API_ERROR, false);

        $validatorResult = $this->createVatIdValidatorResult();
        $validatorResult->setServiceUnavailable();

        $container->reset('config');

        static::assertFalse($validatorResult->isValid());
        static::assertNotEmpty($validatorResult->getErrorMessages());
        static::assertSame(
            'Die Verarbeitung Ihrer USt-IdNr. ist zurzeit nicht möglich. Bitte versuchen Sie es Später noch einmal.',
            $validatorResult->getErrorMessages()['serviceUnavailable']
        );
    }

    public function testSetServiceUnavailableExpectNoErrorMessage(): void
    {
        $container = $this->getContainer();
        static::assertInstanceOf(Container::class, $container);

        $config = $container->get('config');
        static::assertInstanceOf(ShopwareConfig::class, $config);

        $config->offsetSet(VatIdConfigReaderInterface::ALLOW_REGISTER_ON_API_ERROR, true);

        $validatorResult = $this->createVatIdValidatorResult();
        $validatorResult->setServiceUnavailable();

        $container->reset('config');

        static::assertFalse($validatorResult->isValid());
        static::assertEmpty($validatorResult->getErrorMessages());
    }

    private function createVatIdValidatorResult(): VatIdValidatorResult
    {
        return new VatIdValidatorResult(
            $this->getContainer()->get('snippets'),
            'miasValidator',
            $this->getContainer()->get('config')
        );
    }
}
