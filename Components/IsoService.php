<?php
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

namespace SwagVatIdValidation\Components;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Enlight_Event_EventManager as EventManager;

class IsoService implements IsoServiceInterface
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(EventManager $eventManager, Connection $connection)
    {
        $this->eventManager = $eventManager;
        $this->connection = $connection;
    }

    public function getCountryIdsFromIsoList(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_core_countries')
            ->where('countryiso IN (:isoList)')
            ->setParameter('isoList', $this->getCountriesIsoList(), Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getCountriesIsoList(): array
    {
        $collection = new ArrayCollection([
            'AT',
            'BE',
            'BG',
            'CY',
            'CZ',
            'DE',
            'DK',
            'EE',
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
        ]);

        $this->eventManager->collect('SwagVatId_Collect_CountryIso', $collection);

        return $collection->toArray();
    }
}
