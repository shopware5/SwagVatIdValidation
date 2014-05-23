<?php

use Shopware\Plugins\SwagVatIdValidation\Components;
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
            'onPostDispatchFrontendAccountBilling'
        );
    }

    /**
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

        if ($post['register']['billing']['ustid'] === '') {
            return $errors;
        }

        $customer = new Components\VatIdCustomerInformation(
            $post['register']['billing']['ustid'],
            $post['register']['billing']['company'],
            $post['register']['billing']['street'] . ' ' . $post['register']['billing']['streetnumber'],
            $post['register']['billing']['zipcode'],
            $post['register']['billing']['city']);
        $requester = new Components\VatIdInformation($this->Config()->get('vatId'));

        $validator = $this->getValidator($requester->getCountryCode());
        $validatorResult = $validator->check($customer, $requester);

        $errors = $arguments->getReturn();

        $session = Shopware()->Session();
        unset($session['vatIdValidationNotAvailable']);

        if ($validatorResult->isValid()) {
            return $errors;
        }

        if ($validatorResult->serviceNotAvailable()) {
            $dummyValidator = new Components\DummyVatIdValidator();
            $validatorResult = $dummyValidator->check($customer, $requester);

            if($validatorResult->isValid())
            {
                $session['vatIdValidationNotAvailable'] = true;
                return $errors;
            }
        }

        $errors[0] = array_merge($errors[0], $validatorResult->getErrors());
        $errors[1]['ustid'] = true;

        return $errors;
    }

    /**
     * Helper method returns the correct vatIdValidator
     * @param $shopCountryCode
     * @return Components\VatIdValidatorInterface
     */
    private function getValidator($shopCountryCode)
    {
        if ($this->Config()->get('extendedCheck')) {
            if ($shopCountryCode === 'DE') {
                return new Components\ExtendedBffVatIdValidator($this->Config()->get('confirmation'));
            }

            return new Components\ExtendedMiasVatIdValidator();
        }

        if ($shopCountryCode === 'DE') {
            return new Components\SimpleBffVatIdValidator();
        }

        return new Components\SimpleMiasVatIdValidator();
    }

    /**
     * Helper method to get a user billing address
     * @param integer $billingId
     * @return Billing
     */
    private function getBillingById($billingId)
    {
        $billing = $this->getBillingRepository()->findOneById($billingId);

        return $billing;
    }

    /**
     * Helper method to get a user billing address
     * @param integer $billingId
     * @return Billing
     */
    private function getBillingByCustomerId($userId)
    {
        $billing = $this->getBillingRepository()->findOneByCustomerId($userId);

        return $billing;
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
        }

        $vatIdCheck->setBillingAddress($billing);
        $vatIdCheck->setVatId($vatId);

        Shopware()->Models()->persist($vatIdCheck);
        Shopware()->Models()->flush();
    }

    /**
     * Helper method to set the VatId from the user billing address
     * @param Billing $billing
     */
    private function setBillingVatId(Billing $billing, $value = '')
    {
        $billing->setVatId($value);

        Shopware()->Models()->persist($billing);
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
            $billing = $this->getBillingById($return[1][0]);
            $this->saveVatIdForLaterCheck($billing);
            $this->setBillingVatId($billing);
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

        $billing = $this->getBillingByCustomerId($userId);

        if($return[0]['ustid'] === '')
        {
            //Remove Vat-Id from check list
            $check = $this->getVatIdCheck($billing, true);
            Shopware()->Db()->exec(sprintf('DELETE FROM s_plugin_swag_vat_id_checks WHERE id = %d', $check[0]['id']));
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
        $vatIdCheck = Shopware()->Db()->fetchAll('SELECT * FROM s_plugin_swag_vat_id_checks');

        $requester = new Components\VatIdInformation($this->Config()->get('vatId'));
        $validator = $this->getValidator($requester->getCountryCode());

        foreach ($vatIdCheck as $check) {
            $billing = $this->getBillingById($check['billingAddressId']);

            $customer = new Components\VatIdCustomerInformation(
                $check['vatId'],
                $billing->getCompany(),
                $billing->getStreet() . ' ' . $billing->getStreetNumber(),
                $billing->getZipCode(),
                $billing->getCity());

            $validatorResult = $validator->check($customer, $requester);

            if ($validatorResult->serviceNotAvailable()) {
                continue;
            }

            //Remove Vat-Id from check list
            Shopware()->Db()->exec(sprintf('DELETE FROM s_plugin_swag_vat_id_checks WHERE id = %d', $check['id']));

            if ($validatorResult->isValid()) {
                //save Vat-Id in Billing
                $this->setBillingVatId($billing, $check['vatId']);
            }
        }

        return true;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendAccountBilling(Enlight_Event_EventArgs $arguments)
    {
        /**@var $controller Shopware_Controllers_Frontend_Index */
        $controller = $arguments->getSubject();

        /**
         * @var $request Zend_Controller_Request_Http
         */
        $request = $controller->Request();

        /**
         * @var $response Zend_Controller_Response_Http
         */
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

        $user = $this->getCustomer();
        $vatIdCheck = $this->getVatIdCheckRepository()->getVatIdCheckByCustomerIdBuilder()
            ->setParameter('customerId', $user)
            ->getQuery()
            ->getArrayResult();

        //Add our plugin template directory to load our slogan extension.
        $view->addTemplateDir($this->Path() . 'Views/');

        if (!empty($vatIdCheck)) {
            $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/main');

            $messages = array($snippets->get('messages/checkNotAvailable'));

            if ($this->Config()->get('emailNotification')) {
                $messages[] = $snippets->get('messages/emailNotification');
            }

            $view->assign('vatIdCheck', array(
                    'vatId' => $vatIdCheck[0]['vatId'],
                    'messages' => $messages
                )
            );
        }

        $view->extendsTemplate('frontend/plugins/swag_vat_id_validation/index.tpl');
    }

    /**
     * @return mixed
     */
    private function getCustomer()
    {
        return Shopware()->Session()->sUserId;
    }

    /**
     * @param Billing $billing
     * @param bool $array
     * @return array|mixed|null
     */
    private function getVatIdCheck(Billing $billing, $array = false)
    {
        if ($billing->getVatId()) {
            return null;
        }

        $billingId = $billing->getId();

        $query = $this->getVatIdCheckRepository()->getVatIdCheckByBillingIdBuilder()
            ->setParameter('billingId', $billingId)
            ->getQuery();

        if ($array) {
            return $query->getArrayResult();
        }

        return $query->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_SIMPLEOBJECT);
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