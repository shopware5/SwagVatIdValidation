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

        static::assertTrue($controller->View()->getAssign('success'));
    }

    public function testValidateVatIdActionSuccessShouldBeFalse(): void
    {
        $controller = $this->getController();
        $addressData = $this->getValidAddressRequestData();
        $addressData['vatId'] = 'FooBarNumber';

        $controller->Request()->setParams($addressData);

        $controller->validateVatIdAction();

        static::assertFalse($controller->View()->getAssign('success'));
    }

    public function testValidateVatIdActionSuccessShouldBeTrueWithNewAddress(): void
    {
        $controller = $this->getController();
        $addressData = $this->getValidAddressRequestData();
        $addressData['id'] = null;

        $controller->Request()->setParams($addressData);

        $controller->validateVatIdAction();

        static::assertTrue($controller->View()->getAssign('success'));
    }

    private function getController(): VatIdValidationBackendController
    {
        $controller = new VatIdValidationBackendController();

        $container = $this->getContainer();
        static::assertInstanceOf(ShopwareContainer::class, $container);

        $controller->setContainer($container);
        $controller->setRequest(new Enlight_Controller_Request_RequestTestCase());
        $controller->setResponse(new Enlight_Controller_Response_ResponseTestCase());
        $controller->setView(new Enlight_View_Default(new Enlight_Template_Manager()));

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
