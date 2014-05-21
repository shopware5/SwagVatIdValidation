<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class SimpleBffVatIdValidator extends BffVatIdValidator
{
    /**
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return array
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return array(
            'UstId_1' => $shopInformation->getVatId(),
            'UstId_2' => $customerInformation->getVatId(),
            'Firmenname' => '',
            'Ort' => '',
            'PLZ' => '',
            'Strasse' => '',
            'Druck' => ''
        );
    }
}