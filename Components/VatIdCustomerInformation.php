<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
