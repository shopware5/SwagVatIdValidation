<?php

/**
 * Shopware 5
 * Copyright (c) shopware AG
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

class Shopware_Controllers_Widgets_SwagVatIdValidation extends Enlight_Controller_Action
{
    /**
     * This action displays the content of the modal box
     */
    public function modalInfoContentAction()
    {
        /** @var Enlight_Config|string $ISOs */
        $ISOs = $this->get('plugins')->Core()->SwagVatIdValidation()->Config()->get('disabledCountryISOs');

        if (is_string($ISOs)) {
            $ISOs = explode(',', $ISOs);
            $ISOs = array_map('trim', $ISOs);
        } else {
            $ISOs = $ISOs->toArray();
        }

        /** @var \Doctrine\DBAL\Query\QueryBuilder $builder */
        $builder = $this->get('dbal_connection')->createQueryBuilder();

        $countryNameArray = $builder->select('countries.countryname')
            ->from('s_core_countries', 'countries')
            ->where('countryiso IN(:isos)')
            ->setParameter('isos', $ISOs, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll();

        $countries = array_column($countryNameArray, 'countryname');
        $this->View()->assign('disabledCountries', $countries);
    }
}
