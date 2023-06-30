<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Tests\Functional\Controller\Backend;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Shopware\Components\DependencyInjection\Container as ShopwareContainer;
use Shopware_Controllers_Backend_SwagVatIdValidation as VatIdValidationBackendController;
use SwagVatIdValidation\Tests\ContainerTrait;

require_once __DIR__ . '/../../../../Controllers/Backend/SwagVatIdValidation.php';

class SwagVatIdValidationTest extends TestCase
{
    use ContainerTrait;

    public function testValidateVatIdActionSuccessShouldBeTrue(): void
    {
        $controller = $this->getController();
        $controller->Request()->setParams($this->getValidAddressRequestData());

        $controller->validateVatIdAction();

        try {
            static::assertTrue($controller->View()->getAssign('success'));
        } catch (ExpectationFailedException $e) {
            static::assertArrayHasKey('serviceUnavailable', $controller->View()->getAssign('errors'));
            $this->markAsRisky();
        }
    }

    public function testValidateVatIdActionSuccessShouldBeFalse(): void
    {
        $controller = $this->getController();
        $addressData = $this->getValidAddressRequestData();
        $addressData['vatId'] = 'FooBarNumber';

        $controller->Request()->setParams($addressData);

        $controller->validateVatIdAction();

        try {
            static::assertFalse($controller->View()->getAssign('success'));
        } catch (ExpectationFailedException $e) {
            static::assertArrayHasKey('serviceUnavailable', $controller->View()->getAssign('errors'));
            $this->markAsRisky();
        }
    }

    public function testValidateVatIdActionSuccessShouldBeTrueWithNewAddress(): void
    {
        $controller = $this->getController();
        $addressData = $this->getValidAddressRequestData();
        $addressData['id'] = null;

        $controller->Request()->setParams($addressData);

        $controller->validateVatIdAction();

        try {
            static::assertTrue($controller->View()->getAssign('success'));
        } catch (ExpectationFailedException $e) {
            static::assertArrayHasKey('serviceUnavailable', $controller->View()->getAssign('errors'));
            $this->markAsRisky();
        }
    }

    private function getController(): VatIdValidationBackendController
    {
        $controller = new VatIdValidationBackendController();

        $container = $this->getContainer();
        static::assertInstanceOf(ShopwareContainer::class, $container);

        $controller->setContainer($container);
        $controller->setRequest(new \Enlight_Controller_Request_RequestTestCase());
        $controller->setResponse(new \Enlight_Controller_Response_ResponseTestCase());
        $controller->setView(new \Enlight_View_Default(new \Enlight_Template_Manager()));

        return $controller;
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidAddressRequestData(): array
    {
        return [
            'id' => 3,
            'defaultAddress' => null,
            'user_id' => 1,
            'company' => 'shopware AG',
            'department' => null,
            'vatId' => 'DE261679493',
            'salutation' => 'mr',
            'salutationSnippet' => null,
            'title' => null,
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'street' => 'Mustermannstraße 92',
            'zipcode' => '48624',
            'city' => 'Schöppingen',
            'additionalAddressLine1' => null,
            'additionalAddressLine2' => null,
            'countryId' => 2,
            'stateId' => null,
            'phone' => null,
        ];
    }
}
