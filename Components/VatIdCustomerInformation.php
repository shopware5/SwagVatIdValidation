<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

use Shopware\Models\Customer\Billing;

class VatIdCustomerInformation extends VatIdInformation
{
    protected $company;
    protected $street;
    protected $zipCode;
    protected $city;

    public function __construct(Billing $billing, $vatId = null)
    {
        if(!isset($vatId)) {
            $vatId = $billing->getVatId();
        }

        parent::__construct($vatId);

        $this->company = $billing->getCompany();
        $this->street = $billing->getStreet() . ' ' . $billing->getStreetNumber();
        $this->zipCode = $billing->getZipCode();
        $this->city = $billing->getCity();
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function getZipCode()
    {
        return $this->zipCode;
    }

    public function getCity()
    {
        return $this->city;
    }
}