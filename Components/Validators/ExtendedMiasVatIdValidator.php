<?php
/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Plugins\SwagVatIdValidation\Components\Validators;

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;

/**
 * Extended Mias Validator:
 * - will be used when shop VAT ID is foreign or customer's VAT ID is German. Extended check is enabled.
 * - checks the VAT ID and the company's name, street and street number, zip code and city
 * - returns an error message, if the VAT ID is invalid
 * - the API itself doesn't check the address data, the validator class does it manually
 * - an official mail confirmation can't be requested
 *
 * Class ExtendedMiasVatIdValidator
 * @package Shopware\Plugins\SwagVatIdValidation\Components\Validators
 */
class ExtendedMiasVatIdValidator extends MiasVatIdValidator
{
    /**
     * Puts the customer and shop information into the format the API needs.
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return array
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
     * Evaluates the returned address data of a validation request
     * Because the Mias service doesn't compare the address data itself, but rather return the deposited address data
     * (if approved), this validator class has to compare the returned address with the provided address itself.
     * @param array $response
     * @param VatIdCustomerInformation $customerInformation
     */
    protected function addExtendedResults($response, VatIdCustomerInformation $customerInformation)
    {
        if (!$response->traderAddress) {
            return;
        }

        $extendedData = array();

        $extendedData['company'] = array($response->traderName, $customerInformation->getCompany());

        $address = explode("\n", $response->traderAddress);
        $extendedData['street'] = array($address[0], $customerInformation->getStreet());

        $address = trim($address[1]);
        $address = explode(' ', $address, 2);

        $extendedData['zipCode'] = array($address[0], $customerInformation->getZipCode());
        $extendedData['city'] = array($address[1], $customerInformation->getCity());

        foreach ($extendedData as &$data) {
            $string1 = trim($data[0]);
            $string2 = trim($data[1]);

            $data = $this->validateString($string1, $string2);
        }

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
     * @param string $string1
     * @param string $string2
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
     * @param $string1
     * @param $string2
     * @param int $minPercentage
     * @return bool
     */
    private function isSimilar($string1, $string2, $minPercentage = 75)
    {
        $string1 = strtolower($string1);
        $string2 = strtolower($string2);

        similar_text($string1, $string2, $percentage);

        return ($percentage >= $minPercentage);
    }
}
