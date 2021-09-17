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

namespace SwagVatIdValidation\Tests\Functional\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Enlight_Components_Session_Namespace as ShopwareSession;
use PHPUnit\Framework\TestCase;
use Shopware\Models\Customer\Address;
use SwagVatIdValidation\Components\DependencyProvider;
use SwagVatIdValidation\Subscriber\AddressSubscriber;
use SwagVatIdValidation\Tests\ContainerTrait;

class AddressSubscriberTest extends TestCase
{
    use ContainerTrait;

    public function testPrePersist(): void
    {
        $vatId = 'DE123456789';

        $address = new Address();
        $address->setVatId($vatId);

        $args = new LifecycleEventArgs($address, $this->getContainer()->get('models'));

        $this->getAddressSubscriber()->prePersist($args);

        static::assertSame($vatId, $address->getVatId());
    }

    public function testPrePersistShouldRemoveVatId(): void
    {
        $session = $this->getContainer()->get('session');
        static::assertInstanceOf(ShopwareSession::class, $session);
        $session->offsetSet(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG, true);

        $vatId = 'DE123456789';

        $address = new Address();
        $address->setVatId($vatId);

        $args = new LifecycleEventArgs($address, $this->getContainer()->get('models'));

        $this->getAddressSubscriber()->prePersist($args);

        static::assertNull($address->getVatId());
        static::assertNull($session->offsetGet(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG));

        $session->offsetUnset(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG);
    }

    public function testPreUpdate(): void
    {
        $vatId = 'DE123456789';

        $address = new Address();
        $address->setVatId($vatId);

        $args = new LifecycleEventArgs($address, $this->getContainer()->get('models'));

        $this->getAddressSubscriber()->preUpdate($args);

        static::assertSame($vatId, $address->getVatId());
    }

    public function testPreUpdateShouldRemoveVatId(): void
    {
        $session = $this->getContainer()->get('session');
        static::assertInstanceOf(ShopwareSession::class, $session);
        $session->offsetSet(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG, true);

        $vatId = 'DE123456789';

        $address = new Address();
        $address->setVatId($vatId);

        $args = new LifecycleEventArgs($address, $this->getContainer()->get('models'));

        $this->getAddressSubscriber()->preUpdate($args);

        static::assertNull($address->getVatId());
        static::assertNull($session->offsetGet(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG));

        $session->offsetUnset(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG);
    }

    private function getAddressSubscriber(): AddressSubscriber
    {
        $dependencyProvider = $this->getContainer()->get('swag_vat_id_validation.dependency_provider');
        static::assertInstanceOf(DependencyProvider::class, $dependencyProvider);

        return new AddressSubscriber($dependencyProvider);
    }
}
