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
 * Simple Mias Validator:
 * - will be used when shop VAT ID is foreign or customer VAT ID is german. Extended check is disabled.
 * - checks only the VAT ID
 * - returns an error message, if the VAT ID is invalid
 */
class SimpleMiasVatIdValidator extends MiasVatIdValidator
{
    /**
     * Puts the customer and shop information into the format the API needs it.
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return [
            'countryCode' => $customerInformation->getCountryCode(),
            'vatNumber' => $customerInformation->getVatNumber(),
            'traderName' => '',
            'traderCompanyType' => '',
            'traderPostcode' => '',
            'traderCity' => '',
            'requesterCountryCode' => $shopInformation->getCountryCode(),
            'requesterVatNumber' => $shopInformation->getVatNumber(),
        ];
    }

    /**
     * Only useful in extended validators
     */
    protected function addExtendedResults($response, VatIdCustomerInformation $customerInformation)
    {
    }
}
