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
class Update extends ValidationPoint
{
    public static function getSubscribedEvents()
    {
        if (parent::$action !== 'saveBilling') {
            return array();
        }

        return array(
            'Shopware_Modules_Admin_ValidateStep2_FilterResult' => 'onValidateStep2FilterResult',
            'Shopware_Modules_Admin_UpdateBilling_FilterSql' => 'onUpdateBilling',
        );
    }

    public function onUpdateBilling(\Enlight_Event_EventArgs $arguments)
    {
        $return = $arguments->getReturn();

        $userId = $arguments->getId();

        /** @var Billing $billing */
        $billing = $this->getBillingRepository()->findOneById($userId);

        $session = Shopware()->Session();
        $status = new VatIdValidationStatus($session['vatIdValidationStatus']);
        unset($session['vatIdValidationStatus']);

        if ($return[0]['ustid'] === '') {
            $status->setStatus(VatIdValidationStatus::VALID);
        }

        if ($status->isValid()) {
            //Remove Vat-Id from check list, if exists
            $vatIdCheck = $this->getVatIdCheckRepository()->getVatIdCheckByBillingId($billing->getId());

            if ($vatIdCheck) {
                Shopware()->Models()->remove($vatIdCheck);
                Shopware()->Models()->flush($vatIdCheck);
            }

            return $return;
        }

        if ($status->isDummyValid()) {
            $billing->setVatId($return[0]['ustid']);
            $this->saveVatIdCheck($billing, VatIdValidationStatus::UNCHECKED, false);
            $return[0]['ustid'] = '';
        }

        return $return;
    }
}