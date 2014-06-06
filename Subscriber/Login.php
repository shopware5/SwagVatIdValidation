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

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidationStatus;

use Shopware\Models\Customer\Billing;

/**
 * This example is going to show how to test your methods without global shopware state
 *
 * Class Account
 * @package Shopware\Plugins\SwagScdExample\Subscriber
 */
class Login extends ValidationPoint
{
    private static $action;

    public function __construct($config, $action)
    {
        parent::__construct($config);
        self::$action = $action;
    }


    public static function getSubscribedEvents()
    {
        if (self::$action === 'saveRegister') {
            return array();
        }

        return array(
            'Shopware_Modules_Admin_Login_Successful' => 'onLoginSuccessful'
        );
    }

    public function onLoginSuccessful(\Enlight_Event_EventArgs $arguments)
    {
        $user = $arguments->getUser();

        $billing = $this->getBillingRepository()->createQueryBuilder('billing')
            ->select(
                'billing.id',
                'billing.vatId',
                'billing.company',
                'billing.street',
                'billing.streetNumber',
                'billing.zipCode',
                'billing.city'
            )
            ->where('billing.customerId = :customerId')
            ->setParameter('customerId', $user['id'])
            ->setMaxResults(1)
            ->getQuery()->getArrayResult();

        $result = $this->validate(
            $billing[0]['vatId'],
            $billing[0]['company'],
            $billing[0]['street'] . ' ' . $billing[0]['streetNumber'],
            $billing[0]['zipCode'],
            $billing[0]['city'],
            $billing[0]['id']
        );

        $session = Shopware()->Session();
        $session->offsetSet('vatIdValidationStatus', $result->serialize());
    }
}