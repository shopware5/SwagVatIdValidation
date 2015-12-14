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

use Enlight\Event\SubscriberInterface;
use Shopware\Models\Shop\Shop;
use Shopware\Plugins\SwagVatIdValidation\Components\EUStates;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;

/**
 * Class TemplateExtension
 *
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class TemplateExtension implements SubscriberInterface
{
    /** @var  \Enlight_Config */
    private $config;

    /** @var  string */
    private $path;

    /** @var  \Enlight_Components_Session_Namespace */
    private $session;

    /** @var  \Shopware_Components_Snippet_Manager */
    private $snippetManager;

    /** @var  Shop */
    private $shop;

    /**
     * Constructor sets all properties
     *
     * @param \Enlight_Config $config
     * @param string $path
     * @param \Enlight_Components_Session_Namespace $session
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param Shop $shop
     */
    public function __construct(
        \Enlight_Config $config,
        $path,
        \Enlight_Components_Session_Namespace $session,
        \Shopware_Components_Snippet_Manager $snippetManager,
        Shop $shop
    ) {
        $this->config = $config;
        $this->path = $path;
        $this->session = $session;
        $this->snippetManager = $snippetManager;
        $this->shop = $shop;
    }

    /**
     * Returns the events we need to subscribe to
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Account' => 'onPostDispatchFrontendAccount',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchFrontendCheckout',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Register' => 'onPostDispatchFrontendRegister'
        );
    }

    /**
     * Listener to FrontendAccount (index and billing)
     * On Account Index, a short info message will be shown if the validator was not available
     * On Account Billing, the Vat Id input field can be set required
     *
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendAccount(\Enlight_Event_EventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), array('index', 'billing'));
    }

    /**
     * Listener to FrontendCheckout (confirm),
     * Shows a short info message if the validator was not available
     *
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendCheckout(\Enlight_Event_EventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), array('confirm'));
    }

    /**
     * Listener to FrontendRegister (index)
     * The Vat Id input field can be set required
     *
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onPostDispatchFrontendRegister(\Enlight_Event_EventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), array('index'));
    }

    /**
     * Helper function to assign the plugin data to the template
     *
     * @param \Enlight_Controller_Action $controller
     * @param array $actions
     */
    public function postDispatchFrontendController(\Enlight_Controller_Action $controller, $actions)
    {
        /** @var $request \Zend_Controller_Request_Http */
        $request = $controller->Request();

        /** @var $view \Enlight_View_Default */
        $view = $controller->View();

        //Check if there is a template and if an exception has occurred
        if (!in_array($request->getActionName(), $actions)) {
            return;
        }

        $this->extendsTemplate($view, 'frontend/plugins/swag_vat_id_validation/index.tpl');

        $errorMessages = array();
        $requiredButEmpty = false;

        if ($this->session->offsetExists('vatIdValidationStatus')) {
            $serialized = $this->session->offsetGet('vatIdValidationStatus');

            $result = new VatIdValidatorResult($this->snippetManager);
            $result->unserialize($serialized);
            $this->session->offsetUnset('vatIdValidationStatus');

            $errorMessages = $result->getErrorMessages();
        }

        if ($errorMessages) {
            $requiredButEmpty = array_key_exists('required', $errorMessages);
            unset($errorMessages['required']);
        }

        $required = (bool) $this->config->get('vatIdRequired');
        $displayMessage = ($required) ? $this->hasExceptedEUCountries() : false;

        $view->assign(
            array(
                'displayMessage' => $displayMessage,
                'vatIdCheck' => array(
                    'errorMessages' => array_values($errorMessages),
                    'required' => $required,
                    'requiredButEmpty' => $requiredButEmpty
                )
            )
        );
    }

    /**
     * Returns true, if there are valid EU countries excepted from the input requirement
     * @return bool
     */
    private function hasExceptedEUCountries()
    {
        $ISOs = $this->config->get('disabledCountryISOs');

        if (is_string($ISOs)) {
            $ISOs = explode(',', $ISOs);
            $ISOs = array_map('trim', $ISOs);
        } else {
            $ISOs = $ISOs->toArray();
        }

        return EUStates::hasValidEUCountry($ISOs);
    }

    /**
     * @param \Enlight_View_Default $view
     * @param string $templatePath
     */
    private function extendsTemplate(\Enlight_View_Default $view, $templatePath)
    {
        $version = $this->shop->getTemplate()->getVersion();
        if ($version >= 3) {
            $view->sErrorMessages = array_values($view->sErrorMessages);
            $view->addTemplateDir($this->path . 'Views/responsive/');
        } else {
            $view->addTemplateDir($this->path . 'Views/emotion/');
            $view->extendsTemplate($templatePath);
        }
    }
}
