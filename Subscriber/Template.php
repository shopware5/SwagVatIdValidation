<?php
/**
 * Shopware Plugins
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this plugin can be used under
 * a proprietary license as set forth in our Terms and Conditions,
 * section 2.1.2.2 (Conditions of Usage).
 *
 * The text of our proprietary license additionally can be found at and
 * in the LICENSE file you have received along with this plugin.
 *
 * This plugin is distributed in the hope that it will be useful,
 * with LIMITED WARRANTY AND LIABILITY as set forth in our
 * Terms and Conditions, sections 9 (Warranty) and 10 (Liability).
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the plugin does not imply a trademark license.
 * Therefore any rights, title and interest in our trademarks
 * remain entirely with us.
 */

namespace SwagVatIdValidation\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace as Session;
use Enlight_Controller_Action;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Controller_EventArgs as ControllerEventArgs;
use Enlight_Controller_Request_RequestHttp as Request;
use Enlight_View_Default as View;
use SwagVatIdValidation\Components\DependencyProviderInterface;
use SwagVatIdValidation\Components\EUStates;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Components\VatIdValidatorResult;

class Template implements SubscriberInterface
{
    public const REMOVE_ERROR_FIELDS_MESSAGE = 'removeRedFields';

    private const ERROR_ORIGIN_REGISTER = 'register';

    private const ERROR_ORIGIN_EDIT = 'edit';

    private const ERROR_ORIGIN_UNDEFINED = 'undefined';

    /**
     * @var string
     */
    private $errorOrigin = self::ERROR_ORIGIN_UNDEFINED;

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
     * @param string $pluginPath
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
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Address' => 'onPostDispatchFrontendAddressEdit',
            'Theme_Inheritance_Template_Directories_Collected' => 'onTemplatesCollected',
        ];
    }

    /**
     * Listener to FrontendAccount (index and billing)
     * On Account Index, a short info message will be shown if the validator was not available
     * On Account Billing, the Vat Id input field can be set required
     */
    public function onPostDispatchFrontendAccount(ActionEventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), ['index', 'billing']);
    }

    /**
     * Listener to FrontendCheckout (confirm),
     * Shows a short info message if the validator was not available
     */
    public function onPostDispatchFrontendCheckout(ActionEventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), ['confirm']);
    }

    /**
     * Listener to FrontendRegister (index)
     * The Vat Id input field can be set required
     */
    public function onPostDispatchFrontendRegister(ActionEventArgs $arguments)
    {
        $this->postDispatchFrontendController($arguments->getSubject(), ['index']);
    }

    public function onPostDispatchFrontendAddressEdit(ControllerEventArgs $args): void
    {
        /** @var \Shopware_Controllers_Frontend_Address $subject */
        $subject = $args->getSubject();

        $this->prepareBillingErrorMessages($session = $this->dependencyProvider->getSession(), $subject->View());
    }

    public function onTemplatesCollected(\Enlight_Event_EventArgs $arguments)
    {
        $dirs = $arguments->getReturn();

        $dirs[] = $this->pluginPath . '/Resources/views';

        $arguments->setReturn($dirs);
    }

    /**
     * Helper function to assign the plugin data to the template
     *
     * @param string[] $actions
     */
    public function postDispatchFrontendController(Enlight_Controller_Action $controller, array $actions)
    {
        /** @var Request $request */
        $request = $controller->Request();

        if (!\in_array($request->getActionName(), $actions, true)) {
            return;
        }

        /** @var Session $session */
        $session = $this->dependencyProvider->getSession();

        /** @var View $view */
        $view = $controller->View();

        $this->prepareBillingErrorMessages($session, $view);

        if ($view->getAssign('sUserData')['billingaddress']['company'] === null) {
            return;
        }

        $errorMessages = [];
        $requiredButEmpty = false;

        if ($session->offsetExists('vatIdValidationStatus')) {
            $serialized = $session->get('vatIdValidationStatus');

            $result = new VatIdValidatorResult($this->snippetManager, '', $this->config);
            $result->unserialize($serialized);
            $session->offsetUnset('vatIdValidationStatus');

            $errorMessages = $result->getErrorMessages();
        }

        if ($errorMessages) {
            $requiredButEmpty = \array_key_exists('required', $errorMessages);
            unset($errorMessages['required']);
        }

        $required = (bool) $this->config->get('vatcheckrequired');
        $displayMessage = $required ? $this->hasExceptedEUCountries() : false;

        $view->assign(
            [
                'displayMessage' => $displayMessage,
                'vatIdCheck' => [
                    'errorMessages' => \array_values($errorMessages),
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
        $ISOs = $this->config->get(VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST);

        if (\is_string($ISOs)) {
            $ISOs = \explode(',', $ISOs);
            $ISOs = \array_map('trim', $ISOs);
        }

        return EUStates::hasValidEUCountry($ISOs);
    }

    private function prepareBillingErrorMessages(Session $session, View $view): void
    {
        if (!$session->offsetGet(self::REMOVE_ERROR_FIELDS_MESSAGE)) {
            return;
        }

        $session->offsetUnset(self::REMOVE_ERROR_FIELDS_MESSAGE);

        $assignedErrors = $this->getAssignedErrors($view);

        if ($this->errorOrigin === self::ERROR_ORIGIN_REGISTER) {
            $this->handleRegister($assignedErrors, $view);

            return;
        }

        if ($this->errorOrigin === self::ERROR_ORIGIN_EDIT) {
            $this->handleEdit($assignedErrors, $view);
        }
    }

    private function getAssignedErrors(View $view): array
    {
        $assignedErrors = $view->getAssign('errors');
        if ($assignedErrors !== null) {
            $this->errorOrigin = self::ERROR_ORIGIN_REGISTER;

            return $assignedErrors;
        }

        $assignedErrors = $view->getAssign('error_messages');
        if ($assignedErrors !== null) {
            $this->errorOrigin = self::ERROR_ORIGIN_EDIT;

            return $assignedErrors;
        }

        return [];
    }

    private function handleEdit(array $assignedErrors, View $view): void
    {
        if (\count($assignedErrors) < 2) {
            return;
        }
        array_shift($assignedErrors);
        $this->errorOrigin = self::ERROR_ORIGIN_UNDEFINED;

        $view->assign('error_messages', $assignedErrors);
    }

    private function handleRegister(?array $assignedErrors, View $view): void
    {
        if (!isset($assignedErrors['billing'])) {
            return;
        }

        $billingErrorMessages = $assignedErrors['billing'];
        if (\count($billingErrorMessages) < 2) {
            return;
        }

        array_shift($billingErrorMessages);
        $assignedErrors['billing'] = $billingErrorMessages;
        $this->errorOrigin = self::ERROR_ORIGIN_UNDEFINED;

        $view->assign('errors', $assignedErrors);
    }
}
