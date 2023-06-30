<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
