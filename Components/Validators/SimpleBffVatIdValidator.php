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
 * Simple Bff Validator:
 * - will be used when shop VAT ID is german, customer VAT ID is foreign and extended check is disabled
 * - checks only the VAT ID
 * - returns a detailed error message, if the VAT ID is invalid
 */
class SimpleBffVatIdValidator extends BffVatIdValidator
{
    /**
     * Puts the customer and shop information into the format the API needs it.
     */
    protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        return [
            'UstId_1' => $shopInformation->getVatId(),
            'UstId_2' => $customerInformation->getVatId(),
            'Firmenname' => '',
            'Ort' => '',
            'PLZ' => '',
            'Strasse' => '',
            'Druck' => '',
        ];
    }

    /**
     * Only useful in extended validators
     */
    protected function addExtendedResults($response)
    {
    }
}
