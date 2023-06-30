<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
