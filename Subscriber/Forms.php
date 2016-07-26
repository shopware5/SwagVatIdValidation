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
use Enlight_Event_EventArgs as EventArgs;
use Shopware\Bundle\AccountBundle\Form\Account\AddressFormType;
use Shopware\Plugins\SwagVatIdValidation\Bundle\AccountBundle\Constraints\AdvancedVatId;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;

/**
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class Forms implements SubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Form_Builder' => 'onFormBuild'
        ];
    }

    /**
     * @param EventArgs $args
     */
    public function onFormBuild(EventArgs $args)
    {
        if ($args->get('reference') !== AddressFormType::class) {
            return;
        }

        /** @var FormInterface $builder */
        $builder = $args->get('builder');

        $builder->add('vatId', TextType::class, [
            'constraints' => [new AdvancedVatId()]
        ]);
    }
}
