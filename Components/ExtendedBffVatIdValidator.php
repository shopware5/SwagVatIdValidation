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

    /**
     * @param VatIdValidatorResult $result
     * @param array $response
     * @return VatIdValidatorResult
     */
    protected function addExtendedResults(VatIdValidatorResult $result, $response)
    {
        $extendedResults = array(
            'company' => $response['Erg_Name'],
            'street' => $response['Erg_Str'],
            'zipCode' => $response['Erg_PLZ'],
            'city' => $response['Erg_Ort']
        );

        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/main');

        foreach ($extendedResults as $key => &$extendedResult) {
            if ($extendedResult === 'B') {
                $extendedResult = false;
                $result->addError($snippets->get('validator/extended/error/' . $key), $key);
                continue;
            }

            $extendedResult = true;
        }

        if ($extendedResults['company']) {
            $result->setStatus(VatIdValidationStatus::COMPANY_OK);
        }

        if ($extendedResults['street']) {
            $result->setStatus(VatIdValidationStatus::STREET_OK);
        }

        if ($extendedResults['zipCode']) {
            $result->setStatus(VatIdValidationStatus::ZIP_CODE_OK);
        }

        if ($extendedResults['city']) {
            $result->setStatus(VatIdValidationStatus::CITY_OK);
        }

        return $result;
    }
}