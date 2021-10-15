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

class VatIdInformation
{
    /**
     * @var string
     */
    protected $vatId;

    /**
     * @var string
     */
    protected $countryCode;

    /**
     * @var string
     */
    protected $vatNumber;

    /**
     * Constructor sets all properties
     *
     * @param string $vatId
     */
    public function __construct($vatId)
    {
        $this->vatId = $vatId;
        $this->countryCode = \substr($this->vatId, 0, 2);
        $this->vatNumber = \substr($this->vatId, 2);
    }

    /**
     * Returns the VAT ID
     *
     * @return string
     */
    public function getVatId()
    {
        return $this->vatId;
    }

    /**
     * Returns the country code (p.e. DE123456789 => DE)
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Returns the VAT number (p.e. DE123456789 => 123456789)
     *
     * @return string
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }
}
