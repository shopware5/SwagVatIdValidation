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

class ExtendedMiasVatIdValidator extends MiasVatIdValidator
{
    /**
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
     * If the difference is to big, the correct error message will be set to result
     * @param string $string1
     * @param string $string2
     * @param VatIdValidatorResult $result
     * @param string $key
     * @return bool
     */
    private function validateString($string1, $string2)
    {
        if ($this->isSimiliar($string1, $string2)) {
            return true;
        }

        return false;
    }

    /**
     * Helper function to check the similarity of two strings. On default, there have to be a minimum accordance of 80%.
     * @param $string1
     * @param $string2
     * @param int $minPercentage
     * @return bool
     */
    private function isSimiliar($string1, $string2, $minPercentage = 75)
    {
        $string1 = strtolower($string1);
        $string2 = strtolower($string2);

        similar_text($string1, $string2, $percentage);

        return ($percentage >= $minPercentage);
    }
}