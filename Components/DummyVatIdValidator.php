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

        return new VatIdValidatorResult(VatIdValidatorResult::DUMMY_VALID);
    }

    /**
     * @param VatIdInformation $information
     * @return array
     */
    private function checkVatId(VatIdInformation $information)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/dummyValidator');

        $errors = array();

        if (!$information)
        {
            $errors[] = $snippets->get('error5');
            return $errors;
        }

        //All Vat-IDs have a length of 7 to 14 chars
        if (strlen($information->getVatId()) < 7)
        {
            $errors[] = $snippets->get('error1');
        }
        elseif (strlen($information->getVatId()) > 14)
        {
            $errors[] = $snippets->get('error2');
        }

        //The CountyCode always only consists of letters
        if (!ctype_alpha($information->getCountryCode()))
        {
            $errors[] = $snippets->get('error3');
        }

        //The VatNumber always only consists of alphanumerical chars
        if (!ctype_alnum($information->getVatNumber()))
        {
            $errors[] = $snippets->get('error4');
        }

        return $errors;
    }
}