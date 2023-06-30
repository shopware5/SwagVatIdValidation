<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
