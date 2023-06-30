<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components;

use Shopware\Models\Customer\Address;

interface ValidationServiceInterface
{
    /**
     * Helper method returns true if the VAT ID is required
     *
     * @param string|null $company
     * @param int         $countryId
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
