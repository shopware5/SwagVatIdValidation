<?php
declare(strict_types=1);

/**
 * Shopware Plugins
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this plugin can be used under
 * a proprietary license as set forth in our Terms and Conditions,
 * section 2.1.2.2 (Conditions of Usage).
 *
 * The text of our proprietary license additionally can be found at and
 * in the LICENSE file you have received along with this plugin.
 *
 * This plugin is distributed in the hope that it will be useful,
 * with LIMITED WARRANTY AND LIABILITY as set forth in our
 * Terms and Conditions, sections 9 (Warranty) and 10 (Liability).
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the plugin does not imply a trademark license.
 * Therefore any rights, title and interest in our trademarks
 * remain entirely with us.
 */

use Doctrine\DBAL\Connection;

class Shopware_Controllers_Widgets_SwagVatIdValidation extends Enlight_Controller_Action
{
    /**
     * This action displays the content of the modal box
     *
     * @return void
     */
    public function modalInfoContentAction()
    {
        $ISOs = $this->get('plugins')->Core()->SwagVatIdValidation()->Config()->get('disabledCountryISOs');

        if (\is_string($ISOs)) {
            $ISOs = \explode(',', $ISOs);
            $ISOs = \array_map('trim', $ISOs);
        } else {
            $ISOs = $ISOs->toArray();
        }

        $builder = $this->get('dbal_connection')->createQueryBuilder();

        $countryNameArray = $builder->select('countries.countryname')
            ->from('s_core_countries', 'countries')
            ->where('countryiso IN(:isos)')
            ->setParameter('isos', $ISOs, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll();

        $countries = \array_column($countryNameArray, 'countryname');
        $this->View()->assign('disabledCountries', $countries);
    }
}
