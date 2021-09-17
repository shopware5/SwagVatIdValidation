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
 * Simple Bff Validator:
 * - will be used when shop VAT ID is german, customer VAT ID is foreign and extended check is disabled
 * - checks only the VAT ID
 * - returns a detailed error message, if the VAT ID is invalid
 */
class SimpleBffVatIdValidator extends BffVatIdValidator
{
    /**
     * Puts the customer and shop information into the format the API needs it.
     *
     * @return array
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
     *
     * @param array $response
     */
    protected function addExtendedResults($response)
    {
    }
}
