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
            if ($extendedResult === 'A') {
                $extendedResult = VatIdValidatorResult::VALID;
                continue;
            }

            if ($extendedResult === 'B') {
                $extendedResult = VatIdValidatorResult::INVALID;
                $result->addError($snippets->get('validator/extended/error/' . $key), $key);
                continue;
            }

            $extendedResult = VatIdValidatorResult::UNAVAILABLE;
        }

        $result->setExtendedStatus(
            $extendedResults['company'],
            $extendedResults['street'],
            $extendedResults['zipCode'],
            $extendedResults['city']
        );

        return $result;
    }
}