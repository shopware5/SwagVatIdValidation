<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
