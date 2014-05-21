<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class VatIdInformation
{
    protected $vatId;
    protected $countryCode;
    protected $vatNumber;

    public function __construct($vatId)
    {
        $this->vatId = str_replace(array(' ', '.', '-', ',', ', '), '', trim($vatId));
        $this->countryCode = substr($this->vatId, 0, 2);
        $this->vatNumber = substr($this->vatId, 2);
    }

    public function getVatId()
    {
        return $this->vatId;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function getVatNumber()
    {
        return $this->vatNumber;
    }
}