<?php

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

namespace Shopware\Plugins\SwagVatIdValidation\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Plugins\SwagVatIdValidation\Bundle\AccountBundle\Service\Validator\AddressValidatorDecorator;
use Shopware\Plugins\SwagVatIdValidation\Components\ValidationService;

/**
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class Services implements SubscriberInterface
{
    /**
     * @var Container $container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_InitResource_vat_id.validation_service' => 'onInitValidationService',
            'Enlight_Bootstrap_AfterInitResource_shopware_account.address_validator' => 'onDecorateAddressValidator'
        ];
    }

    /**
     * @return ValidationService
     */
    public function onInitValidationService()
    {
        return new ValidationService(
            $this->container->get('config'),
            $this->container->get('snippets'),
            $this->container->get('models'),
            $this->container->get('templatemail')
        );
    }

    /**
     * decorates the core address validator to extends the check for vat id
     */
    public function onDecorateAddressValidator()
    {
        $addressValidator = $this->container->get('shopware_account.address_validator');

        $vatIdValidator = new AddressValidatorDecorator(
            $addressValidator,
            $this->container->get('config'),
            $this->container->get('vat_id.validation_service'),
            $this->container->get('validator')
        );

        $this->container->set('shopware_account.address_validator', $vatIdValidator);
    }
}
