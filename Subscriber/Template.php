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
 */

namespace SwagVatIdValidation\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Controller_Request_RequestHttp as Request;
use SwagVatIdValidation\Components\DependencyProviderInterface;
use SwagVatIdValidation\Components\EUStates;
use SwagVatIdValidation\Components\VatIdValidatorResult;

class Template implements SubscriberInterface
{
    /**
     * @var DependencyProviderInterface
     */
    private $dependencyProvider;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var string
     */
    private $pluginPath;

    /**
     * @param DependencyProviderInterface          $dependencyProvider
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param \Shopware_Components_Config          $config
     * @param string                               $pluginPath
     */
    public function __construct(
        DependencyProviderInterface $dependencyProvider,
        \Shopware_Components_Snippet_Manager $snippetManager,
        \Shopware_Components_Config $config,
        $pluginPath
    ) {
        $this->dependencyProvider = $dependencyProvider;
        $this->snippetManager = $snippetManager;
        $this->config = $config;
        $this->pluginPath = $pluginPath;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Account' => 'onPostDispatchFrontendAccount',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchFrontendCheckout',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Register' => 'onPostDispatchFrontendRegister',
            'Theme_Inheritance_Template_Directories_Collected' => 'onTemplatesCollected',
        ];
    }

    /**
     * Listener to FrontendAccount (index and billing)
     * On Account Index, a short info message will be shown if the validator was not available
     * On Account Billing, the Vat Id input field can be set required
     *
     * @param ActionEventArgs $arguments
     */
    public function onPostDispatchFrontendAccount(ActionEventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), ['index', 'billing']);
    }

    /**
     * Listener to FrontendCheckout (confirm),
     * Shows a short info message if the validator was not available
     *
     * @param ActionEventArgs $arguments
     */
    public function onPostDispatchFrontendCheckout(ActionEventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), ['confirm']);
    }

    /**
     * Listener to FrontendRegister (index)
     * The Vat Id input field can be set required
     *
     * @param ActionEventArgs $arguments
     */
    public function onPostDispatchFrontendRegister(ActionEventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), ['index']);
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     */
    public function onTemplatesCollected(\Enlight_Event_EventArgs $arguments)
    {
        $dirs = $arguments->getReturn();

        $dirs[] = $this->pluginPath . '/Resources/views';

        $arguments->setReturn($dirs);
    }

    /**
     * Helper function to assign the plugin data to the template
     *
     * @param Enlight_Controller_Action $controller
     * @param string[]                  $actions
     */
    public function postDispatchFrontendController(Enlight_Controller_Action $controller, array $actions)
    {
        /** @var Request $request */
        $request = $controller->Request();

        if (!in_array($request->getActionName(), $actions, true)) {
            return;
        }

        /** @var \Enlight_Components_Session_Namespace $session */
        $session = $this->dependencyProvider->getSession();

        /** @var $view \Enlight_View_Default */
        $view = $controller->View();
        if ($view->getAssign('sUserData')['billingaddress']['company'] === null) {
            return;
        }

        $errorMessages = [];
        $requiredButEmpty = false;

        if ($session->offsetExists('vatIdValidationStatus')) {
            $serialized = $session->get('vatIdValidationStatus');

            $result = new VatIdValidatorResult($this->snippetManager);
            $result->unserialize($serialized);
            $session->offsetUnset('vatIdValidationStatus');

            $errorMessages = $result->getErrorMessages();
        }

        if ($errorMessages) {
            $requiredButEmpty = array_key_exists('required', $errorMessages);
            unset($errorMessages['required']);
        }

        $required = (bool) $this->config->get('vatcheckrequired');
        $displayMessage = $required ? $this->hasExceptedEUCountries() : false;

        $view->assign(
            [
                'displayMessage' => $displayMessage,
                'vatIdCheck' => [
                    'errorMessages' => array_values($errorMessages),
                    'required' => $required,
                    'requiredButEmpty' => $requiredButEmpty,
                ],
            ]
        );
    }

    /**
     * Returns true, if there are valid EU countries excepted from the input requirement
     *
     * @return bool
     */
    private function hasExceptedEUCountries()
    {
        /** @var array|string $ISOs */
        $ISOs = $this->config->get('disabledCountryISOs');

        if (is_string($ISOs)) {
            $ISOs = explode(',', $ISOs);
            $ISOs = array_map('trim', $ISOs);
        }

        return EUStates::hasValidEUCountry($ISOs);
    }
}
