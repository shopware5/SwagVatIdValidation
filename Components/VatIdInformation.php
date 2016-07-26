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

/**
 * Class VatIdInformation
 * @package Shopware\Plugins\SwagVatIdValidation\Components
 */
class VatIdInformation
{
    /** @var string */
    protected $vatId;

    /** @var string */
    protected $countryCode;

    /** @var string */
    protected $vatNumber;

    /**
     * Constructor sets all properties
     * @param string $vatId
     */
    public function __construct($vatId)
    {
        $this->vatId = str_replace([' ', '.', '-', ',', ', '], '', trim($vatId));
        $this->countryCode = substr($this->vatId, 0, 2);
        $this->vatNumber = substr($this->vatId, 2);
    }

    /**
     * Returns the VAT ID
     * @return string
     */
    public function getVatId()
    {
        return $this->vatId;
    }

    /**
     * Returns the country code (p.e. DE123456789 => DE)
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Returns the VAT number (p.e. DE123456789 => 123456789)
     * @return string
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }
}
