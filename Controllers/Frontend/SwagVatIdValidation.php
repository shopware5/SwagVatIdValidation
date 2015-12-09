<?php

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
 * @package    Shopware_Controllers_Frontend_SwagBrowserLanguage
 * @copyright  Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */
use Doctrine\DBAL\Connection;

class Shopware_Controllers_Frontend_SwagVatIdValidation extends Enlight_Controller_Action
{
    /**
     * @var  Shopware_Plugins_Core_SwagVatIdValidation_Bootstrap
     */
    private $config;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * The pre-dispatch function of this controller
     */
    public function preDispatch()
    {
        $this->config = $this->get('plugins')->Core()->SwagVatIdValidation()->Config();
        $this->connection = Shopware()->Models()->getConnection();
    }

    /**
     * This action displays the content of the modal box
     */
    public function getModalAction()
    {
        $this->loadTemplate();

        $ISOs = $this->config->get('disabledCountryISOs');

        if(is_string($ISOs)) {
            $ISOs = explode(',', $ISOs);
            $ISOs = array_map('trim', $ISOs);
        } else {
            $ISOs = $ISOs->toArray();
        }

        $builder = $this->connection->createQueryBuilder();

        $countryNameArray = $builder->select('countries.countryname')
            ->from('s_core_countries', 'countries')
            ->where('countryiso IN(:isos)')
            ->setParameter('isos', $ISOs, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll();

        $countries = array_column($countryNameArray, 'countryname');
        $this->View()->assign('disabledCountries', $countries);
    }

    private function loadTemplate()
    {
        $version = Shopware()->Shop()->getTemplate()->getVersion();
        if ($version >= 3) {
            $this->View()->loadTemplate('responsive/swag_vat_id_validation/modal_info_content.tpl');
        } else {
            $this->View()->loadTemplate('emotion/frontend/plugins/swag_vat_id_validation/modal_info_content.tpl');
        }
    }
}
