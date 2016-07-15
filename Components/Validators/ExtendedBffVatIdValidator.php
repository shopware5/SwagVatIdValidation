<?php

/**
 * Shopware 5
 * Copyright (c) shopware AG
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
 * Extended Bff Validator:
 * - used when shop VAT ID is German, customer VAT ID is foreign and extended check is enabled
 * - checks the VAT ID and the company's name, street and street number, zip code and city
 * - returns a detailed error message, if the VAT ID is invalid
 * - the API itself checks the address data
 * - an official mail confirmation can be optionally requested
 *
 * Class ExtendedBffVatIdValidator
 * @package Shopware\Plugins\SwagVatIdValidation\Components\Validators
 */
class ExtendedBffVatIdValidator extends BffVatIdValidator
{
    /**
     * Puts the customer and shop information into the format the API needs it.
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return array
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return [
            'UstId_1' => $shopInformation->getVatId(),
            'UstId_2' => $customerInformation->getVatId(),
            'Firmenname' => $customerInformation->getCompany(),
            'Ort' => $customerInformation->getCity(),
            'PLZ' => $customerInformation->getZipCode(),
            'Strasse' => $customerInformation->getStreet(),
            'Druck' => ($this->confirmation) ? 'ja' : 'nein'
        ];
    }

    /**
     * Evaluates the returned address data of a validation request
     * The Bff validator checks the committed address data itself, so it returns the result of each comparison
     * (A = valid, B = invalid, C = not requested, D = state does not approve)
     * @param array $response
     * @return VatIdValidatorResult
     */
    protected function addExtendedResults($response)
    {
        $extendedResults = [
            'company' => $response['Erg_Name'],
            'street' => $response['Erg_Str'],
            'zipCode' => $response['Erg_PLZ'],
            'city' => $response['Erg_Ort']
        ];

        $extendedResults = array_keys($extendedResults, 'B', true);

        if (in_array('company', $extendedResults)) {
            $this->result->setCompanyInvalid();
        }

        if (in_array('street', $extendedResults)) {
            $this->result->setStreetInvalid();
        }

        if (in_array('zipCode', $extendedResults)) {
            $this->result->setZipCodeInvalid();
        }

        if (in_array('city', $extendedResults)) {
            $this->result->setCityInvalid();
        }
    }
}
