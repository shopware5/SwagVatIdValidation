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

/**
 * Simple Mias Validator:
 * - will be used when shop VAT ID is foreign or customer VAT ID is german. Extended check is disabled.
 * - checks only the VAT ID
 * - returns an error message, if the VAT ID is invalid
 *
 * Class SimpleMiasVatIdValidator
 * @package Shopware\Plugins\SwagVatIdValidation\Components\Validators
 */
class SimpleMiasVatIdValidator extends MiasVatIdValidator
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
     * @param array $response
     * @param VatIdCustomerInformation $customerInformation
     */
    protected function addExtendedResults($response, VatIdCustomerInformation $customerInformation)
    {
    }
}
