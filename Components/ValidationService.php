<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components;

use Monolog\Logger;
use Psr\Log\LogLevel;
use Shopware\Components\Logger as PluginLogger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use Shopware_Components_Config as ShopwareConfig;
use Shopware_Components_Snippet_Manager as SnippetManager;
use Shopware_Components_TemplateMail as TemplateMail;
use SwagVatIdValidation\Components\Validators\ValidatorFactoryInterface;
use SwagVatIdValidation\Subscriber\AddressSubscriber;
use SwagVatIdValidation\Subscriber\Template;

class ValidationService implements ValidationServiceInterface
{
    /**
     * @var ShopwareConfig
     */
    protected $config;

    /**
     * @var SnippetManager
     */
    private $snippetManager;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var TemplateMail
     */
    private $templateMail;

    /**
     * @var PluginLogger
     */
    private $pluginLogger;

    /**
     * @var ValidatorFactoryInterface
     */
    private $validatorFactory;

    /**
     * @var DependencyProvider
     */
    private $dependencyProvider;

    public function __construct(
        ShopwareConfig $config,
        SnippetManager $snippetManager,
        ModelManager $modelManager,
        TemplateMail $templateMail,
        PluginLogger $pluginLogger,
        ValidatorFactoryInterface $validatorFactory,
        DependencyProvider $dependencyProvider
    ) {
        $this->config = $config;
        $this->snippetManager = $snippetManager;
        $this->modelManager = $modelManager;
        $this->templateMail = $templateMail;
        $this->pluginLogger = $pluginLogger;
        $this->validatorFactory = $validatorFactory;
        $this->dependencyProvider = $dependencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isVatIdRequired($company, $countryId)
    {
        /*
         * There is no VAT Id required, if...
         * ... the Vat Id is not required in the config,
         */
        if (!$this->config->get('vatcheckrequired')
            && !$this->config->get(VatIdConfigReaderInterface::IS_VAT_ID_REQUIRED)) {
            return false;
        }

        /*
         * ... the customer is not a company,
         */
        if (!$company) {
            return false;
        }

        $countryISO = $this->getCountryIso((int) $countryId);

        /**
         * ... or the check is disabled for the billing country.
         */
        $disabledCountries = $this->config->get(VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST);

        if (\is_string($disabledCountries)) {
            $disabledCountries = \explode(',', $disabledCountries);
            $disabledCountries = \array_map('trim', $disabledCountries);
        }

        if (\in_array($countryISO, $disabledCountries, true)) {
            return false;
        }

        /*
         * ... the billing country is a non-EU-country
         */
        if (!EUStates::isEUCountry($countryISO)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function validateVatId(Address $billingAddress, $deleteVatIdFromAddress = true)
    {
        /**
         * Step 1: Dummy validation
         * The dummy validator checks if the VAT Id COULD be valid. Empty VAT Ids are also okay.
         * The validator fails when:
         * - VAT ID is shorter than 4 or longer than 14 chars
         * - Country Code includes non-alphabetical chars
         * - VAT Number includes non-alphanumerical chars
         * - VAT Number only has alphabetical chars
         */
        $customerInformation = new VatIdCustomerInformation($billingAddress);
        $result = $this->validateWithDummyValidator($customerInformation);

        /*
         * If the VAT Id can't be valid, the API validation can be skipped. If the VAT Id belongs to a billing address,
         * the VAT Id will be removed from it and an email will optionally be sent to the shop owner.
         */
        if (!$result->isValid()) {
            $this->sendShopOwnerEmail($customerInformation, $result);
            $this->removeVatIdFromBilling($billingAddress->getId(), $result, $deleteVatIdFromAddress);

            return $result;
        }

        $apiValidationType = (int) $this->config->get(VatIdConfigReaderInterface::API_VALIDATION_TYPE);

        if ($apiValidationType === APIValidationType::NONE) {
            return $result;
        }

        /**
         * Get all whitelisted country ISOs from the plugin config
         */
        $exceptedNonEuISOs = $this->config->get(VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST);

        if (!\is_array($exceptedNonEuISOs)) {
            $exceptedNonEuISOs = \explode(',', $exceptedNonEuISOs);
        }
        $exceptedNonEuISOs = \array_map('trim', $exceptedNonEuISOs);

        /*
         * If the country code is whitelisted skip validation
         */
        if (\in_array($customerInformation->getCountryCode(), $exceptedNonEuISOs, true)) {
            return $result;
        }

        /**
         * Step 2: API validation
         * There are two API validators, both with two validation methods:
         *
         * Simple Bff Validator:
         * - will be used when shop VAT-ID is german, customer VAT-ID is foreign and extended check is disabled
         * - checks only the VAT-ID
         * - returns a detailed error message, if the VAT-Id is invalid
         *
         * Extended Bff Validator:
         * - will be used when shop VAT-ID is german, customer VAT-ID is foreign and extended check is enabled
         * - checks the VAT-ID and additionally company, steet and steetnumber, zipcode and city
         * - returns a detailed error message, if the VAT-Id is invalid
         * - the API itself checks the address data
         * - furthermore an official mail confirmation can be ordered
         *
         * Simple Mias Validator:
         * - will be used when shop VAT-ID is foreign or customer VAT-ID is german. Extended check is disabled.
         * - checks only the VAT-ID
         * - returns an error message, if the VAT-Id is invalid
         *
         * Extended Mias Validator:
         * - will be used when shop VAT-ID is foreign or customer VAT-ID is german. Extended check is enabled.
         * - checks the VAT-ID and additionally company, street and street number, zip code and city
         * - returns an error message, if the VAT-Id is invalid
         * - the API itself doesn't check the address data, the validator class does it manually
         * - an official mail confirmation can't be ordered
         *
         *
         * Each validator connects to an external API. If the API is not available, the result will be false.
         * The customer VAT Id has not to be empty. Otherwise the result will also be false!
         */
        $shopInformation = new VatIdInformation($this->config->get(VatIdConfigReaderInterface::VAT_ID, ''));
        $result = $this->validateWithApiValidator($customerInformation, $shopInformation, $apiValidationType);

        /*
         * If the VAT Id or the billing address is invalid or the API service is not available ...
         */
        if (!$result->isValid()) {
            /*
             * ... send a mail to the shop owner
             */
            $this->sendShopOwnerEmail($customerInformation, $result);

            /*
             * ... if the api was unavailable return the result, ...
             */
            if ($result->isApiUnavailable()) {
                return $this->handleApiIsUnavailable($billingAddress, $result, $deleteVatIdFromAddress);
            }

            /*
             * ... otherwise also the VAT Id has to be removed from the billing address.
             */
            $this->removeVatIdFromBilling($billingAddress->getId(), $result, $deleteVatIdFromAddress);
        }

        /*
         * The returned result includes a status code, the error messages and error flags
         */
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirementErrorResult()
    {
        $result = new VatIdValidatorResult($this->snippetManager, 'main', $this->config);
        $result->setVatIdRequired();

        return $result;
    }

    /**
     * Helper function to get the country iso of the given billing address
     */
    private function getCountryIso(int $countryId): ?string
    {
        $country = $this->modelManager->getRepository(Country::class)->find($countryId);
        if (!$country instanceof Country) {
            return null;
        }

        return $country->getIso();
    }

    /**
     * Helper function to check a VAT Id with the dummy validator
     */
    private function validateWithDummyValidator(VatIdCustomerInformation $customerInformation): VatIdValidatorResult
    {
        $dummyValidator = $this->validatorFactory->createDummyValidator();

        return $dummyValidator->check($customerInformation, new VatIdInformation($customerInformation->getVatId()));
    }

    /**
     * Helper function to check a VAT Id with an API Validator (Bff or Mias, simple or extended)
     */
    private function validateWithApiValidator(
        VatIdCustomerInformation $customerInformation,
        VatIdInformation $shopInformation,
        int $validationType
    ): VatIdValidatorResult {
        // an empty Vat Id will occur an error, so the api validation should be skipped
        if ($customerInformation->getVatId() === '') {
            return new VatIdValidatorResult($this->snippetManager, '', $this->config);
        }

        $validator = $this->validatorFactory->getValidator(
            $customerInformation->getCountryCode(),
            $shopInformation->getCountryCode(),
            $validationType
        );

        return $validator->check($customerInformation, $shopInformation);
    }

    /**
     * Helper function to remove the VAT Id from the customer billing address
     */
    private function removeVatIdFromBilling(?int $billingAddressId, VatIdValidatorResult $result, bool $deleteVatIdFromAddress): void
    {
        if (!$deleteVatIdFromAddress || empty($billingAddressId)) {
            return;
        }

        $billingAddress = $this->modelManager->getRepository(Address::class)->find($billingAddressId);

        if (!$billingAddress instanceof Address) {
            return;
        }

        $billingAddress->setVatId(null);

        $this->modelManager->persist($billingAddress);
        $this->modelManager->flush($billingAddress);

        $country = $billingAddress->getCountry();
        if (!$country instanceof Country) {
            return;
        }

        $company = $billingAddress->getCompany();
        if ($company === null) {
            return;
        }

        if ($this->isVatIdRequired($company, $country->getId())) {
            $result->setVatIdRequired();
        }
    }

    /**
     * Helper function to send an email to the shop owner, informing him about an invalid Vat Id
     */
    private function sendShopOwnerEmail(VatIdCustomerInformation $customerInformation, VatIdValidatorResult $result): void
    {
        if ($customerInformation->getCompany() === null) {
            return;
        }

        $email = $this->getEmailAddress();

        if (empty($email)) {
            return;
        }

        if ($result->isApiUnavailable()) {
            $error = $result->getErrorMessage('messages/checkNotAvailable');
            $this->pluginLogger->log(LogLevel::ERROR, (string) $error);
        } else {
            $error = \implode("\n", $result->getErrorMessages());
        }

        $context = [
            'sVatId' => $customerInformation->getVatId(),
            'sCompany' => $customerInformation->getCompany(),
            'sStreet' => $customerInformation->getStreet(),
            'sZipCode' => $customerInformation->getZipCode(),
            'sCity' => $customerInformation->getCity(),
            'sCountryCode' => $customerInformation->getBillingCountryIso(),
            'sError' => $error,
        ];

        try {
            $mail = $this->templateMail->createMail('sSWAGVATIDVALIDATION_VALIDATIONERROR', $context);
            $mail->addTo($email);
            $mail->setFrom($this->config->get('sMAIL'), $this->config->get('sSHOPNAME'));
            $mail->send();
        } catch (\Exception $e) {
            $this->pluginLogger->log(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * Helper function returns the configured email address or false if deactivated or invalid
     *
     * @return false|string
     */
    private function getEmailAddress()
    {
        $emailNotification = $this->config->get(VatIdConfigReaderInterface::EMAIL_NOTIFICATION);

        if (\is_string($emailNotification)) {
            $emailAddress = $emailNotification;
        } elseif ($emailNotification) {
            $emailAddress = $this->config->get('sMAIL');
        } else {
            return false;
        }

        return \filter_var($emailAddress, \FILTER_VALIDATE_EMAIL);
    }

    private function handleApiIsUnavailable(Address $billingAddress, VatIdValidatorResult $result, bool $deleteVatIdFromAddress): VatIdValidatorResult
    {
        if (!$this->isFrontendRequest()) {
            return $result;
        }

        $allowRegisterOnApiError = (bool) $this->config->get(VatIdConfigReaderInterface::ALLOW_REGISTER_ON_API_ERROR);
        $session = $this->dependencyProvider->getSession();

        if ($allowRegisterOnApiError === true) {
            $this->removeVatIdFromBilling($billingAddress->getId(), $result, $deleteVatIdFromAddress);

            $session->offsetSet(AddressSubscriber::DELETE_VAT_ID_SESSION_FLAG, true);

            return $result;
        }

        $session->offsetSet(Template::REMOVE_ERROR_FIELDS_MESSAGE, true);

        return $result;
    }

    private function isFrontendRequest(): bool
    {
        $frontController = $this->dependencyProvider->getFront();
        if (!$frontController instanceof \Enlight_Controller_Front) {
            return false;
        }

        $request = $frontController->Request();
        if ($request === null) {
            return false;
        }

        if ($request->getModuleName() === 'backend') {
            return false;
        }

        return true;
    }
}
