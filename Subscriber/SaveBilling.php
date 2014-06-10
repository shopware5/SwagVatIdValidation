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

/**
 * Class SaveBilling
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class SaveBilling extends ValidationPoint
{
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

        if ($post['customer_type'] !== 'business') {
            return $errors;
        }

        if ($post['register']['billing']['company'] === '') {
            return $errors;
        }

        if (!$this->config->get('vatIdRequired')) {
            return $errors;
        }

        if ($post['register']['billing']['ustid'] === '') {
            $errors[1]['ustid'] = true;
        }

        return $errors;
    }

    /**
     * Listener to validate the VAT Id
     * @param \Enlight_Event_EventArgs $arguments
     * @return array|mixed
     */
    public function onValidateStep2FilterResult(\Enlight_Event_EventArgs $arguments)
    {
        $post = $arguments->getPost();
        $errors = $arguments->getReturn();

        $result = $this->validate(
            $post['register']['billing']['ustid'],
            $post['register']['billing']['company'],
            $post['register']['billing']['street'],
            $post['register']['billing']['zipcode'],
            $post['register']['billing']['city']
        );

        $errors = array(
            array_merge($result->getErrorMessages(), $errors[0]),
            array_merge($result->getErrorFlags(), $errors[1])
        );

        return $errors;
    }
}