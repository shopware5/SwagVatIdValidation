<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class VatIdCustomerInformation extends VatIdInformation
{
    protected $company;
    protected $street;
    protected $zipCode;
    protected $city;

    public function __construct($vatId, $company, $street, $zipCode, $city)
    {
        parent::__construct($vatId);

        $this->company = $company;
        $this->street = $street;
        $this->zipCode = $zipCode;
        $this->city = $city;
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