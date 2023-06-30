<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components;

interface VatIdConfigReaderInterface
{
    public const ALLOW_REGISTER_ON_API_ERROR = 'allow_register_on_api_error';

    public const VAT_ID = 'vatId';

    public const EMAIL_NOTIFICATION = 'shopEmailNotification';

    public const API_VALIDATION_TYPE = 'apiValidationType';

    public const OFFICIAL_CONFIRMATION = 'confirmation';

    public const DISABLED_COUNTRY_ISO_LIST = 'disabledCountryISOs';

    public const IS_VAT_ID_REQUIRED = 'vatId_is_required';

    public function getPluginConfig(): array;
}
