<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class ExtendedBffVatIdValidator extends BffVatIdValidator
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
            'Firmenname' => $customerInformation->getCompany(),
            'Ort' => $customerInformation->getCity(),
            'PLZ' => $customerInformation->getZipCode(),
            'Strasse' => $customerInformation->getStreet(),
            'Druck' => ($this->confirmation) ? 'ja' : 'nein'
        );
    }
}