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
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagVatIdValidation\Components\IsoServiceInterface;
use SwagVatIdValidation\Tests\ContainerTrait;
use SwagVatIdValidation\Tests\PluginConfigCacheTrait;

class IsoServiceTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use PluginConfigCacheTrait;
    use ContainerTrait;

    public function testGetCountryIdsFromIsoList(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/config.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->exec($sql);

        $sql = \file_get_contents(__DIR__ . '/_fixtures/update_config.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->exec($sql);

        $this->clearCache();

        $result = $this->getIsoService()->getCountryIdsFromIsoList();

        $expectedResult = [
            0 => '2',
            1 => '5',
            2 => '7',
            3 => '8',
            4 => '9',
            5 => '10',
            6 => '11',
            7 => '12',
            8 => '14',
            9 => '18',
            10 => '21',
            11 => '23',
            12 => '24',
            13 => '25',
            14 => '27',
            15 => '30',
            16 => '31',
            17 => '33',
            18 => '34',
            19 => '35',
            20 => '38',
            21 => '39',
            22 => '40',
            23 => '41',
            24 => '42',
            25 => '43',
            26 => '44',
            27 => '45',
            28 => '209',
        ];

        static::assertSame($expectedResult, $result);
    }

    public function testGetCountriesIsoList(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/config.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->exec($sql);

        $sql = \file_get_contents(__DIR__ . '/_fixtures/update_config.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->exec($sql);

        $this->clearCache();

        $result = $this->getIsoService()->getCountriesIsoList();

        $expectedResult = [
            'AT',
            'BE',
            'BG',
            'CY',
            'CZ',
            'DE',
            'DK',
            'EE',
            'EL',
            'GR',
            'ES',
            'FI',
            'FR',
            'GB',
            'HR',
            'HU',
            'IE',
            'IT',
            'LT',
            'LU',
            'LV',
            'MT',
            'NL',
            'PL',
            'PT',
            'RO',
            'SE',
            'SI',
            'SK',
            'SM',
            'XI',
        ];

        static::assertSame($expectedResult, $result);
    }

    public function testGetCountriesIsoListShouldRemoveAT(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/config.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->exec($sql);

        $this->clearCache();

        $result = $this->getIsoService()->getCountriesIsoList();

        static::assertFalse(\in_array('AT', $result));
    }

    private function getIsoService(): IsoServiceInterface
    {
        $isoService = $this->getContainer()->get('swag_vat_id_validation.iso_service');
        static::assertInstanceOf(IsoServiceInterface::class, $isoService);

        return $isoService;
    }
}
