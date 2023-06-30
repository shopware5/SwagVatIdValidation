<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     */
    public function __construct(string $vatId)
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
