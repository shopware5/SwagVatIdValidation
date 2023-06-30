<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Shopware\Models\Customer\Address;
use SwagVatIdValidation\Components\DependencyProviderInterface;

class AddressSubscriber implements EventSubscriber
{
    public const DELETE_VAT_ID_SESSION_FLAG = 'deleteVatIdFromAddress';

    /**
     * @var DependencyProviderInterface
     */
    private $dependencyProvider;

    public function __construct(DependencyProviderInterface $dependencyProvider)
    {
        $this->dependencyProvider = $dependencyProvider;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $arguments): void
    {
        $this->handle($arguments);
    }

    public function preUpdate(LifecycleEventArgs $arguments): void
    {
        $this->handle($arguments);
    }

    private function handle(LifecycleEventArgs $arguments): void
    {
        if (!$this->isFrontendRequest()) {
            return;
        }

        $address = $arguments->getEntity();
        if (!$address instanceof Address) {
            return;
        }

        $session = $this->dependencyProvider->getSession();
        $deleteVatId = $session->offsetGet(self::DELETE_VAT_ID_SESSION_FLAG);
        if ($deleteVatId === null) {
            return;
        }

        $session->offsetUnset(self::DELETE_VAT_ID_SESSION_FLAG);

        $address->setVatId(null);
    }

    private function isFrontendRequest(): bool
    {
        $frontController = $this->dependencyProvider->getFront();
        if (!$frontController instanceof \Enlight_Controller_Front) {
            return false;
        }

        $request = $frontController->Request();
        if ($request === null) {
            return false;
        }

        if ($request->getModuleName() === 'backend') {
            return false;
        }

        return true;
    }
}
