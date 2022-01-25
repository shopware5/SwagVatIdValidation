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

    /**
     * @var VatIdConfigReaderInterface
     */
    private $configReader;

    public function __construct(
        EventManager $eventManager,
        Connection $connection,
        VatIdConfigReaderInterface $configReader
    ) {
        $this->eventManager = $eventManager;
        $this->connection = $connection;
        $this->configReader = $configReader;
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
        $collection = new ArrayCollection(
            $this->removeDisabledCountries(EUStates::getEUCountryList())
        );

        $this->eventManager->collect('SwagVatId_Collect_CountryIso', $collection);

        return $collection->toArray();
    }

    private function removeDisabledCountries(array $euCountryList): array
    {
        $config = $this->configReader->getPluginConfig();

        $disabledCountryISOs = $config[VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST];

        if (!\is_array($disabledCountryISOs)) {
            $disabledCountryISOs = [$disabledCountryISOs];
        }

        foreach ($euCountryList as $index => $euCountryIso) {
            if (\in_array($euCountryIso, $disabledCountryISOs)) {
                unset($euCountryList[$index]);
            }
        }

        return $euCountryList;
    }
}
