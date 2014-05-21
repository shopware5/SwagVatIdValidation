<?php

use Shopware\Plugins\SwagVatIdValidation\Components;

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
        return true;
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
                'description' => 'Eigene UstId-Nummer, die zur Prüfung verwendet werden soll.'
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
            'Shopware_Modules_Admin_ValidateStep2_FilterStart',
            'ShopwareModulesAdminValidateStep2FilterStart'
        );
    }

    public function ShopwareModulesAdminValidateStep2FilterStart(Enlight_Event_EventArgs $arguments)
    {
        $post = $arguments->getPost();

        $errors = $arguments->getReturn();

        if(empty($post['register']['billing']['ustid']))
        {
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

        if (!$validatorResult->isValid()) {
            $errors = $arguments->getReturn();

            $errors[1]['ustid'] = true;
            $errors[0] = array_merge($errors[0], $validatorResult->getErrors());

            return $errors;
        }
    }

    /**
     * @param $shopCountryCode
     * @return Components\VatIdValidatorInterface
     */
    public function getValidator($shopCountryCode)
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