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

use Shopware\Models\Customer\Address;

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

    /** @var  string */
    protected $billingCountryIso;

    /**
     * Constructor sets all properties
     *
     * @param Address $billingAddress
     */
    public function __construct(Address $billingAddress)
    {
        parent::__construct($billingAddress->getVatId());

        $this->company = $billingAddress->getCompany();
        $this->street = $billingAddress->getStreet();
        $this->zipCode = $billingAddress->getZipcode();
        $this->city = $billingAddress->getCity();
        $this->billingCountryIso = $billingAddress->getCountry()->getIso();
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
     * Returns the zip code
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

    /**
     * Returns the iso code of the billing country
     * @return string
     */
    public function getBillingCountryIso()
    {
        return $this->billingCountryIso;
    }
}
