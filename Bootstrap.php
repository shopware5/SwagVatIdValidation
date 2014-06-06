<?php
/**
 * Shopware 4
 * Copyright © shopware AG
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

use Shopware\Plugins\SwagVatIdValidation\Components\Validators\VatIdValidatorInterface;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\DummyVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\SimpleBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\ExtendedBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\SimpleMiasVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\ExtendedMiasVatIdValidator;

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidationStatus;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;

use Shopware\CustomModels\SwagVatIdValidation\VatIdCheck;
use Shopware\Models\Customer\Billing;
use Shopware\Models\Mail\Mail;

/**
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage SwagVatIdValidation
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 */
class Shopware_Plugins_Core_SwagVatIdValidation_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /** @var  \Shopware\Models\Mail\Mail */
    private $mailRepository;

    /**
     * Helper function to get the MailRepository
     * @return \Shopware\Models\Mail\Repository
     */
    private function getMailRepository()
    {
        if (!$this->mailRepository) {
            $this->mailRepository = Shopware()->Models()->getRepository('\Shopware\Models\Mail\Mail');
        }

        return $this->mailRepository;
    }

    /**
     * Returns an array with the capabilities of the plugin.
     * @return array
     */
    public function getCapabilities()
    {
        return array(
            'install' => true,
            'enable' => true,
            'update' => true,
            'secureUninstall' => true
        );
    }

    /**
     * Returns the name of the plugin.
     * @return string
     */
    public function getLabel()
    {
        return 'VAT Validation';
    }

    /**
     * Returns the current version of the plugin.
     * @return string
     */
    public function getVersion()
    {
        return "1.0.0";
    }

    /**
     * Returns an array with some information about the plugin.
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'description' => file_get_contents($this->Path() . 'info_de.txt').file_get_contents($this->Path() . 'info_en.txt'),
        );
    }

    /**
     * Install function of the plugin bootstrap.
     *
     * Registers all necessary components and dependencies.
     *
     * @return bool
     */
    public function install()
    {
        $this->createMailTemplates();
        $this->createConfiguration();
        $this->registerEvents();

        return true;
    }

    public function uninstall()
    {
        $this->secureUninstall();

        return true;
    }

    public function secureUninstall()
    {
        $this->removeMailTemplate('VALIDATIONERROR');

        return true;
    }

    public function createMailTemplates()
    {
        //Template
        $content = "Hallo,\n\nbei der Überprüfung der USt-IdNr. {\$sVatId} der Firma\n\n{\$sCompany}\n{\$sStreet}\n{\$sZipCode} {\$sCity}\n\nist ein Fehler aufgetreten:\n\n{\$sError}\n\n{config name=shopName}";

        $mail = new Mail();
        $mail->setName('sSWAGVATIDVALIDATION_VALIDATIONERROR');
        $mail->setFromMail('');
        $mail->setFromName('');
        $mail->setSubject("Bei der Überprüfung der Ust-IdNr. {\$sVatId} ist ein Fehler aufgetreten");
        $mail->setContent($content);
        $mail->setMailtype(Mail::MAILTYPE_SYSTEM);

        Shopware()->Models()->persist($mail);
        Shopware()->Models()->flush();

        //Translation
        $localeRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');

        $translation = new \Shopware\Models\Translation\Translation();
        $translation->setLocale($localeRepository->findOneByLocale('en_GB'));
        $translation->setType('config_mails');
        $translation->setKey($mail->getId());
        $translation->setData(serialize(array('subject' => "", 'content' => "")));

        Shopware()->Models()->persist($translation);
        Shopware()->Models()->flush();
    }

    private function removeMailTemplate($name)
    {
        /** @var Mail $mail */
        $mail = $this->getMailRepository()->findOneByName('sSWAGVATIDVALIDATION_' . $name);

        $this->removeMailTranslations($mail->getId());

        Shopware()->Models()->remove($mail);
        Shopware()->Models()->flush($mail);
    }

    private function removeMailTranslations($mailTemplateId)
    {
        $translationRepository = Shopware()->Models()->getRepository('\Shopware\Models\Translation\Translation');
        $translations = $translationRepository->findByKey($mailTemplateId);

        /** @var \Shopware\Models\Translation\Translation $translation */
        foreach($translations as $translation) {
            if ($translation->getType() !== 'config_mails') {
                continue;
            }

            Shopware()->Models()->remove($translation);
            Shopware()->Models()->flush($translation);
        }
    }

    /**
     * Creates the configuration fields.
     * Selects first a row of the s_articles_attributes to get all possible article attributes.
     */
    private function createConfiguration()
    {
        $form = $this->Form();

        $form->setElement(
            'text',
            'vatId',
            array(
                'label' => 'Eigene USt-IdNr.',
                'value' => Shopware()->Config()->get('sTAXNUMBER'),
                'description' => 'Eigene USt-IdNr., die zur Prüfung verwendet werden soll.',
                'required' => true
            )
        );

        $form->setElement(
            'text',
            'shopEmailNotification',
            array(
                'label' => 'E-Mail-Benachrichtigung',
                'value' => Shopware()->Config()->get('sMAIL'),
                'description' => 'An diese E-Mail-Adresse erhalten Sie eine Mitteilungen, wenn die Ust-IdNr. eines Bestandskunden abgelaufen ist. Wenn leer, erhalten Sie keine E-Mail.'
            )
        );

        $form->setElement(
            'checkbox',
            'vatIdRequired',
            array(
                'label' => 'Ust-IdNr.-Angabe ist Pflicht',
                'value' => false,
                'description' => 'Wandelt das Feld für die Ust-IdNr. in ein Pflichtfeld um.'
            )
        );

        $form->setElement(
            'checkbox',
            'extendedCheck',
            array(
                'label' => 'Erweiterte Prüfung durchführen',
                'value' => false,
                'description' => 'Qualifizierte Bestätigungsanfragen können nur von deutschen USt-IdNrn. für ausländische USt-IdNrn. gestellt werden. Sofern der angefragte EU-Mitgliedsstaat die Adressdaten bereit stellt, werden diese anderenfalls manuell durch das Plugin verglichen.'
            )
        );

        $form->setElement(
            'checkbox',
            'confirmation',
            array(
                'label' => 'Amtliche Bestätigungsmitteilung',
                'value' => false,
                'description' => 'Amtliche Bestätigungsmitteilung bei qualifizierten Bestätigungsanfragen anfordern. Qualifizierte Bestätigungsanfragen können nur von deutschen USt-IdNrn. für ausländische USt-IdNrn. gestellt werden.'
            )
        );

        $this->addFormTranslations(
            array(
                'en_GB' => array(
                    'vatId' => array(
                        'label' => 'Own VAT ID',
                        'description' => 'Your own VAT ID number which is required for validation. During the validation process, your VAT ID is never given to your customers.'
                    ),
                    'shopEmailNotification' => array(
                        'label' => 'Own email notifications',
                        'description' => 'If provided, you will receive an email when a VAT ID validation error occurs.'
                    ),
                    'vatIdRequired' => array(
                        'label' => 'VAT ID is required',
                        'description' => 'VAT ID is required'
                    ),
                    'extendedCheck' => array(
                        'label' => 'Extended checks',
                        'description' => 'If enabled, this plugin will compare the address provided by the customer with the data available in the remote VAT ID validation service. Note: depending on the market of both you and your customer, the completeness of the available information for comparison may be limited.'
                    ),
                    'confirmation' => array(
                        'label' => 'Official mail confirmation',
                        'description' => 'Only available for German-based shops. Requests an official mail confirmation for qualified checks of foreign VAT IDs.'
                    )
                )
            )
        );
    }

    private function registerEvents()
    {
        // The SubscriberInterface is available in SW 4.1.4 and later
        if (!$this->assertVersionGreaterThen('4.1.4')) {
            throw new \RuntimeException('At least Shopware 4.1.4 is required');
        }

        // Register an early event for our event subscribers
        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );

        return;
    }

    /**
     * This callback function is triggered at the very beginning of the dispatch process and allows
     * us to register additional events on the fly. This way you won't ever need to reinstall you
     * plugin for new events - any event and hook can simply be registerend in the event subscribers
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $config = $this->Config();
        $path = $this->Path();
        $action = $args->getRequest()->getParam('action');


        $subscribers = array(
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\Account($config, $path),
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\Checkout($config, $path),
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\Registration($config, $path),
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\Login($config, $action),
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\Register($config, $action),
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\Update($config, $action)
        );

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }

    /**
     * Registers the plugin's namespace.
     */
    public function afterInit()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware\Plugins\SwagVatIdValidation',
            $this->Path()
        );
    }
}