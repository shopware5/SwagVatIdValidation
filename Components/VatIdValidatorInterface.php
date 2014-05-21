<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

/**
 * Interface VarValidator
 */
interface VatIdValidatorInterface
{
    /**
     * @param VatIdInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);
}