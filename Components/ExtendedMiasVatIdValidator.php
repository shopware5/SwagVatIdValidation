<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class ExtendedMiasVatIdValidator extends MiasVatIdValidator
{
    /**
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return array(
            'countryCode' => $customerInformation->getCountryCode(),
            'vatNumber' => $customerInformation->getVatNumber(),
            'traderName' => $customerInformation->getCompany(),
            'traderCompanyType' => '',
            'traderPostcode' => $customerInformation->getZipCode(),
            'traderCity' => $customerInformation->getCity(),
            'requesterCountryCode' => $shopInformation->getCountryCode(),
            'requesterVatNumber' => $shopInformation->getVatNumber(),
        );
    }

    /**
     * @param VatIdValidatorResult $result
     * @param array $response
     * @return VatIdValidatorResult
     */
    protected function addExtendedResults(VatIdValidatorResult $result, $response, VatIdCustomerInformation $customerInformation)
    {
        if (!$response->traderAddress) {
            return $result;
        }

        $extendedData = array();

        $extendedData['company'] = array($response->traderName, $customerInformation->getCompany());

        $address = explode("\n", $response->traderAddress);
        $extendedData['street'] = array($address[0], $customerInformation->getStreet());

        $address = trim($address[1]);
        $address = explode(' ', $address, 2);

        $extendedData['zipCode'] = array($address[0], $customerInformation->getZipCode());
        $extendedData['city'] = array($address[1], $customerInformation->getCity());

        foreach ($extendedData as $key => &$data) {
            $string1 = trim($data[0]);
            $string2 = trim($data[1]);

            $data = $this->validateString($string1, $string2, $result, $key);
        }

        if ($extendedData['company']) {
            $result->setStatus(VatIdValidationStatus::COMPANY_OK);
        }

        if ($extendedData['street']) {
            $result->setStatus(VatIdValidationStatus::STREET_OK);
        }

        if ($extendedData['zipCode']) {
            $result->setStatus(VatIdValidationStatus::ZIP_CODE_OK);
        }

        if ($extendedData['city']) {
            $result->setStatus(VatIdValidationStatus::CITY_OK);
        }

        return $result;
    }

    /**
     * @param string $string1
     * @param string $string2
     * @param VatIdValidatorResult $result
     * @param string $key
     * @return bool
     */
    private function validateString($string1, $string2, VatIdValidatorResult $result, $key)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/main');

        if ($this->isSimiliar($string1, $string2)) {
            return true;
        }

        $result->addError($snippets->get('validator/extended/error/' . $key), $key);
        return false;
    }

    /**
     * @param $string1
     * @param $string2
     * @param int $minPercentage
     * @return bool
     */
    private function isSimiliar($string1, $string2, $minPercentage = 80)
    {
        $string1 = strtolower($string1);
        $string2 = strtolower($string2);

        similar_text($string1, $string2, $percentage);

        return ($percentage >= $minPercentage);
    }
}