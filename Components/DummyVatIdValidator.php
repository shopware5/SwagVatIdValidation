<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class DummyVatIdValidator implements VatIdValidatorInterface
{
    /**
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $errors = $this->checkVatId($shopInformation);

        if(!empty($errors)) {
            var_dump($errors);
        }

        $errors = $this->checkVatId($customerInformation);

        if(!empty($errors))
        {
            return new VatIdValidatorResult(VatIdValidatorResult::INVALID, $errors);
        }

        return new VatIdValidatorResult(VatIdValidatorResult::VALID);
    }

    /**
     * @param VatIdInformation $information
     * @return array
     */
    private function checkVatId(VatIdInformation $information)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/main');

        $errors = array();

        //All Vat-IDs have a length of 7 to 14 chars
        if (strlen($information->getVatId()) < 7)
        {
            $errors['209'] = $snippets->get('validator/bff/error209');
        }
        elseif (strlen($information->getVatId()) > 14)
        {
            $errors['209'] = $snippets->get('validator/bff/error209');
        }

        //The CountyCode always only consists of letters
        if (!ctype_alpha($information->getCountryCode()))
        {
            $errors['212'] = $snippets->get('validator/bff/error212');
        }

        //The VatNumber always only consists of alphanumerical chars
        if (!ctype_alnum($information->getVatNumber()))
        {
            $errors['211'] = $snippets->get('validator/bff/error211');
        }

        return array_values($errors);
    }
}