<?php

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorInterface;
use Shopware\Plugins\SwagVatIdValidation\Components\DummyVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\SimpleBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\ExtendedBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\SimpleMiasVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\ExtendedMiasVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;

use Shopware\CustomModels\SwagVatIdValidation\VatIdCheck;
use Shopware\Models\Customer\Billing;

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
    /** @var  \Shopware\Models\Customer\BillingRepository */
    private $billingRepository;

    /** @var \Shopware\CustomModels\SwagVatIdValidation\Repository */
    private $vatIdCheckRepository;


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
        return 'UstId-Prüfung bei Registrierung';
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
     * Returns an array with some informations about the plugin.
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            //'description' => file_get_contents(__DIR__ . '/info.txt'),
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
                'label' => 'UstId-Nummer',
                'value' => Shopware()->Config()->get('sTAXNUMBER'),
                'description' => 'Eigene UstId-Nummer, die zur Prüfung verwendet werden soll.',
                'required' => true
            )
        );

        $form->setElement(
            'checkbox',
            'emailNotification',
            array(
                'label' => 'E-Mail-Benachrichtigung',
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
                'description' => 'Die erweiterte Prüfung kann nur von deutschen UstId-Nummern angefragt werden.'
            )
        );

        $form->setElement(
            'checkbox',
            'confirmation',
            array(
                'label' => 'Amtliche Bestätigungsmitteilung',
                'value' => false,
                'description' => 'Amtliche Bestätigungsmitteilung bei der erweiterten Überprüfung anfordern.'
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
            'Enlight_Controller_Action_PostDispatch_Frontend_Account',
            'onPostDispatchFrontendAccount'
        );
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

        return array(array_merge($return[0], $errors[0]), array_merge($return[1], $errors[1]));
    }

    /**
     * Helper function to validate a VatId, if validator is not available, the dummy validator can be used optionally
     * @param Billing $billing
     * @param VatIdInformation $requester
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
        $errors = array(array(), array());

        $session = Shopware()->Session();
        unset($session['vatIdValidationNotAvailable']);

        if ($validatorResult->isValid()) {
            return $errors;
        }

        if ($validatorResult->isDummyValid()) {
            $session['vatIdValidationNotAvailable'] = true;
            return $errors;
        }

        $errors[0] = array_merge($errors[0], $validatorResult->getErrors());
        $errors[1]['ustid'] = true;

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
     * Helper method to save the VatId for later checks if the the validation service was not available
     * @param Billing $billing
     */
    private function saveVatIdForLaterCheck(Billing $billing, $vatId = '')
    {
        if ($vatId === '') {
            $vatId = $billing->getVatId();
        }

        $vatIdCheck = $this->getVatIdCheck($billing);

        if (empty($vatIdCheck)) {
            $vatIdCheck = new \Shopware\CustomModels\SwagVatIdValidation\VatIdCheck();
            $vatIdCheck->setBillingAddress($billing);
        }

        $vatIdCheck->setVatId($vatId);

        Shopware()->Models()->persist($vatIdCheck);
        Shopware()->Models()->flush();
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
        if($session['vatIdValidationNotAvailable'])
        {
            $billing = $this->getBillingRepository()->findOneById($return[1][0]);
            $this->saveVatIdForLaterCheck($billing);
            $billing->setVatId('');

            Shopware()->Models()->persist($billing);
            Shopware()->Models()->flush();

            unset($session['vatIdValidationNotAvailable']);
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

        $billing = $this->getBillingRepository()->findOneById($userId);

        if($return[0]['ustid'] === '')
        {
            //Remove Vat-Id from check list
            $vatIdCheck = $this->getVatIdCheck($billing);

            Shopware()->Models()->remove($vatIdCheck);
            Shopware()->Models()->flush($vatIdCheck);

            return $return;
        }

        $session = Shopware()->Session();
        if($session['vatIdValidationNotAvailable'])
        {
            $this->saveVatIdForLaterCheck($billing, $return[0]['ustid']);
            $return[0]['ustid'] = '';
            unset($session['vatIdValidationNotAvailable']);
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

        /**@var VatIdCheck $vatIdCheck */
        foreach ($vatIdChecks as $vatIdCheck) {
            $billing = $vatIdCheck->getBillingAddress();
            $billing->setVatId($vatIdCheck->getVatId());

            $validatorResult = $this->validate($billing, $requester);

            if ($validatorResult->serviceNotAvailable()) {
                continue;
            }

            //Remove Vat-Id from check list
            Shopware()->Models()->remove($vatIdCheck);
            Shopware()->Models()->flush($vatIdCheck);

            if ($validatorResult->isValid()) {
                //save Vat-Id in Billing
                Shopware()->Models()->persist($billing);
                Shopware()->Models()->flush();
            }
        }

        return true;
    }

    /**
     * Listener to FrontendAccount (index and billing), shows the vatId and an info, if the validator was not available
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendAccount(Enlight_Event_EventArgs $arguments)
    {
        /** @var $controller Shopware_Controllers_Frontend_Index */
        $controller = $arguments->getSubject();

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
            || !in_array($request->getActionName(), array('index', 'billing'))
        ) {
            return;
        }

        $vatIdCheck = $this->getVatIdCheckRepository()->getVatIdCheckByCustomerId(Shopware()->Session()->sUserId);

        //Add our plugin template directory to load our slogan extension.
        $view->addTemplateDir($this->Path() . 'Views/');

        if ($vatIdCheck) {
            $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/main');

            $messages = array($snippets->get('messages/checkNotAvailable'));

            if ($this->Config()->get('emailNotification')) {
                $messages[] = $snippets->get('messages/emailNotification');
            }

            $view->assign('vatIdCheck', array('vatId' => $vatIdCheck->getVatId(), 'messages' => $messages));
        }

        $view->extendsTemplate('frontend/plugins/swag_vat_id_validation/index.tpl');
    }

    /**
     * Helper function to get the vatId check by billing
     * @param Billing $billing
     * @return null|VatIdCheck
     */
    private function getVatIdCheck(Billing $billing)
    {
        if ($billing->getVatId() !== '') {
            return null;
        }

        /** @var VatIdCheck $vatIdCheck */
        $vatIdCheck =  $this->getVatIdCheckRepository()->getVatIdCheckByBillingId($billing->getId());

        return $vatIdCheck;
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