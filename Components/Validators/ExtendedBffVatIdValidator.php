<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components\Validators;

use SwagVatIdValidation\Components\VatIdCustomerInformation;
use SwagVatIdValidation\Components\VatIdInformation;

/**
 * Extended Bff Validator:
 * - used when shop VAT ID is German, customer VAT ID is foreign and extended check is enabled
 * - checks the VAT ID and the company's name, street and street number, zip code and city
 * - returns a detailed error message, if the VAT ID is invalid
 * - the API itself checks the address data
 * - an official mail confirmation can be optionally requested
 */
class ExtendedBffVatIdValidator extends BffVatIdValidator
{
    /**
     * Puts the customer and shop information into the format the API needs it.
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return [
            'UstId_1' => $shopInformation->getVatId(),
            // The bff validator api does only support 'EL' as greece iso. Therefore, we replace the original GR with the EL.
            'UstId_2' => \str_replace('GR', 'EL', $customerInformation->getVatId()),
            'Firmenname' => $customerInformation->getCompany(),
            'Ort' => $customerInformation->getCity(),
            'PLZ' => $customerInformation->getZipCode(),
            'Strasse' => $customerInformation->getStreet(),
            'Druck' => $this->confirmation ? 'ja' : 'nein',
        ];
    }

    /**
     * Evaluates the returned address data of a validation request
     * The Bff validator checks the committed address data itself, so it returns the result of each comparison
     * (A = valid, B = invalid, C = not requested, D = state does not approve)
     */
    protected function addExtendedResults($response)
    {
        $extendedResults = [
            'company' => $response['Erg_Name'],
            'street' => $response['Erg_Str'],
            'zipCode' => $response['Erg_PLZ'],
            'city' => $response['Erg_Ort'],
        ];

        $extendedResults = \array_keys($extendedResults, 'B', true);

        if (\in_array('company', $extendedResults, true)) {
            $this->result->setCompanyInvalid();
        }

        if (\in_array('street', $extendedResults, true)) {
            $this->result->setStreetInvalid();
        }

        if (\in_array('zipCode', $extendedResults, true)) {
            $this->result->setZipCodeInvalid();
        }

        if (\in_array('city', $extendedResults, true)) {
            $this->result->setCityInvalid();
        }
    }
}
