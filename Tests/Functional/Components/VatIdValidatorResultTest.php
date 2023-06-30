<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
