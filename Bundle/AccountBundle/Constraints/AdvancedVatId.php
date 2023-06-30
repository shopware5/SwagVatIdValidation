<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Bundle\AccountBundle\Constraints;

use Shopware\Models\Customer\Address;
use Symfony\Component\Validator\Constraint;

class AdvancedVatId extends Constraint
{
    /**
     * @var Address
     */
    public $address;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'swag_vat_id_validation.advanced_vat_id_validator';
    }
}
