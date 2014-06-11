<?php
/**
 * Shopware 4
 * Copyright © shopware AG
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
 * Class VatIdCustomerInformation
 * @package Shopware\Plugins\SwagVatIdValidation\Components
 */
class VatIdCustomerInformation extends VatIdInformation
{
    /** @var  string */
    protected $company;

    /** @var  string */
    protected $street;

    /** @var  string */
    protected $zipCode;

    /** @var  string */
    protected $city;

    /**
     * Constructor sets all properties
     * @param string $vatId
     * @param string $company
     * @param string $street
     * @param string $zipCode
     * @param string $city
     */
    public function __construct($vatId, $company, $street, $zipCode, $city)
    {
        parent::__construct($vatId);

        $this->company = $company;
        $this->street = $street;
        $this->zipCode = $zipCode;
        $this->city = $city;
    }

    /**
     * Returns the company
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Returns the street
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Returns the zipcode
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Returns the city
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }
}