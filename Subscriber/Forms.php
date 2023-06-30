<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;
use Shopware\Bundle\AccountBundle\Form\Account\AddressFormType;
use SwagVatIdValidation\Bundle\AccountBundle\Constraints\AdvancedVatId;
use SwagVatIdValidation\Components\IsoServiceInterface;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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

    public function __construct(
        IsoServiceInterface $isoService,
        VatIdConfigReaderInterface $configReader
    ) {
        $this->isoService = $isoService;
        $this->configReader = $configReader;
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
        $controller = $args->get('subject');

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
