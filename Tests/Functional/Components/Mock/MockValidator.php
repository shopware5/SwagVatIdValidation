<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
