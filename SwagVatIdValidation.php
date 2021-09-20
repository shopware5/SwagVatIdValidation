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
