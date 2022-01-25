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

use Shopware\Models\Customer\Address;

class VatIdCustomerInformation extends VatIdInformation
{
    /**
     * @var string|null
     */
    protected $company;

    /**
     * @var string|null
     */
    protected $street;

    /**
     * @var string
     */
    protected $zipCode;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string|null
     */
    protected $billingCountryIso;

    /**
     * Constructor sets all properties
     */
    public function __construct(Address $billingAddress)
    {
        parent::__construct((string) $billingAddress->getVatId());

        $this->company = $billingAddress->getCompany();
        $this->street = $billingAddress->getStreet();
        $this->zipCode = $billingAddress->getZipcode();
        $this->city = $billingAddress->getCity();
        $billingCountry = $billingAddress->getCountry();
        $this->billingCountryIso = $billingCountry ? $billingCountry->getIso() : null;
    }

    /**
     * Returns the company
     *
     * @return string|null
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Returns the street
     *
     * @return string|null
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Returns the zip code
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Returns the city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Returns the iso code of the billing country
     *
     * @return string|null
     */
    public function getBillingCountryIso()
    {
        return $this->billingCountryIso;
    }
}
