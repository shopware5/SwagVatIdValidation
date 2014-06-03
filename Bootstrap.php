<?php

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorInterface;
use Shopware\Plugins\SwagVatIdValidation\Components\DummyVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\SimpleBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\ExtendedBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\SimpleMiasVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\ExtendedMiasVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidationStatus;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;

use Shopware\CustomModels\SwagVatIdValidation\VatIdCheck;
use Shopware\Models\Customer\Billing;
use Shopware\Models\Mail\Mail;

/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
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
 *
 * @category   Shopware
 * @package   Shopware_Plugins
 * @subpackage SwagVatIdValidation
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 */
class Shopware_Plugins_Core_SwagVatIdValidation_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /** @var  \Shopware\Models\Mail\Mail */
    private $mailRepository;

    /** @var  \Shopware\Models\Customer\BillingRepository */
    private $billingRepository;

    /** @var \Shopware\CustomModels\SwagVatIdValidation\Repository */
    private $vatIdCheckRepository;

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
     * Helper function to get the BillingRepository
     * @return \Shopware\Models\Customer\BillingRepository
     */
    private function getBillingRepository()
    {
        if (!$this->billingRepository) {
            $this->billingRepository = Shopware()->Models()->getRepository('\Shopware\Models\Customer\Billing');
        }

        return $this->billingRepository;
    }

    /**
     * Helper function to get the VatIdCheckRepository
     * @return \Shopware\CustomModels\SwagVatIdValidation\Repository
     */
    private function getVatIdCheckRepository()
    {
        if (!$this->vatIdCheckRepository) {
            $this->vatIdCheckRepository = Shopware()->Models()->getRepository('\Shopware\CustomModels\SwagVatIdValidation\VatIdCheck');
        }

        return $this->vatIdCheckRepository;
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
        return 'Ust-IdNr.-Prüfung';
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
            'description' => file_get_contents(__DIR__ . '/info.txt'),
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
        $this->createDatabaseTables();
        $this->createMailTemplates();
        $this->registerCronJobs();
        $this->createConfiguration();
        $this->registerEvents();

        return true;
    }

    public function uninstall()
    {
        $this->secureUninstall();

        $this->removeDatabaseTables();

        return true;
    }

    public function secureUninstall()
    {
        $this->removeMailTemplate('CUSTOMERINFORMATION');
        $this->removeMailTemplate('CRONJOBSUMMARY');
        $this->removeMailTemplate('VALIDATIONERROR');

        return true;
    }

    private function createDatabaseTables()
    {
        $this->registerCustomModels();

        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);

        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\SwagVatIdValidation\VatIdCheck')
        );

        try {
            $tool->createSchema($classes);
        } catch (\Doctrine\ORM\Tools\ToolsException $e) {
            // ignore
        }
    }

    public function removeDatabaseTables()
    {
        $this->registerCustomModels();

        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);

        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\SwagVatIdValidation\VatIdCheck')
        );

        $tool->dropSchema($classes);
    }

    public function createMailTemplates()
    {
        $content = "Hallo,\n\nIhre USt-IdNr. konnte soeben erfolgreich geprüft werden.\n\n{if \$sValid}Sie ist gültig. Sie können nun mehrwertsteuerfrei einkaufen.{else}Es wurden Fehler erkannt. Bitte kontrollieren Sie nochmal Ihre Eingaben.{/if}\n\nViele Grüße,\n\nIhr Team von {config name=shopName}";
        $this->createMailTemplate('CUSTOMERINFORMATION', 'Ihre USt-IdNr. wurde geprüft', $content);

        $content = "Hallo,\n\nes wurden gerade {\$sAmount} USt-IdNrn. auf Ihre Gültigkeit geprüft. Davon waren {\$sInvalid} ungültig.\n\n{config name=shopName}";
        $this->createMailTemplate('CRONJOBSUMMARY', 'Zusammenfassung der durchgeführten USt-IdNr.-Prüfungen', $content);

        $content = "Hallo,\n\nes gab einen Fehler bei der Prüfung der USt-IdNr. {\$sVatId}:\n\n{\$sError}\n\n{config name=shopName}";
        $this->createMailTemplate('VALIDATIONERROR', 'Bei einer USt-IdNr.-Prüfung ist ein Fehler aufgetreten.', $content);
    }

    private function createMailTemplate($name, $subject, $content)
    {
        $mail = new Mail();
        $mail->setName('sSWAGVATIDVALIDATION_' . $name);
        $mail->setFromMail('');
        $mail->setFromName('');
        $mail->setSubject($subject);
        $mail->setContent($content);
        $mail->setMailtype(Mail::MAILTYPE_SYSTEM);

        Shopware()->Models()->persist($mail);
        Shopware()->Models()->flush();
    }

    private function sendMailByTemplate($name, $to, $context)
    {
        $mail = Shopware()->TemplateMail()->createMail('sSWAGVATIDVALIDATION_' . $name, $context);
        $mail->addTo($to);
        $mail->send();
    }

    private function removeMailTemplate($name)
    {
        $mail = $this->getMailRepository()->findOneByName('sSWAGVATIDVALIDATION_' . $name);
        Shopware()->Models()->remove($mail);
        Shopware()->Models()->flush($mail);
    }

    private function registerCronJobs()
    {
        $this->createCronJob(
            'SwagCheckVatIds',
            'SwagVatIdValidationCron',
            3600,
            true
        );

        $this->subscribeEvent(
            'Shopware_CronJob_SwagVatIdValidationCron',
            'onRunSwagVatIdValidationCronJob'
        );
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
                'label' => 'Eigene E-Mail-Benachrichtigungen',
                'value' => Shopware()->Config()->get('sMAIL'),
                'description' => 'An diese E-Mail-Adresse erhalten Sie Cronjob-Zusammenfassungen sowie Fehler-Mitteilungen. Wenn leer, werden keine E-Mails versandt.'
            )
        );

        $form->setElement(
            'checkbox',
            'customerEmailNotification',
            array(
                'label' => 'Kunden-Benachrichtigung per E-Mail',
                'value' => false,
                'description' => 'Sendet dem Kunden das Ergebnis der Prüfung zu, wenn diese nicht sofort durchgeführt werden konnte.'
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
                        'description' => 'If provided, you will receive an email when a VAT ID validation error occurs. You also will receive a regular summary of these errors.'
                    ),
                    'customerEmailNotification' => array(
                        'label' => 'Customer email notifications',
                        'description' => 'The customer will get the result of his validation via email, if the validation could not be performed immediately.'
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
        $this->subscribeEvent(
            'Shopware_Modules_Admin_ValidateStep2_FilterResult',
            'ShopwareModulesAdminValidateStep2FilterResult'
        );

        $this->subscribeEvent(
            'Shopware_Modules_Admin_SaveRegisterBillingAttributes_FilterSql',
            'ShopwareModulesAdminSaveRegisterBillingAttributesFilterSql'
        );

        $this->subscribeEvent(
            'Shopware_Modules_Admin_UpdateBilling_FilterSql',
            'onShopwareModulesAdminUpdateBillingFilterSql'
        );

        $this->subscribeEvent(
            'Shopware_Modules_Admin_Login_Successful',
            'ShopwareModulesAdminLoginSuccessful'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Account',
            'onPostDispatchFrontendAccount'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
            'onPostDispatchFrontendCheckout'
        );
    }

    /**
     * Listener to ValidateStep2, checks the VatId
     * @param Enlight_Event_EventArgs $arguments
     * @return mixed
     */
    public function ShopwareModulesAdminValidateStep2FilterResult(Enlight_Event_EventArgs $arguments)
    {
        $post = $arguments->getPost();
        $errors = $arguments->getReturn();
        $errors = $this->validateVatId($post['register']['billing'], $errors);

        return $errors;
    }

    /**
     * Helper function includes the complete check process
     * @param array $billing
     * @param array $return
     * @return array
     */
    private function validateVatId($billing, $return = array())
    {
        if ($billing['ustid'] === '') {
            return $return;
        }

        $billingModel = new Billing();
        $billingModel->fromArray($billing);
        $billingModel->setVatId($billing['ustid']);

        $requester = new VatIdInformation($this->Config()->get('vatId'));

        $validatorResult = $this->validate($billingModel, $requester, true);

        $errors = $this->evaluateValidatorResult($validatorResult);

        if(!empty($errors[0]))
        {
            $email = $this->Config()->get('shopEmailNotification');
            if (!empty($email)) {
                $context = array(
                    'sVatId' => $billingModel->getVatId(),
                    'sError' => implode("\n", $validatorResult->getErrors())
                );

                $this->sendMailByTemplate('VALIDATIONERROR', $email, $context);
            }
        }

        return array(array_merge($return[0], $errors[0]), array_merge($return[1], $errors[1]));
    }

    /**
     * Helper function to validate a VatId, if validator is not available, the dummy validator can be used optionally
     * @param Billing $billing
     * @param VatIdInformation $requester
     * @param bool $dummyValidation
     * @return VatIdValidatorResult
     */
    private function validate(Billing $billing, VatIdInformation $requester, $dummyValidation = false)
    {
        $customer = new VatIdCustomerInformation($billing);
        $validator = $this->getValidator($requester->getCountryCode(), $customer->getCountryCode());
        $validatorResult = $validator->check($customer, $requester);

        if (!$dummyValidation) {
            return $validatorResult;
        }

        if ($validatorResult->serviceNotAvailable()) {
            $dummyValidator = new DummyVatIdValidator();
            $validatorResult = $dummyValidator->check($customer, $requester);
        }

        return $validatorResult;
    }

    /**
     * Helper function evaluates validators result and returns the error messages
     * @param VatIdValidatorResult $validatorResult
     * @return array
     */
    private function evaluateValidatorResult(VatIdValidatorResult $validatorResult)
    {
        $session = Shopware()->Session();
        unset($session['vatIdValidationStatus']);

        $errors = array(
            $validatorResult->getErrors(),
            array()
        );

        foreach ($errors[0] as $key => $error) {
            $key = strtolower($key);

            if ($key === 'vatid') {
                $errors[1]['ustid'] = true;
                break;
            }

            if ($key === 'street') {
                $errors[1]['streetnumber'] = true;
            }

            $errors[1][$key] = true;
        }

        if ($validatorResult->isVatIdValid() || $validatorResult->isDummyValid()) {
            $session['vatIdValidationStatus'] = $validatorResult->getStatus();
        }

        return $errors;
    }

    /**
     * Helper function to get the correct validator
     * @param string $shopCountryCode
     * @param string $customerCountryCode
     * @return VatIdValidatorInterface
     */
    private function getValidator($shopCountryCode, $customerCountryCode)
    {
        if ($this->Config()->get('extendedCheck')) {
            return $this->getExtendedValidator($shopCountryCode, $customerCountryCode);
        }

        if ($shopCountryCode !== 'DE') {
            return new SimpleMiasVatIdValidator();
        }

        if ($customerCountryCode === 'DE') {
            return new SimpleMiasVatIdValidator();
        }

        return new SimpleBffVatIdValidator();
    }

    /**
     * Helper function to get the correct extended validator
     * @param string $shopCountryCode
     * @param string $customerCountryCode
     * @return VatIdValidatorInterface
     */
    private function getExtendedValidator($shopCountryCode, $customerCountryCode)
    {
        if ($shopCountryCode !== 'DE') {
            return new ExtendedMiasVatIdValidator();
        }

        if ($customerCountryCode === 'DE') {
            return new ExtendedMiasVatIdValidator();
        }

        return new ExtendedBffVatIdValidator($this->Config()->get('confirmation'));
    }

    /**
     * Helper method to save the VatIdCheck in the database and optionally remove the VatId from users billing
     * @param Billing $billing
     * @param int $status
     * @param bool $removeBillingVatId
     * @internal param string $vatId
     */

    private function saveVatIdCheck(Billing $billing, $status = 0, $removeBillingVatId  = true)
    {
        $vatIdCheck = $this->getVatIdCheckRepository()->getVatIdCheckByBillingId($billing->getId());

        if (!$vatIdCheck) {
            $vatIdCheck = new VatIdCheck();
            $vatIdCheck->setBillingAddress($billing);
        }

        $vatIdCheck->setVatId($billing->getVatId());
        $vatIdCheck->setStatus($status);

        Shopware()->Models()->persist($vatIdCheck);
        Shopware()->Models()->flush();

        if($removeBillingVatId)
        {
            $billing->setVatId('');

            Shopware()->Models()->persist($billing);
            Shopware()->Models()->flush();
        }
    }

    /**
     * Listener to saveRegisterBilling, the billing is already saved, so the vat id have to be removed
     * @param Enlight_Event_EventArgs $arguments
     * @return mixed
     */
    public function ShopwareModulesAdminSaveRegisterBillingAttributesFilterSql(Enlight_Event_EventArgs $arguments)
    {
        $return = $arguments->getReturn();

        $session = Shopware()->Session();
        $status = new VatIdValidationStatus($session['vatIdValidationStatus']);
        unset($session['vatIdValidationStatus']);

        if($status->isDummyValid())
        {
            /** @var Billing $billing */
            $billing = $this->getBillingRepository()->findOneById($return[1][0]);
            $this->saveVatIdCheck($billing);
        }

        return $return;
    }

    /**
     * Listener to updateBilling, the billing is not saved yet, so the data can be easily changed
     * @param Enlight_Event_EventArgs $arguments
     * @return mixed
     */
    public function onShopwareModulesAdminUpdateBillingFilterSql(Enlight_Event_EventArgs $arguments)
    {
        $return = $arguments->getReturn();

        $userId = $arguments->getId();

        /** @var Billing $billing */
        $billing = $this->getBillingRepository()->findOneById($userId);

        $session = Shopware()->Session();
        $status = new VatIdValidationStatus($session['vatIdValidationStatus']);
        unset($session['vatIdValidationStatus']);

        if ($return[0]['ustid'] === '') {
            $status->setStatus(VatIdValidationStatus::VALID);
        }

        if ($status->isValid()) {
            //Remove Vat-Id from check list, if exists
            $vatIdCheck = $this->getVatIdCheckRepository()->getVatIdCheckByBillingId($billing->getId());

            if ($vatIdCheck) {
                Shopware()->Models()->remove($vatIdCheck);
                Shopware()->Models()->flush($vatIdCheck);
            }

            return $return;
        }

        if ($status->isDummyValid()) {
            $billing->setVatId($return[0]['ustid']);
            $this->saveVatIdCheck($billing, VatIdValidationStatus::UNCHECKED, false);
            $return[0]['ustid'] = '';
        }

        return $return;
    }

    /**
     * CronJob checks all vat ids in the 's_plugin_swag_vat_id_checks' database table.
     * If an id is valid, it will be removed from the table and set in the billing address
     * If an id in invalid, it will also be removed from the table, but will not be set in the billing address
     * If the service is still unavailable, the vat id keeps in the table and will not be set in the billing address
     * @param Shopware_Components_Cron_CronJob $job
     * @return bool
     */
    public function onRunSwagVatIdValidationCronJob(Shopware_Components_Cron_CronJob $job)
    {
        $vatIdChecks = $this->getVatIdCheckRepository()->getVatIdCheckBuilder()->getQuery()->getResult();
        $requester = new VatIdInformation($this->Config()->get('vatId'));

        $summary = array('sAmount' => 0, 'sInvalid' => 0);

        /**@var VatIdCheck $vatIdCheck */
        foreach ($vatIdChecks as $vatIdCheck) {
            $billing = $vatIdCheck->getBillingAddress();
            $billing->setVatId($vatIdCheck->getVatId());

            $validatorResult = $this->validate($billing, $requester);

            if ($validatorResult->serviceNotAvailable()) {
                continue;
            }

            $summary['sAmount']++;

            if ($validatorResult->isValid()) {
                //save Vat-Id in Billing
                Shopware()->Models()->persist($billing);
                Shopware()->Models()->flush();
            } else {
                $summary['sInvalid']++;
            }

            $status = $validatorResult->getStatus();
            $vatIdCheck->setStatus($status);

            Shopware()->Models()->persist($vatIdCheck);
            Shopware()->Models()->flush();

            $emailFlag = $this->Config()->get('customerEmailNotification');
            if ($emailFlag) {
                $context['sValid'] = $validatorResult->isValid();
                $this->sendMailByTemplate('CUSTOMERINFORMATION', $billing->getCustomer()->getEmail(), $context);
            }
        }

        if(empty($summary['sAmount'])) {
            return true;
        }

        $email = $this->Config()->get('shopEmailNotification');
        if (!empty($email)) {
            $this->sendMailByTemplate('CRONJOBSUMMARY', $email, $summary);
        }

        return true;
    }

    /**
     * Listener to FrontendAccount (index and billing), shows the vatId and an info, if the validator was not available
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendAccount(Enlight_Event_EventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), array('index', 'billing'));
    }

    /**
     * Listener to FrontendAccount (index and billing), shows the vatId and an info, if the validator was not available
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendCheckout(Enlight_Event_EventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), array('confirm'));
    }

    /**
     * @param Enlight_Controller_Action $controller
     * @param array $actions
     */
    public function postDispatchFrontendController(Enlight_Controller_Action $controller, $actions)
    {
        /** @var $request Zend_Controller_Request_Http */
        $request = $controller->Request();

        /** @var $response Zend_Controller_Response_Http */
        $response = $controller->Response();

        /**
         * @var $view Enlight_View_Default
         */
        $view = $controller->View();

        //Check if there is a template and if an exception has occurred
        if (!$request->isDispatched()
            || $response->isException()
            || !$view->hasTemplate()
            || !in_array($request->getActionName(), $actions)
        ) {
            return;
        }

        $vatIdCheck = $this->getVatIdCheckRepository()->getVatIdCheckByCustomerId(Shopware()->Session()->sUserId);

        if (!$vatIdCheck) {
            return;
        }

        //Add our plugin template directory to load our slogan extension.
        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('frontend/plugins/swag_vat_id_validation/index.tpl');

        $status = new VatIdValidationStatus($vatIdCheck->getStatus());
        $errors = $this->getErrors($status);
        $view->assign('vatIdCheck',
            array(
                'vatId' => $vatIdCheck->getVatId(),
                'errors' => $errors,
                'success' => $status->isVatIdValid()
            )
        );

        if ($status->isValid()) {
            Shopware()->Models()->remove($vatIdCheck);
            Shopware()->Models()->flush($vatIdCheck);
        }
    }

    /**
     * Helper function sets the ErrorMessages and ErrorFlags by the VatIdValidationStatus
     * @param VatIdValidationStatus $status
     * @return array
     */
    private function getErrors(VatIdValidationStatus $status)
    {
        $errors = array(
            'messages' => array(),
            'flags' => array()
        );

        if ($status->isValid()) {
            return $errors;
        }

        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/main');

        if ($status->serviceNotAvailable()) {
            $errors['messages'][] = $snippets->get('messages/checkNotAvailable');

            if ($this->Config()->get('customerEmailNotification')) {
                $errors['messages'][] = $snippets->get('messages/emailNotification');
            }

            return $errors;
        }

        if (!$status->isVatIdValid()) {
            $errors['messages'][] = $snippets->get('validator/error/vatId');
            $errors['flags']['ustid'] = true;
            return $errors;
        }

        if (!$status->isCompanyValid()) {
            $errors['messages'][] = $snippets->get('validator/extended/error/company');
            $errors['flags']['company'] = true;
        }

        if (!$status->isStreetValid()) {
            $errors['messages'][] = $snippets->get('validator/extended/error/street');
            $errors['flags']['street'] = true;
            $errors['flags']['streetnumber'] = true;
        }

        if (!$status->isZipCodeValid()) {
            $errors['messages'][] = $snippets->get('validator/extended/error/zipCode');
            $errors['flags']['zipcode'] = true;
        }

        if (!$status->isCityValid()) {
            $errors['messages'][] = $snippets->get('validator/extended/error/city');
            $errors['flags']['city'] = true;
        }

        return $errors;
    }

    public function ShopwareModulesAdminLoginSuccessful(Enlight_Event_EventArgs $arguments)
    {
        $user = $arguments->getUser();

        /** @var Billing $billing */
        $billing = $this->getBillingRepository()->findOneByCustomerId($user['id']);

        $vatId = $billing->getVatId();

        if ($vatId === '') {
            return;
        }

        $requester = new VatIdInformation($this->Config()->get('vatId'));
        $validatorResult = $this->validate($billing, $requester, true);

        if ($validatorResult->isValid()) {
            return;
        }

        $status = $validatorResult->getStatus();

        if ($validatorResult->isDummyValid()) {
            $status = VatIdValidationStatus::UNCHECKED;
        }

        $this->saveVatIdCheck($billing, $status);
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