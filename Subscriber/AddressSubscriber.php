<?php
declare(strict_types=1);
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
}
