<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
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

namespace Shopware\CustomModels\SwagVatIdValidation;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping AS ORM;

/**
 * Shopware SwagForum Plugin - Forum Model
 *
 * @category  Shopware
 * @package   Shopware\Plugins\SwagVatIdValidation\Models
 * @copyright Copyright (c) 2012, shopware AG (http://www.shopware.de)
 *
 * @ORM\Table(name="s_plugin_swag_vat_id_checks")
 * @ORM\Entity(repositoryClass="Repository")
 */
class VatIdCheck extends ModelEntity
{
    /**
     * Unique identifier
     *
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="vatId", type="string", nullable=false)
     */
    protected $vatId;

    /**
     * @ORM\OneToOne(
     *      targetEntity="\Shopware\Models\Customer\Billing",
     *      inversedBy="vatIdCheck"
     * )
     *
     * @ORM\JoinColumn(name="billingAddressId", referencedColumnName="id")
     * @var \Shopware\Models\Customer\Billing
     */
    protected $billingAddress;

    /**
     * @var integer
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * Constants for status column
     */
    const UNCHECKED = 0;
    const CHECKED = 1;
    const VAT_ID_OK = 2;
    const COMPANY_OK = 4;
    const STREET_OK = 8;
    const ZIP_CODE_OK = 16;
    const CITY_OK = 32;
    const VALID = 63;

    /**
     * Getter function for the unique id identifier property
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter function for the company column property
     *
     * @param  \Shopware\Models\Customer\Billing
     * @return VatIdCheck
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * Getter function for the company column property.
     *
     * @return \Shopware\Models\Customer\Billing
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param string $vatId
     * @return VatIdCheck
     */
    public function setVatId($vatId)
    {
        $this->vatId = $vatId;

        return $this;
    }

    /**
     * @return string
     */
    public function getVatId()
    {
        return $this->vatId;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}