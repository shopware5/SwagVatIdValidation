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

class DummyVatIdValidator implements VatIdValidatorInterface
{
    /**
     * The dummy Validator checks whether the Vat Ids can be valid or not.
     * Vat Ids, that have an invalid format, have not to be written in the database for later checks.
     */

    /** @var  VatIdValidatorResult */
    private $result;

    /**
     * Constructor sets the snippet namespace
     */
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->result = new VatIdValidatorResult($snippetManager, 'dummyValidator');
    }

    /**
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation = null)
    {
        if ($customerInformation->getVatId() === '') {
            return $this->result;
        }

        //All Vat-IDs have a length of 7 to 14 chars
        if (strlen($customerInformation->getVatId()) < 7) {
            $this->result->setVatIdInvalid('1');
        } elseif (strlen($customerInformation->getVatId()) > 14) {
            $this->result->setVatIdInvalid('2');
        }

        //The CountyCode always only consists of letters
        if (!ctype_alpha($customerInformation->getCountryCode())) {
            $this->result->setVatIdInvalid('3');
        }

        //The VatNumber always only consists of alphanumerical chars
        if (!ctype_alnum($customerInformation->getVatNumber())) {
            $this->result->setVatIdInvalid('4');
        }

        return $this->result;
    }
}