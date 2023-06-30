<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components;

use Shopware\Components\Plugin\CachedConfigReader;
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

    /**
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->coreService->configureOptions($resolver);
    }

    public function getBlockPrefix()
    {
        return $this->coreService->getBlockPrefix();
    }

    /**
     * @return void
     */
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
