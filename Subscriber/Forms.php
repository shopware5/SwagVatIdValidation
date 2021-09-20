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

namespace SwagVatIdValidation\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;
use Shopware\Bundle\AccountBundle\Form\Account\AddressFormType;
use Shopware_Controllers_Frontend_Register;
use SwagVatIdValidation\Bundle\AccountBundle\Constraints\AdvancedVatId;
use SwagVatIdValidation\Components\DependencyProvider;
use SwagVatIdValidation\Components\IsoServiceInterface;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;

class Forms implements SubscriberInterface
{
    /**
     * @var IsoServiceInterface
     */
    private $isoService;

    /**
     * @var VatIdConfigReaderInterface
     */
    private $configReader;

    /**
     * @var DependencyProvider
     */
    private $dependencyProvider;

    public function __construct(
        IsoServiceInterface $isoService,
        VatIdConfigReaderInterface $configReader,
        DependencyProvider $dependencyProvider
    ) {
        $this->isoService = $isoService;
        $this->configReader = $configReader;
        $this->dependencyProvider = $dependencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Form_Builder' => 'onFormBuild',
            'Enlight_Controller_Action_PreDispatch_Frontend_Register' => 'onRegister',
        ];
    }

    public function onFormBuild(EventArgs $args): void
    {
        $ref = $args->get('reference');
        if ($ref !== AddressFormType::class && $ref !== 'address') {
            return;
        }

        /** @var FormInterface $builder */
        $builder = $args->get('builder');

        $builder->add(
            'vatId',
            TextType::class,
            [
                'constraints' => [new AdvancedVatId()],
            ]
        );
    }

    public function onRegister(EventArgs $args): void
    {
        /** @var Shopware_Controllers_Frontend_Register $controller */
        $controller = $args->getSubject();

        $request = $controller->Request();

        if ($request->getActionName() !== 'index') {
            return;
        }

        $config = $this->configReader->getPluginConfig();

        $controller->View()->assign(
            'vatIdIsRequired',
            \json_encode($config[VatIdConfigReaderInterface::IS_VAT_ID_REQUIRED])
        );

        $controller->View()->assign(
            'countryIsoIdList',
            \json_encode(
                $this->isoService->getCountryIdsFromIsoList()
            )
        );
    }
}
