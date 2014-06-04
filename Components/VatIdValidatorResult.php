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

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class VatIdValidatorResult extends VatIdValidationStatus
{
    /**
     * @var array
     */
    private $errors;

    /**
     * @param $status
     * @param array $errors
     */
    public function __construct($status = 0, $errors = array())
    {
        parent::__construct($status);
        $this->errors = $errors;
    }

    /**
     * @param string $error
     * @param string $key
     */
    public function addError($error, $key = '')
    {
        $this->errors[$key] = $error;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
         return $this->errors;
    }
}