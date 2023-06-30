<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components;

class APIValidationType
{
    /* The type for no api validation */
    public const NONE = 1;

    /* Providing this type will use simple api validation */
    public const SIMPLE = 2;

    /* Providing this type will use extended api validation */
    public const EXTENDED = 3;
}
