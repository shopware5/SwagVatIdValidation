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

namespace SwagVatIdValidation\Components;

use Shopware\Models\Customer\Address;

interface ValidationServiceInterface
{
    /**
     * Helper method returns true if the VAT ID is required
     *
     * @param string $company
     * @param int    $countryId
     *
     * @return bool
     */
    public function isVatIdRequired($company, $countryId);

    /**
     * Helper function for the whole validation process
     * If billing Id is set, the matching customer billing address will be removed if validation result is invalid
     *
     * @param Address $billingAddress
     * @param bool    $deleteVatIdFromAddress
     *
     * @return VatIdValidatorResult
     */
    public function validateVatId(Address $billingAddress, $deleteVatIdFromAddress = true);

    /**
     * Helper method to get a valid result if the VAT ID is required but empty
     *
     * @return VatIdValidatorResult
     */
    public function getRequirementErrorResult();
}
