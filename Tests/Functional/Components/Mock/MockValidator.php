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

namespace SwagVatIdValidation\Tests\Functional\Components\Mock;

use SwagVatIdValidation\Components\Validators\VatIdValidatorInterface;
use SwagVatIdValidation\Components\VatIdCustomerInformation;
use SwagVatIdValidation\Components\VatIdInformation;
use SwagVatIdValidation\Components\VatIdValidatorResult;

class MockValidator implements VatIdValidatorInterface
{
    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    public function __construct(
        \Shopware_Components_Snippet_Manager $snippetManager,
        \Shopware_Components_Config $config
    ) {
        $this->snippetManager = $snippetManager;
        $this->config = $config;
    }

    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $result = new VatIdValidatorResult(
            $this->snippetManager,
            '',
            $this->config
        );
        $result->setServiceUnavailable();

        return $result;
    }
}
