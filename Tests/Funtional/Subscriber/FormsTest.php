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

namespace SwagVatIdValidation\Tests\Functional\Subscriber;

use PHPUnit\Framework\TestCase;
use SwagVatIdValidation\Subscriber\Forms;
use SwagVatIdValidation\Tests\PluginConfigCacheTrait;

class FormsTest extends TestCase
{
    use PluginConfigCacheTrait;

    public function testOnRegister(): void
    {
        $request = new \Enlight_Controller_Request_RequestHttp();
        $request->setActionName('index');

        $view = new \Enlight_View_Default(new \Enlight_Template_Manager());

        $controller = new \Shopware_Controllers_Frontend_Register();
        $controller->setRequest($request);
        $controller->setView($view);

        $eventArgs = new \Enlight_Event_EventArgs(['subject' => $controller]);

        $this->clearCache();

        $this->getFormSubscriber()->onRegister($eventArgs);

        $result = $controller->View()->getAssign('countryIsoIdList');

        static::assertSame(
            '["2","5","7","8","9","10","11","12","14","18","21","24","25","27","30","31","33","34","35","38","39","40","41","42","43","44","45","209"]',
            $result
        );
    }

    public function testOnRegisterResultShouldBeEmpty(): void
    {
        $request = new \Enlight_Controller_Request_RequestHttp();
        $request->setActionName('anyAction');

        $view = new \Enlight_View_Default(new \Enlight_Template_Manager());

        $controller = new \Shopware_Controllers_Frontend_Register();
        $controller->setRequest($request);
        $controller->setView($view);

        $eventArgs = new \Enlight_Event_EventArgs(['subject' => $controller]);

        $this->getFormSubscriber()->onRegister($eventArgs);

        $result = $controller->View()->getAssign('countryIsoIdList');

        static::assertNull($result);
    }

    private function getFormSubscriber(): Forms
    {
        return Shopware()->Container()->get('swag_vat_id_validation.subscriber.forms');
    }
}
