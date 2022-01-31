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
