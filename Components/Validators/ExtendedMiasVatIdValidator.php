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
     */
    protected function addExtendedResults($response, VatIdCustomerInformation $customerInformation)
    {
        if (!isset($response->traderAddress)) {
            return;
        }

        $extendedData = [];

        $traderName = isset($response->traderName) ? $response->traderName : '';
        $extendedData['company'] = [$traderName, $customerInformation->getCompany()];

        $address = \explode("\n", $response->traderAddress);
        $extendedData['street'] = [$address[0], $customerInformation->getStreet()];

        $address = \trim($address[1]);
        $address = \explode(' ', $address, 2);

        $extendedData['zipCode'] = [$address[0], $customerInformation->getZipCode()];
        $extendedData['city'] = [$address[1], $customerInformation->getCity()];

        $validationResult = [];
        foreach ($extendedData as $key => $data) {
            $valueFromApi = \trim($data[0]);
            $valueFromShopware = \trim($data[1] ?? '');

            $validationResult[$key] = $this->validateString($valueFromApi, $valueFromShopware);
        }

        if (!$validationResult['company']) {
            $this->result->setCompanyInvalid();
        }

        if (!$validationResult['street']) {
            $this->result->setStreetInvalid();
        }

        if (!$validationResult['zipCode']) {
            $this->result->setZipCodeInvalid();
        }

        if (!$validationResult['city']) {
            $this->result->setCityInvalid();
        }
    }

    /**
     * Helper function to check the similarity of two address data strings
     * If the difference is too big, the correct error message will be set to result
     */
    private function validateString(string $valueFromApi, string $valueFromShopware): bool
    {
        $valueFromApi = \strtolower($valueFromApi);
        $valueFromShopware = \strtolower($valueFromShopware);

        \similar_text($valueFromApi, $valueFromShopware, $percentage);

        return $percentage >= 75;
    }
}
