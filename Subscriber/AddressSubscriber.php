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
