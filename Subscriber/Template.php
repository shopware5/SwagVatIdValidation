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
namespace Shopware\Plugins\SwagVatIdValidation\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Controller_Request_RequestHttp as Request;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Plugins\SwagVatIdValidation\Components\EUStates;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;

/**
 * Class TemplateExtension
 *
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
class Template implements SubscriberInterface
{
    /**
     * @var Container $container
     */
    private $container;

    /** @var string $path */
    private $path;

    /**
     * @param Container $container
     * @param string $path
     */
    public function __construct(Container $container, $path)
    {
        $this->container = $container;
        $this->path = $path;
    }

    /**
     * Returns the events we need to subscribe to
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Account' => 'onPostDispatchFrontendAccount',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchFrontendCheckout',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Register' => 'onPostDispatchFrontendRegister'
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
     * Helper function to assign the plugin data to the template
     *
     * @param Enlight_Controller_Action $controller
     * @param string[] $actions
     */
    public function postDispatchFrontendController(Enlight_Controller_Action $controller, array $actions)
    {
        /** @var Request $request */
        $request = $controller->Request();

        if (!in_array($request->getActionName(), $actions)) {
            return;
        }

        /** @var \Enlight_Components_Session_Namespace $session */
        $session = $this->container->get('session');

        /** @var $view \Enlight_View_Default */
        $view = $controller->View();

        $view->addTemplateDir($this->path . 'Views/');

        $errorMessages = [];
        $requiredButEmpty = false;

        if ($session->offsetExists('vatIdValidationStatus')) {
            $serialized = $session->get('vatIdValidationStatus');

            $result = new VatIdValidatorResult($this->container->get('snippets'));
            $result->unserialize($serialized);
            $session->offsetUnset('vatIdValidationStatus');

            $errorMessages = $result->getErrorMessages();
        }

        if ($errorMessages) {
            $requiredButEmpty = array_key_exists('required', $errorMessages);
            unset($errorMessages['required']);
        }

        $required = (bool) $this->container->get('config')->get('vatcheckrequired');
        $displayMessage = ($required) ? $this->hasExceptedEUCountries() : false;

        $view->assign(
            [
                'displayMessage' => $displayMessage,
                'vatIdCheck' => [
                    'errorMessages' => array_values($errorMessages),
                    'required' => $required,
                    'requiredButEmpty' => $requiredButEmpty
                ]
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
        $ISOs = $this->container->get('config')->get('disabledCountryISOs');

        if (is_string($ISOs)) {
            $ISOs = explode(',', $ISOs);
            $ISOs = array_map('trim', $ISOs);
        }

        return EUStates::hasValidEUCountry($ISOs);
    }
}
