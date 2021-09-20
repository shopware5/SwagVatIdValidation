<?php
/**
 * Shopware Plugins
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this plugin can be used under
 * a proprietary license as set forth in our Terms and Conditions,
 * section 2.1.2.2 (Conditions of Usage).
 *
 * The text of our proprietary license additionally can be found at and
 * in the LICENSE file you have received along with this plugin.
 *
 * This plugin is distributed in the hope that it will be useful,
 * with LIMITED WARRANTY AND LIABILITY as set forth in our
 * Terms and Conditions, sections 9 (Warranty) and 10 (Liability).
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the plugin does not imply a trademark license.
 * Therefore any rights, title and interest in our trademarks
 * remain entirely with us.
 */

namespace SwagVatIdValidation\Components\Validators;

use SwagVatIdValidation\Components\VatIdCustomerInformation;
use SwagVatIdValidation\Components\VatIdInformation;

/**
 * Extended Mias Validator:
 * - will be used when shop VAT ID is foreign or customer's VAT ID is German. Extended check is enabled.
 * - checks the VAT ID and the company's name, street and street number, zip code and city
 * - returns an error message, if the VAT ID is invalid
 * - the API itself doesn't check the address data, the validator class does it manually
 * - an official mail confirmation can't be requested
 */
class ExtendedMiasVatIdValidator extends MiasVatIdValidator
{
    /**
     * Puts the customer and shop information into the format the API needs.
     *
     * @return array
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return [
            'countryCode' => $customerInformation->getCountryCode(),
            'vatNumber' => $customerInformation->getVatNumber(),
            'traderName' => $customerInformation->getCompany(),
            'traderCompanyType' => '',
            'traderPostcode' => $customerInformation->getZipCode(),
            'traderCity' => $customerInformation->getCity(),
            'requesterCountryCode' => $shopInformation->getCountryCode(),
            'requesterVatNumber' => $shopInformation->getVatNumber(),
        ];
    }

    /**
     * Evaluates the returned address data of a validation request
     * Because the Mias service doesn't compare the address data itself, but rather return the deposited address data
     * (if approved), this validator class has to compare the returned address with the provided address itself.
     *
     * @param array $response
     */
    protected function addExtendedResults($response, VatIdCustomerInformation $customerInformation)
    {
        if (!$response->traderAddress) {
            return;
        }

        $extendedData = [];

        $extendedData['company'] = [$response->traderName, $customerInformation->getCompany()];

        $address = \explode("\n", $response->traderAddress);
        $extendedData['street'] = [$address[0], $customerInformation->getStreet()];

        $address = \trim($address[1]);
        $address = \explode(' ', $address, 2);

        $extendedData['zipCode'] = [$address[0], $customerInformation->getZipCode()];
        $extendedData['city'] = [$address[1], $customerInformation->getCity()];

        foreach ($extendedData as &$data) {
            $string1 = \trim($data[0]);
            $string2 = \trim($data[1]);

            $data = $this->validateString($string1, $string2);
        }
        unset($data);

        if (!$extendedData['company']) {
            $this->result->setCompanyInvalid();
        }

        if (!$extendedData['street']) {
            $this->result->setStreetInvalid();
        }

        if (!$extendedData['zipCode']) {
            $this->result->setZipCodeInvalid();
        }

        if (!$extendedData['city']) {
            $this->result->setCityInvalid();
        }
    }

    /**
     * Helper function to check the similarity of two address data strings
     * If the difference is too big, the correct error message will be set to result
     *
     * @param string $string1
     * @param string $string2
     *
     * @return bool
     */
    private function validateString($string1, $string2)
    {
        if ($this->isSimilar($string1, $string2)) {
            return true;
        }

        return false;
    }

    /**
     * Helper function to check the similarity of two strings. On default, there have to
     * be a minimum accordance of 75%.
     *
     * @param int $minPercentage
     *
     * @return bool
     */
    private function isSimilar($string1, $string2, $minPercentage = 75)
    {
        $string1 = \strtolower($string1);
        $string2 = \strtolower($string2);

        \similar_text($string1, $string2, $percentage);

        return $percentage >= $minPercentage;
    }
}
