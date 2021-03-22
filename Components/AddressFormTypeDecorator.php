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
        if ($config['vatId_is_required'] === false) {
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
