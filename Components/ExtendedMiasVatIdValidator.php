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
        $company = $response->traderName;

        $address = explode("\n", $response->traderAddress);

        $street = trim($address[0]);

        $address = trim($address[1]);
        $address = explode(' ', $address, 2);

        $zipCode = $address[0];
        $city = $address[1];

        $company = $this->validateString($company, $customerInformation->getCompany(), $result, 'company');
        $street = $this->validateString($street, $customerInformation->getStreet(), $result, 'street');
        $zipCode = $this->validateString($zipCode, $customerInformation->getZipCode(), $result, 'zipCode', 80);
        $city = $this->validateString($city, $customerInformation->getCity(), $result, 'city');

        $result->setExtendedStatus($company, $street, $zipCode, $city);

        return $result;
    }

    /**
     * @param $string1
     * @param $string2
     * @param VatIdValidatorResult $result
     * @param $key
     * @return int
     */
    private function validateString($string1, $string2, VatIdValidatorResult $result, $key, $minPercentage = 85)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/main');

        if ($this->isSimiliar($string1, $string2, $minPercentage)) {
            return VatIdValidatorResult::VALID;
        }

        $result->addError($snippets->get('validator/extended/error/' . $key), $key);
        return VatIdValidatorResult::INVALID;
    }

    /**
     * @param $string1
     * @param $string2
     * @param int $minPercentage
     * @return bool
     */
    private function isSimiliar($string1, $string2, $minPercentage = 90)
    {
        $string1 = strtolower($string1);
        $string2 = strtolower($string2);

        similar_text($string1, $string2, $percentage);

        return ($percentage >= $minPercentage);
    }
}