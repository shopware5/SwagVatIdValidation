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
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;

/**
 * Dummy validation
 * The dummy validator checks if the VAT ID could be valid. Empty VAT IDs are also okay.
 * The validator fails when:
 * - VAT ID is shorter than 7 or longer than 14 chars
 * - Country Code includes non-alphabetical chars
 * - VAT Number includes non-alphanumerical chars
 *
 * Class DummyVatIdValidator
 * @package Shopware\Plugins\SwagVatIdValidation\Components\Validators
 */
class DummyVatIdValidator implements VatIdValidatorInterface
{
    /** @var  VatIdValidatorResult */
    private $result;

    /**
     * Constructor sets the snippet namespace
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->result = new VatIdValidatorResult($snippetManager, 'dummyValidator');
    }

    /**
     * Check process of a validator
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation = null)
    {
        if ($customerInformation->getVatId() === '') {
            $this->result->setVatIdInvalid(4);
            return $this->result;
        }

        //All VAT IDs have a length of 7 to 14 chars
        if (strlen($customerInformation->getVatId()) < 7) {
            $this->result->setVatIdInvalid('1');
        } elseif (strlen($customerInformation->getVatId()) > 14) {
            $this->result->setVatIdInvalid('2');
        }

        //The country code always only consists of letters
        if (!ctype_alpha($customerInformation->getCountryCode())) {
            $this->result->setVatIdInvalid('3');
        }

        //The VAT number always only consists of alphanumerical chars
        if (!ctype_alnum($customerInformation->getVatNumber())) {
            $this->result->setVatIdInvalid('4');
        }

        //If vat number only consists alphas its invalid
        if(ctype_alpha($customerInformation->getVatNumber())) {
            $this->result->setVatIdInvalid('5');
        }

        return $this->result;
    }
}