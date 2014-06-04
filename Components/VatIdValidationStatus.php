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

class VatIdValidationStatus
{
    protected $status;

    //Flags
    const CHECKED = 1;
    const VAT_ID_OK = 2;
    const COMPANY_OK = 4;
    const STREET_OK = 8;
    const ZIP_CODE_OK = 16;
    const CITY_OK = 32;

    //States

    /**
     * Status 0 happens when
     * - validation service was unavailable
     * - the check is still not executed
     */
    const UNAVAILABLE = 0;
    const UNCHECKED = 0;

    /** Status 1 happens when
     * - the check was executed, but nothing was valid
     * - the dummy validator found an error
     */
    const INVALID = 1;
    const DUMMY_INVALID = 1;

    /**
     * Status 2 happens when
     * - the dummy validator check was successful
     */
    const DUMMY_VALID = 2;

    /**
     * Status 3 can be used for
     * - setting the VatId was checked and is valid
     */
    const VAT_ID_VALID = 3;

    /**
     * Status 60 can be used for
     * - setting all address relevant flags valid
     */
    const ADDRESS_VALID = 60;

    /**
     * Status 63 happens when
     * - the check was executed and each was valid
     */
    const VALID = 63;


    public function __construct($status = 0)
    {
        $this->status = $status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status |= $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->status === $this::VALID);
    }

    /**
     * Is true when the requested validation service was unavailable but the dummy validator check was successful
     * @return bool
     */
    public function isDummyValid()
    {
        return ($this->status === $this::DUMMY_VALID);
    }

    /**
     * @return bool
     */
    public function serviceNotAvailable()
    {
        return ($this->status === $this::UNAVAILABLE);
    }

    /**
     * @return bool
     */
    public function isVatIdValid()
    {
        return (($this->status & $this::CHECKED) && ($this->status & $this::VAT_ID_OK));
    }

    /**
     * @return bool
     */
    public function isCompanyValid()
    {
        return ($this->isVatIdValid() && ($this->status & $this::COMPANY_OK));
    }

    /**
     * @return bool
     */
    public function isStreetValid()
    {
        return ($this->isVatIdValid() && ($this->status & $this::STREET_OK));
    }

    /**
     * @return bool
     */
    public function isZipCodeValid()
    {
        return ($this->isVatIdValid() && ($this->status & $this::ZIP_CODE_OK));
    }

    /**
     * @return bool
     */
    public function isCityValid()
    {
        return ($this->isVatIdValid() && ($this->status & $this::CITY_OK));
    }
}