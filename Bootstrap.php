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

use Shopware\Models\Mail\Mail;
use Shopware\Models\Translation\Translation;

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

    /** @var  \Shopware\Models\Shop\Locale */
    private $localeRepository;

    /** @var  \Shopware\Models\Translation\Translation */
    private $translationRepository;

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
     * Helper function to get the LocaleRepository
     * @return \Shopware\Models\Shop\Locale
     */
    private function getLocaleRepository()
    {
        if (!$this->localeRepository) {
            $this->localeRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
        }

        return $this->localeRepository;
    }

    /**
     * Helper function to get the TranslationRepository
     * @return \Shopware\Models\Translation\Translation
     */
    private function getTranslationRepository()
    {
        if (!$this->translationRepository) {
            $this->translationRepository = Shopware()->Models()->getRepository('\Shopware\Models\Translation\Translation');
        }

        return $this->translationRepository;
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
	 * @throws Exception
     */
    public function getVersion()
    {
		$info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

		if($info) {
			return $info['currentVersion'];
		} else {
			throw new Exception('The plugin has an invalid version file.');
        }
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
        $this->createMailTemplate();
        $this->createConfiguration();
        $this->registerEvents();

        return true;
    }

    /**
     * (Unsecure) uninstall method, removes also the user-defined data (like the maybe changed mail template)
     * @return bool
     */
    public function uninstall()
    {
        //do NOT remove the mail template on secureUninstall
        $this->removeMailTemplate();

        return true;
    }

    /**
     * Helper function to create the mail template
     */
    private function createMailTemplate()
    {
        //check, if mail template already exists (because secureUninstall)
        $mail = $this->getMailRepository()->findOneByName('sSWAGVATIDVALIDATION_VALIDATIONERROR');

        if ($mail) {
            return;
        }

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
        $translation = new Translation();

        if($this->assertMinimumVersion("5.1.0")) {
            $translation->setShop($this->getShopByLocale("en_GB"));
        }
        else {
            $translation->setLocale($this->getLocaleRepository()->findOneByLocale('en_GB'));
        }

        $translation->setType('config_mails');
        $translation->setKey($mail->getId());
        $translation->setData(serialize(array(
            'subject' => "An error occurred when validating VAT ID {\$sVatId}.",
            'content' => "Hello,\n\nAn error occurred during the validation of VAT ID {\$sVatId} associated with the following company:\n\n{\$sCompany}\n{\$sStreet}\n{\$sZipCode} {\$sCity}\n\nThe following errors were detected:\n\n{\$sError}\n\n{config name=shopName}"
        )));

        Shopware()->Models()->persist($translation);
        Shopware()->Models()->flush();
    }

    /**
     * Helper method that returns the correct shop for the specified locale.
     * @deprecated for shopware 5.2+
     * @param string $locale
     * @return Shopware\Models\Shop\Shop $result
     */
    private function getShopByLocale($locale)
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findOneByLocale($this->getLocaleRepository()->findOneByLocale($locale));
    }

    /**
     * Helper function to remove the mail template
     */
    private function removeMailTemplate()
    {
        /** @var Mail $mail */
        $mail = $this->getMailRepository()->findOneByName('sSWAGVATIDVALIDATION_VALIDATIONERROR');

        $this->removeMailTranslations($mail->getId());

        Shopware()->Models()->remove($mail);
        Shopware()->Models()->flush($mail);
    }

    /**
     * Helper function to remove the translations of the mail template
     * @param $mailTemplateId
     */
    private function removeMailTranslations($mailTemplateId)
    {
        $translations = $this->getTranslationRepository()->findByKey($mailTemplateId);

        /** @var Translation $translation */
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
                        'description' => 'If enabled, the input of a VAT ID is required for business customers.'
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

    /**
     * Helper function to register an early event for our event subscribers
     * @throws RuntimeException
     */
    private function registerEvents()
    {
        // The SubscriberInterface is available in SW 4.2.2 and later
        if (!$this->assertVersionGreaterThen('4.2.2')) {
            throw new \RuntimeException('At least Shopware 4.2.2 is required');
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
     * plugin for new events - any event and hook can simply be registered in the event subscribers
     */
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        //Only subscribe when we are in the frontend
        $module = $args->getRequest()->getParam('module');

        if (!in_array($module, array('', 'frontend'))) {
            return;
        }

        $config = $this->Config();
        $path = $this->Path();
        $snippets = Shopware()->Snippets();
        $models = Shopware()->Models();
        $mailer = Shopware()->TemplateMail();
        $session = Shopware()->Session();
        $action = $args->getRequest()->getActionName();
        $shop = Shopware()->Shop();

        $subscribers = array(
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\TemplateExtension($config, $path, $session, $snippets, $shop),
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\Login($config, $snippets, $models, $mailer, $session, $action),
            new \Shopware\Plugins\SwagVatIdValidation\Subscriber\SaveBilling($config, $snippets)
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