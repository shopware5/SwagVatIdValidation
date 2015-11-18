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

namespace Shopware\Plugins\SwagVatIdValidation\Subscriber;
use Shopware\Plugins\SwagVatIdValidation\Components\EUStates;

/**
 * Class SaveBilling
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class SaveBilling extends ValidationPoint
{
    private $countryIso;

    /**
     * Returns the events we need to subscribe to
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Shopware_Modules_Admin_ValidateStep2_FilterStart' => 'onValidateStep2FilterStart',
            'Shopware_Modules_Admin_ValidateStep2_FilterResult' => 'onValidateStep2FilterResult'
        );
    }

    /**
     * Listener to check if the VAT Id is required or not.
     * @param \Enlight_Event_EventArgs $arguments
     * @return array|mixed
     */
    public function onValidateStep2FilterStart(\Enlight_Event_EventArgs $arguments)
    {
        $post = $arguments->getPost();
        $errors = $arguments->getReturn();

        /**
         * There is no VAT Id required, if...
         * ... the Vat Id is not required in the config,
         */
        if (!$this->config->get('vatIdRequired')) {
            return $errors;
        }

        /**
         * ... the customer is not a company,
         */
        if($this->customerIsNoCompany($post['customer_type'], $post['register']['billing']['company'])) {
            return $errors;
        }

        /**
         * ... the billing country is a non-EU-country
         */
        $countryId = $post['register']['billing']['country'];
        $this->countryIso = $countryISO = $this->getCountryRepository()->findOneById($countryId)->getIso();

        if(!EUStates::isEUCountry($countryISO)) {
            return $errors;
        }

        /**
         * ... or the check is disabled for the billing country.
         */
        $disabledCountries = explode(',', str_replace(' ', '', $this->config->get('disabledCountryISOs')));

        if (in_array($countryISO, $disabledCountries)) {
            return $errors;
        }

        /**
         * Otherwise if the VAT Id is still empty, set the error flag
         */
        if ($post['register']['billing']['ustid'] === '') {
            $errors[1]['ustid'] = true;
        }

        return $errors;
    }

    /**
     * Listener to validate the VAT ID
     * @param \Enlight_Event_EventArgs $arguments
     * @return array|mixed
     */
    public function onValidateStep2FilterResult(\Enlight_Event_EventArgs $arguments)
    {
        $post = $arguments->getPost();
        $errors = $arguments->getReturn();

        if($this->customerIsNoCompany($post['customer_type'], $post['register']['billing']['company'])) {
            return $errors;
        }

        $result = $this->validate(
            $post['register']['billing']['ustid'],
            $post['register']['billing']['company'],
            $post['register']['billing']['street'],
            $post['register']['billing']['zipcode'],
            $post['register']['billing']['city'],
            $this->countryIso
        );

        $errors = array(
            array_merge($result->getErrorMessages(), $errors[0]),
            array_merge($result->getErrorFlags(), $errors[1])
        );

        return $errors;
    }

    /**
     * Helper method, returns true if customer type is not "business" or the company is not set
     * @param string $customerType
     * @param string $company
     * @return bool
     */
    private function customerIsNoCompany($customerType, $company)
    {
        return (($customerType !== 'business') || (!$company));
    }
}