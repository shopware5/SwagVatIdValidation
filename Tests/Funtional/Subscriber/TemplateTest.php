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

use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use PHPUnit\Framework\TestCase;
use SwagVatIdValidation\Subscriber\Template;
use SwagVatIdValidation\Tests\ContainerTrait;

class TemplateTest extends TestCase
{
    use ContainerTrait;

    public function testOnPostDispatchFrontendAddressEditAcceptsActionEventArgs(): void
    {
        $messageToRemove = 'message to remove';
        $messageToStay = 'message to stay';

        $view = new \Enlight_View_Default(new \Enlight_Template_Manager());
        $view->assign([
            'error_messages' => [$messageToRemove, $messageToStay],
        ]);

        $controller = new \Shopware_Controllers_Frontend_Address();
        $controller->setView($view);

        $actionEventArgs = new ActionEventArgs([
            'subject' => $controller,
        ]);

        $session = $this->getContainer()->get('session');
        $session->offsetSet(Template::REMOVE_ERROR_FIELDS_MESSAGE, true);

        $this->getSubscriber()->onPostDispatchFrontendAddressEdit($actionEventArgs);

        $result = $view->getAssign('error_messages');

        static::assertCount(1, $result);
        static::assertSame($messageToStay, array_shift($result));
    }

    private function getSubscriber(): Template
    {
        return new Template(
            $this->getContainer()->get('swag_vat_id_validation.dependency_provider'),
            $this->getContainer()->get('snippets'),
            $this->getContainer()->get('config'),
            __DIR__ . '/../../../'
        );
    }
}
