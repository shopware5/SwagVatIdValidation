<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class SimpleMiasVatIdValidator extends MiasVatIdValidator
{
    /**
     * @param VatIdInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return array(
            'countryCode' => $customerInformation->getCountryCode(),
            'vatNumber' => $customerInformation->getVatNumber(),
            'traderName' => '',
            'traderCompanyType' => '',
            'traderPostcode' => '',
            'traderCity' => '',
            'requesterCountryCode' => $shopInformation->getCountryCode(),
            'requesterVatNumber' => $shopInformation->getVatNumber(),
        );
    }
}