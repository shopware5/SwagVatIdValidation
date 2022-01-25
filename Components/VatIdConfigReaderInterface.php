<?php
declare(strict_types=1);
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
