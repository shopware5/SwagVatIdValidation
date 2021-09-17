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
     * @param bool $deleteVatIdFromAddress
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
