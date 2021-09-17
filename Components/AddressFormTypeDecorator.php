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

namespace SwagVatIdValidation\Components;

use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddressFormTypeDecorator extends AbstractType
{
    /**
     * @var AbstractType
     */
    private $coreService;

    /**
     * @var CachedConfigReader
     */
    private $configReader;

    /**
     * @var IsoServiceInterface
     */
    private $isoService;

    public function __construct(AbstractType $coreService, CachedConfigReader $configReader, IsoServiceInterface $isoService)
    {
        $this->coreService = $coreService;
        $this->configReader = $configReader;
        $this->isoService = $isoService;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $this->coreService->configureOptions($resolver);
    }

    public function getBlockPrefix()
    {
        return $this->coreService->getBlockPrefix();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->coreService->buildForm($builder, $options);

        $config = $this->configReader->getByPluginName('SwagVatIdValidation');
        if ($config[VatIdConfigReaderInterface::IS_VAT_ID_REQUIRED] === false) {
            return;
        }

        $this->addVatIdValidation($builder);
    }

    private function addVatIdValidation(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var Address $data */
            $data = $form->getData();

            $customerType = $form->get('additional')->get('customer_type')->getData();

            if ($customerType !== Customer::CUSTOMER_TYPE_BUSINESS || !empty($data->getVatId())) {
                return;
            }

            if (!\in_array($data->getCountry()->getIso(), $this->isoService->getCountriesIsoList(), true)) {
                return;
            }

            $notBlank = new NotBlank(['message' => null]);
            $error = new FormError($notBlank->message);
            $error->setOrigin($form->get('vatId'));
            $form->addError($error);
        });
    }
}
