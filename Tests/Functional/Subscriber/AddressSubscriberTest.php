<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Tests\Functional\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Enlight_Components_Session_Namespace as ShopwareSession;
use PHPUnit\Framework\TestCase;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
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

        $this->setFrontendRequest();

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

        $this->setFrontendRequest();

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

        $this->setFrontendRequest();

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

        $this->setFrontendRequest();

        $this->getAddressSubscriber()->preUpdate($args);

        static::assertNull($address->getVatId());
        static::assertNull($session->offsetGet(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG));

        $session->offsetUnset(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG);
    }

    public function testPreUpdateWithBackendRequest(): void
    {
        $vatId = 'DE123456789';

        $customer = $this->getContainer()->get('models')->getRepository(Customer::class)->find(1);
        static::assertInstanceOf(Customer::class, $customer);

        $address = new Address();
        $address->setVatId($vatId);
        $address->setCustomer($customer);

        $args = new LifecycleEventArgs($address, $this->getContainer()->get('models'));

        $this->setBackendRequest();

        $this->getAddressSubscriber()->preUpdate($args);

        static::assertSame($vatId, $address->getVatId());
    }

    public function setFrontendRequest(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setModuleName('frontend');

        $this->getContainer()->get('front')->setRequest($request);
    }

    public function setBackendRequest(): void
    {
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setModuleName('backend');

        $this->getContainer()->get('front')->setRequest($request);
    }

    private function getAddressSubscriber(): AddressSubscriber
    {
        return new AddressSubscriber(
            $this->getContainer()->get('swag_vat_id_validation.dependency_provider')
        );
    }
}
