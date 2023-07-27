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

    /**
     * @dataProvider provideVatIdValidatorResults
     *
     * @param array<int, mixed>  $errorCodes
     * @param array<int, string> $expectedErrorMessages
     */
    public function testAddErrorCodesWillNotThrowAnError(string $serializedVatIdValidatorResult, array $errorCodes, array $expectedErrorMessages): void
    {
        $validatorResult = $this->createVatIdValidatorResult();
        $validatorResult->unserialize($serializedVatIdValidatorResult);

        static::assertFalse($validatorResult->isValid());

        $errorMessages = $validatorResult->getErrorMessages();

        $counter = 0;
        foreach ($errorMessages as $errorCode => $errorMessage) {
            static::assertSame($errorCodes[$counter], $errorCode);
            static::assertSame($expectedErrorMessages[$counter], $errorMessage);

            ++$counter;
        }
    }

    /**
     * @return \Generator<array{serializedVatIdValidatorResult: string, errorCodes: array<int, mixed>, expectedErrorMessages: array<int, string>}>
     */
    public function provideVatIdValidatorResults(): \Generator
    {
        yield 'existing VatId is invalid but required' => [
            'serializedVatIdValidatorResult' => 'a:2:{s:9:"namespace";s:13:"miasValidator";s:4:"keys";a:2:{i:0;i:1;i:1;s:8:"required";}}',
            'errorCodes' => [
                1,
                'required',
            ],
            'expectedErrorMessages' => [
                'Die angefragte USt-IdNr. ist ungültig.',
                'Sie haben gegenwärtig keine USt-IdNr. angegeben. Bitte tragen Sie sie in Ihrem Account nach.',
            ],
        ];
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
