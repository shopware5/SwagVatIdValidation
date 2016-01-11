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
use Shopware\Plugins\SwagVatIdValidation\Components\APIValidationType;
use Shopware\Plugins\SwagVatIdValidation\Subscriber;

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
     *
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
     *
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
     *
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
     *
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
     *
     * @return string
     */
    public function getLabel()
    {
        return 'VAT Validation';
    }

    /**
     * Returns the current version of the plugin.
     *
     * @return string
     * @throws Exception
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * Returns an array with some information about the plugin.
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'description' => file_get_contents($this->Path() . 'info_de.txt') . file_get_contents($this->Path() . 'info_en.txt'),
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

        $this->registerController('frontend', "SwagVatIdValidation");

        return true;
    }

    /**
     * Standard plugin enable method
     *
     * @return array
     */
    public function enable()
    {
        return array('success' => true, 'invalidateCache' => $this->getInvalidateCacheArray());
    }

    /**
     * Standard plugin disable method
     *
     * @return array
     */
    public function disable()
    {
        return array('success' => true, 'invalidateCache' => $this->getInvalidateCacheArray());
    }

    /**
     * Handles the updates
     *
     * @param string $oldVersion
     * @return bool
     */
    public function update($oldVersion)
    {
        if (version_compare($oldVersion, '1.0.7', '<')) {
            $form = $this->Form();
            $form->removeElement('extendedCheck');
        }

        return $this->install();
    }

    /**
     * (Insecure) uninstall method, removes also the user-defined data (like the maybe changed mail template)
     *
     * @return bool
     */
    public function uninstall()
    {
        //do NOT remove the mail template on secureUninstall
        $this->removeMailTemplate();

        return array('success' => true, 'invalidateCache' => $this->getInvalidateCacheArray());
    }

    /**
     * Helper function to create the mail template
     */
    private function createMailTemplate()
    {
        //check, if mail template already exists (because secureUninstall and update)
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

        if ($this->assertMinimumVersion("5.1.0")) {
            $shop = $this->getShopByLocale("en_GB");

            if (!$shop) {
                return;
            }

            $translation->setShop($shop);
        } else {
            $translation->setLocale($this->getLocaleRepository()->findOneByLocale('en_GB'));
        }

        $translation->setType('config_mails');
        $translation->setKey($mail->getId());
        $translation->setData(
            serialize(
                array(
                    'subject' => "An error occurred when validating VAT ID {\$sVatId}.",
                    'content' => "Hello,\n\nAn error occurred during the validation of VAT ID {\$sVatId} associated with the following company:\n\n{\$sCompany}\n{\$sStreet}\n{\$sZipCode} {\$sCity}\n\nThe following errors were detected:\n\n{\$sError}\n\n{config name=shopName}"
                )
            )
        );

        Shopware()->Models()->persist($translation);
        Shopware()->Models()->flush();
    }

    /**
     * Helper method that returns the correct shop for the specified locale.
     *
     * @deprecated for shopware 5.2+
     * @param string $locale
     * @return Shopware\Models\Shop\Shop $result
     */
    private function getShopByLocale($locale)
    {
        return Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')
            ->findOneByLocale($this->getLocaleRepository()->findOneByLocale($locale));
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
     *
     * @param $mailTemplateId
     */
    private function removeMailTranslations($mailTemplateId)
    {
        $translations = $this->getTranslationRepository()->findByKey($mailTemplateId);

        /** @var Translation $translation */
        foreach ($translations as $translation) {
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
                'required' => true,
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement(
            'select',
            'shopEmailNotification',
            array(
                'label' => 'E-Mail-Benachrichtigung',
                'store' => array(
                    array(0, array('de_DE' => 'Nein', 'en_GB' => 'No')),
                    array(1, array('de_DE' => 'Shopbetreiber-E-Mail-Adresse', 'en_GB' => 'Shop owner email address'))
                ),
                'value' => 1,
                'description' => 'An diese E-Mail-Adresse erhalten Sie eine Mitteilungen, wenn die Ust-IdNr. eines Bestandskunden ungültig ist.<br>
                                  1. <u>Nein</u>: Es wird keine E-Mail versendet.<br>
                                  2. <u>Shopbetreiber-E-Mail-Adresse</u>: Es wird die E-Mail-Adresse aus den Stammdaten genutzt.<br>
                                     <u>Hinweis:</u> Sie können auch eine individuelle E-Mail-Adresse hinterlegen.',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement(
            'checkbox',
            'vatIdRequired',
            array(
                'label' => 'Ust-IdNr.-Angabe ist Pflicht',
                'value' => false,
                'description' => 'Wandelt das Feld für die Ust-IdNr. für EU-Länder in ein Pflichtfeld um. Ausnahmen können unten angegeben werden.',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement(
            'combo',
            'apiValidationType',
            array(
                'label' => 'Art der API-Überprüfung',
                'value' => APIValidationType::SIMPLE,
                'store' => array(
                    array(APIValidationType::NONE, "Keine (None)"),
                    array(APIValidationType::SIMPLE, 'Einfach (Simple)'),
                    array(APIValidationType::EXTENDED, 'Erweitert (Extended)')
                ),
                'description' => '1. <u>Keine</u>: Es wird keine API-Überprüfung durchgeführt.<br>
                                  2. <u>Einfach</u>: Es wird überprüft, ob diese Ust-IdNr. existiert.<br>
                                  3. <u>Erweitert</u>: Es wird überprüft, ob diese Ust-IdNr. existiert und zur Adresse passt.
                                     <u>Hinweis:</u> Erweiterte Bestätigungsanfragen können nur von deutschen USt-IdNrn. für ausländische USt-IdNrn. gestellt werden. Sofern der angefragte EU-Mitgliedsstaat die Adressdaten bereit stellt, werden diese anderenfalls manuell durch das Plugin verglichen.',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement(
            'checkbox',
            'confirmation',
            array(
                'label' => 'Amtliche Bestätigungsmitteilung',
                'value' => false,
                'description' => 'Amtliche Bestätigungsmitteilung bei qualifizierten Bestätigungsanfragen anfordern. Qualifizierte Bestätigungsanfragen können nur von deutschen USt-IdNrn. für ausländische USt-IdNrn. gestellt werden.',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        $form->setElement(
            'select',
            'disabledCountryISOs',
            array(
                'label' => 'Ausnahmen der Pflichtangabe der Ust-IdNr.',
                'store' => array(
                    array('AT', array('de_DE' => 'Österreich', 'en_GB' => 'Austria')), //Republic of Austria
                    array('BE', array('de_DE' => 'Belgien', 'en_GB' => 'Belgium')), //Kingdom of Belgium
                    array('BG', array('de_DE' => 'Bulgarien', 'en_GB' => 'Bulgaria')), //Republic of Bulgaria
                    array('CY', array('de_DE' => 'Zypern', 'en_GB' => 'Cyprus')), //Republic of Cyprus
                    array('CZ', array('de_DE' => 'Tschechien', 'en_GB' => 'Czechia')), //Czech Republic
                    array('DE', array('de_DE' => 'Deutschland', 'en_GB' => 'Germany')), //Federal Republic of Germany
                    array('DK', array('de_DE' => 'Dänemark', 'en_GB' => 'Denmark')), //Kingdom of Denmark
                    array('EE', array('de_DE' => 'Estland', 'en_GB' => 'Estonia')), //Republic of Estonia
                    array('EL', array('de_DE' => 'Griechenland', 'en_GB' => 'Greece')), //Hellenic Republic (Greece)
                    array('ES', array('de_DE' => 'Spanien', 'en_GB' => 'Spain')), //Kingdom of Spain
                    array('FI', array('de_DE' => 'Finnland', 'en_GB' => 'Spain')), //Republic of Finland
                    array('FR', array('de_DE' => 'Frankreich', 'en_GB' => 'France')), //French Republic
                    array('GB', array('de_DE' => 'Großbritannien', 'en_GB' => 'Great Britain')), //United Kingdom of Great Britain and Northern Ireland
                    array('HR', array('de_DE' => 'Kroatien', 'en_GB' => 'Croatia')), //Republic of Croatia
                    array('HU', array('de_DE' => 'Ungarn', 'en_GB' => 'Hungary')), //Hungary
                    array('IE', array('de_DE' => 'Irland', 'en_GB' => 'Ireland')), //Ireland
                    array('IT', array('de_DE' => 'Italien', 'en_GB' => 'Italy')), //Italian Republic
                    array('LT', array('de_DE' => 'Litauen', 'en_GB' => 'Lithuania')), //Republic of Lithuania
                    array('LU', array('de_DE' => 'Luxemburg', 'en_GB' => 'Luxembourg')), //Grand Duchy of Luxembourg
                    array('LV', array('de_DE' => 'Lettland', 'en_GB' => 'Latvia')), //Republic of Latvia
                    array('MT', array('de_DE' => 'Malta', 'en_GB' => 'Malta')), //Republic of Malta
                    array('NL', array('de_DE' => 'Niederlande', 'en_GB' => 'Netherlands')), //Kingdom of the Netherlands
                    array('PL', array('de_DE' => 'Polen', 'en_GB' => 'Poland')), //Republic of Poland
                    array('PT', array('de_DE' => 'Portugal', 'en_GB' => 'Portugal')), //Portuguese Republic
                    array('RO', array('de_DE' => 'Rumänien', 'en_GB' => 'Romania')), //Romania
                    array('SE', array('de_DE' => 'Schweden', 'en_GB' => 'Sweden')), //Kingdom of Sweden
                    array('SI', array('de_DE' => 'Slowenien', 'en_GB' => 'Slovenia')), //Republic of Slovenia
                    array('SK', array('de_DE' => 'Slowakei', 'en_GB' => 'Slovakia')), //Slovak Republic
                ),
                'multiSelect' => true,
                'value' => '',
                'description' => 'Hier können Sie ISO Codes von EU-Ländern eintragen, die eine Ausnahme in Bezug auf die Einstellung "Ust-IdNr.-Angabe ist Pflicht" bilden. Beispiele sind z.B. DE, GB oder AT, Angabe mehrer Länder mit Komma getrennt möglich.',
                'scope' => Shopware\Models\Config\Element::SCOPE_SHOP
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
                        'description' => 'If provided, you will receive an email when a VAT ID validation error occurs.<br>
                                          1. <u>No</u>: You won\'t receive an email.<br>
                                          2. <u>Shopowner email address</u>: The email address of the basic information will be used.<br>
                                             <u>Information:</u> You also can enter an individual email address.'
                    ),
                    'vatIdRequired' => array(
                        'label' => 'VAT ID is required',
                        'description' => 'If enabled, the input of a VAT ID is required for EU countries. Below you can define exceptions for that.'
                    ),
                    'apiValidationType' => array(
                        'label' => 'Type of API validation',
                        'description' => '1. <u>None</u>: No API validation process will be executed.<br>
                                          2. <u>Simple</u>: It will be checked if the VAT ID exists in general.<br>
                                          3. <u>Extended</u>: It will be checked, if the VAT ID exists in general and if it matches the customers address.
                                             <u>Information:</u> The extended check will compare the address provided by the customer with the data available in the remote VAT ID validation service. Note: depending on the market of both you and your customer, the completeness of the available information for comparison may be limited.'
                    ),
                    'confirmation' => array(
                        'label' => 'Official mail confirmation',
                        'description' => 'Only available for German-based shops. Requests an official mail confirmation for qualified checks of foreign VAT IDs.'
                    ),
                    'disabledCountryISOs' => array(
                        'label' => 'Exceptions for the requirement of the VAT ID',
                        'description' => 'The country ISO codes you enter here will be excluded from the VAT id requirement if enabled above. Examples are DE, GB or AT. Multiple countries are possible separated by comma'
                    ),
                )
            )
        );
    }

    /**
     * Helper function to register an early event for our event subscribers
     *
     * @throws RuntimeException
     */
    private function registerEvents()
    {
        // The SubscriberInterface is available in SW 4.2.2 and later
        if (!$this->assertVersionGreaterThen('4.2.2')) {
            throw new \RuntimeException('At least Shopware 4.2.2 is required');
        }

        // Register an early event for our event subscribers
        $this->subscribeEvent('Enlight_Controller_Front_DispatchLoopStartup', 'onStartDispatch');

        return;
    }

    /**
     * This callback function is triggered at the very beginning of the dispatch process and allows
     * us to register additional events on the fly. This way you won't ever need to reinstall you
     * plugin for new events - any event and hook can simply be registered in the event subscribers
     *
     * @param Enlight_Event_EventArgs $args
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
            new Subscriber\TemplateExtension($config, $path, $session, $snippets, $shop),
            new Subscriber\Login($action, $config, $snippets, $session, $models, $mailer),
            new Subscriber\SaveBilling($config, $snippets, $models, $mailer),
            new Subscriber\CheckoutFinish($config, $snippets, $models)
        );

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }

    /**
     * Helper method to return all the caches, that need to be cleared after installing/uninstalling/enabling/disabling a plugin
     *
     * @return array
     */
    private function getInvalidateCacheArray()
    {
        return array('frontend', 'theme');
    }

    /**
     * Registers the plugin's namespace.
     */
    public function afterInit()
    {
        $this->Application()->Loader()->registerNamespace('Shopware\Plugins\SwagVatIdValidation', $this->Path());
    }
}
