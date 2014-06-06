<?php
/**
 * Shopware 4
 * Copyright © shopware AG
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
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidationStatus;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;

class DummyVatIdValidator implements VatIdValidatorInterface
{
    /**
     * The dummy Validator checks whether the Vat Ids can be valid or not.
     * Vat Ids, that have an invalid format, have not to be written in the database for later checks.
     */

    /**
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation = null)
    {
        $errors = $this->checkVatId($customerInformation);

        if (!empty($errors)) {
            return new VatIdValidatorResult(VatIdValidationStatus::INVALID, $errors);
        }

        return new VatIdValidatorResult(VatIdValidationStatus::VALID);
    }

    /**
     * @param VatIdInformation $information
     * @return array
     */
    private function checkVatId(VatIdInformation $information)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/dummyValidator');

        $errors = array();

        if ($information->getVatId() === '') {
            return $errors;
        }

        //All Vat-IDs have a length of 7 to 14 chars
        if (strlen($information->getVatId()) < 7) {
            $errors[] = $snippets->get('error1');
        } elseif (strlen($information->getVatId()) > 14) {
            $errors[] = $snippets->get('error2');
        }

        //The CountyCode always only consists of letters
        if (!ctype_alpha($information->getCountryCode())) {
            $errors[] = $snippets->get('error3');
        }

        //The VatNumber always only consists of alphanumerical chars
        if (!ctype_alnum($information->getVatNumber())) {
            $errors[] = $snippets->get('error4');
        }

        return $errors;
    }
}