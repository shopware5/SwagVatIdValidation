<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use SwagVatIdValidation\Bootstrap\Installer;
use SwagVatIdValidation\Bootstrap\Uninstaller;
use SwagVatIdValidation\Components\SoapExtensionChecker\SoapExtensionChecker;

class SwagVatIdValidation extends Plugin
{
    public function install(InstallContext $context)
    {
        $soapExtensionChecker = new SoapExtensionChecker();
        $soapExtensionChecker->check();

        $installer = new Installer(
            $this->container->get('models')
        );

        $installer->install();
    }

    public function uninstall(UninstallContext $context)
    {
        $uninstaller = new Uninstaller(
            $this->container->get('models')
        );

        $uninstaller->uninstall($context->keepUserData());

        $context->scheduleClearCache(UninstallContext::CACHE_LIST_ALL);
    }

    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(ActivateContext::CACHE_LIST_ALL);
    }

    public function deactivate(DeactivateContext $context)
    {
        $context->scheduleClearCache(DeactivateContext::CACHE_LIST_ALL);
    }
}
