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

use Shopware\Components\Model\ModelManager;

/**
 * Class Login
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class Login extends ValidationPoint
{
    /** @var  string */
    private static $action;

    /** @var \Enlight_Components_Session_Namespace  */
    private $session;

    /**
     * Constructor sets all properties
     * @param \Enlight_Config $config
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param ModelManager $modelManager
     * @param \Shopware_Components_TemplateMail $templateMail
     * @param \Enlight_Components_Session_Namespace $session
     * @param string $action
     */
    public function __construct(\Enlight_Config $config, \Shopware_Components_Snippet_Manager $snippetManager, ModelManager $modelManager = null, \Shopware_Components_TemplateMail $templateMail = null, \Enlight_Components_Session_Namespace $session, $action)
    {
        parent::__construct($config, $snippetManager, $modelManager, $templateMail);
        $this->session = $session;
        self::$action = $action;
    }

    /**
     * Returns the events we need to subscribe to
     * @return array
     */
    public static function getSubscribedEvents()
    {
        //After successfully registration, this would be a second validation. The first on save, the second on login.
        if (self::$action === 'saveRegister') {
            return array();
        }

        return array(
            'Shopware_Modules_Admin_Login_Successful' => 'onLoginSuccessful'
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onLoginSuccessful(\Enlight_Event_EventArgs $arguments)
    {
        $user = $arguments->getUser();

        $billing = $this->getBillingRepository()->createQueryBuilder('billing')
            ->select(
                'billing.id',
                'billing.vatId',
                'billing.company',
                'billing.street',
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
            $billing[0]['street'],
            $billing[0]['zipCode'],
            $billing[0]['city'],
            $billing[0]['id']
        );

        $this->session->offsetSet('vatIdValidationStatus', $result->serialize());
    }
}