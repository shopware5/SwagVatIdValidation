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
