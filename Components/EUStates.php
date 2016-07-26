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

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class EUStates
{
    /** @var string[] */
    private static $EUCountries = [
        'AT', //Republic of Austria
        'BE', //Kingdom of Belgium
        'BG', //Republic of Bulgaria
        'CY', //Republic of Cyprus
        'CZ', //Czech Republic
        'DE', //Federal Republic of Germany
        'DK', //Kingdom of Denmark
        'EE', //Republic of Estonia
        'EL', //Hellenic Republic (Greece)
        'ES', //Kingdom of Spain
        'FI', //Republic of Finland
        'FR', //French Republic
        'GB', //United Kingdom of Great Britain and Northern Ireland
        'HR', //Republic of Croatia
        'HU', //Hungary
        'IE', //Ireland
        'IT', //Italian Republic
        'LT', //Republic of Lithuania
        'LU', //Grand Duchy of Luxembourg
        'LV', //Republic of Latvia
        'MT', //Republic of Malta
        'NL', //Kingdom of the Netherlands
        'PL', //Republic of Poland
        'PT', //Portuguese Republic
        'RO', //Romania
        'SE', //Kingdom of Sweden
        'SI', //Republic of Slovenia
        'SK', //Slovak Republic
    ];

    /**
     * A helper function that returns a boolean indicating if a country is in the EU or not
     * @param string $countryIso
     * @return bool
     */
    public static function isEUCountry($countryIso)
    {
        return in_array($countryIso, self::$EUCountries);
    }

    /**
     * A helper function that returns a boolean if there are valid EU country isos in the array
     * @param string[] $countryIsos
     * @return bool
     */
    public static function hasValidEUCountry(array $countryIsos)
    {
        return (bool) array_intersect($countryIsos, self::$EUCountries);
    }
}
