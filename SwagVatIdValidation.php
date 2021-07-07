<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
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
