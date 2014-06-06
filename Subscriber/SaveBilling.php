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

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidationStatus;
use Shopware\Models\Customer\Billing;

/**
 * This example is going to show how to test your methods without global shopware state
 *
 * Class Account
 * @package Shopware\Plugins\SwagScdExample\Subscriber
 */
class SaveBilling extends ValidationPoint
{
    public static function getSubscribedEvents()
    {
        return array(
            'Shopware_Modules_Admin_ValidateStep2_FilterResult' => 'onValidateStep2FilterResult'
        );
    }

    /**
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