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
use Shopware_Components_Config as ShopwareConfig;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Components\VatIdValidatorResult;
use SwagVatIdValidation\Tests\ContainerTrait;

class VatIdValidatorResultTest extends TestCase
{
    use ContainerTrait;

    private const DEFAULT_CONFIG_ALLOW_REGISTER_ON_API_ERROR = false;

    /**
     * @var ShopwareConfig
     */
    private $config;

    /**
     * @before
     */
    public function setUp(): void
    {
        $this->config = $this->getContainer()->get('config');
    }

    public function testSetServiceUnavailable(): void
    {
        $this->config->offsetSet(VatIdConfigReaderInterface::ALLOW_REGISTER_ON_API_ERROR, false);

        $validatorResult = $this->createVatIdValidatorResult();
        $validatorResult->setServiceUnavailable();

        $this->config->offsetSet(VatIdConfigReaderInterface::ALLOW_REGISTER_ON_API_ERROR, self::DEFAULT_CONFIG_ALLOW_REGISTER_ON_API_ERROR);

        static::assertFalse($validatorResult->isValid());
        static::assertNotEmpty($validatorResult->getErrorMessages());
        static::assertSame(
            'Die Verarbeitung Ihrer USt-IdNr. ist zurzeit nicht möglich. Bitte versuchen Sie es Später noch einmal.',
            $validatorResult->getErrorMessages()['serviceUnavailable']
        );
    }

    public function testSetServiceUnavailableExpectNoErrorMessage(): void
    {
        $this->config->offsetSet(VatIdConfigReaderInterface::ALLOW_REGISTER_ON_API_ERROR, true);

        $validatorResult = $this->createVatIdValidatorResult();
        $validatorResult->setServiceUnavailable();

        $this->config->offsetSet(VatIdConfigReaderInterface::ALLOW_REGISTER_ON_API_ERROR, self::DEFAULT_CONFIG_ALLOW_REGISTER_ON_API_ERROR);

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
