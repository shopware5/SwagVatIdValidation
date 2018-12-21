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

namespace SwagVatIdValidation\Components;

use Monolog\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\Repository as CountryRepository;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\AddressRepository;
use Shopware_Components_Config as ShopwareConfig;
use Shopware_Components_Snippet_Manager as SnippetManager;
use Shopware_Components_TemplateMail as TemplateMail;
use SwagVatIdValidation\Components\Validators\DummyVatIdValidator;
use SwagVatIdValidation\Components\Validators\ExtendedBffVatIdValidator;
use SwagVatIdValidation\Components\Validators\ExtendedMiasVatIdValidator;
use SwagVatIdValidation\Components\Validators\SimpleBffVatIdValidator;
use SwagVatIdValidation\Components\Validators\SimpleMiasVatIdValidator;
use SwagVatIdValidation\Components\Validators\VatIdValidatorInterface;

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
     * @var AddressRepository
     */
    private $billingRepository;

    /**
     * @var CountryRepository
     */
    private $countryRepository;

    /**
     * @var string
     */
    private $countryIso;

    /**
     * Constructor sets all properties
     *
     * @param ShopwareConfig $config
     * @param SnippetManager $snippetManager
     * @param ModelManager   $modelManager
     * @param TemplateMail   $templateMail
     */
    public function __construct(
        ShopwareConfig $config,
        SnippetManager $snippetManager,
        ModelManager $modelManager,
        TemplateMail $templateMail
    ) {
        $this->config = $config;
        $this->snippetManager = $snippetManager;
        $this->modelManager = $modelManager;
        $this->templateMail = $templateMail;
        $this->countryIso = '';
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
        if (!$this->config->get('vatcheckrequired')) {
            return false;
        }

        /*
         * ... the customer is not a company,
         */
        if (!$company) {
            return false;
        }

        $countryISO = $this->getCountryIso($countryId);

        /**
         * ... or the check is disabled for the billing country.
         */
        $disabledCountries = $this->config->get('disabledCountryISOs');

        if (is_string($disabledCountries)) {
            $disabledCountries = explode(',', $disabledCountries);
            $disabledCountries = array_map('trim', $disabledCountries);
        }

        if (in_array($countryISO, $disabledCountries, true)) {
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

        $apiValidationType = $this->config->get('apiValidationType');

        if ($apiValidationType === APIValidationType::NONE) {
            return $result;
        }

        /**
         * Get all whitelisted country ISOs from the plugin config
         */
        $exceptedNonEuISOs = $this->config->get('disabledCountryISOs');

        if (!is_array($exceptedNonEuISOs)) {
            $exceptedNonEuISOs = explode(',', $exceptedNonEuISOs);
        }
        $exceptedNonEuISOs = array_map('trim', $exceptedNonEuISOs);

        /*
         * If the country code is whitelisted skip validation
         */
        if (in_array($customerInformation->getCountryCode(), $exceptedNonEuISOs, true)) {
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
        $shopInformation = new VatIdInformation($this->config->get('vatId'));
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
                return $result;
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
        $result = new VatIdValidatorResult($this->snippetManager, 'main');
        $result->setVatIdRequired();

        return $result;
    }

    /**
     * Helper function to get the address repository
     *
     * @return AddressRepository
     */
    private function getAddressRepository()
    {
        if (!$this->billingRepository) {
            $this->billingRepository = $this->modelManager->getRepository(Address::class);
        }

        return $this->billingRepository;
    }

    /**
     * Helper function to get the CountryRepository
     *
     * @return CountryRepository
     */
    private function getCountryRepository()
    {
        if (!$this->countryRepository) {
            $this->countryRepository = $this->modelManager->getRepository(Country::class);
        }

        return $this->countryRepository;
    }

    /**
     * Helper function to get the country iso of the given billing address
     *
     * @param int $countryId
     *
     * @return string
     */
    private function getCountryIso($countryId)
    {
        if (!$this->countryIso) {
            /** @var Country $country */
            $country = $this->getCountryRepository()->find($countryId);
            $this->countryIso = $country->getIso();
        }

        return $this->countryIso;
    }

    /**
     * Helper function to check a VAT Id with the dummy validator
     *
     * @param VatIdCustomerInformation $customerInformation
     *
     * @return VatIdValidatorResult
     */
    private function validateWithDummyValidator(VatIdCustomerInformation $customerInformation)
    {
        return (new DummyVatIdValidator($this->snippetManager))->check($customerInformation);
    }

    /**
     * Helper function to check a VAT Id with an API Validator (Bff or Mias, simple or extended)
     *
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation         $shopInformation
     * @param int                      $validationType
     *
     * @return VatIdValidatorResult
     */
    private function validateWithApiValidator(
        VatIdCustomerInformation $customerInformation,
        VatIdInformation $shopInformation,
        $validationType
    ) {
        //an empty Vat Id will occur an error, so the api validation should be skipped
        if ($customerInformation->getVatId() === '') {
            return new VatIdValidatorResult();
        }

        $validator = $this->createValidator(
            $customerInformation->getCountryCode(),
            $shopInformation->getCountryCode(),
            $validationType
        );

        return $validator->check($customerInformation, $shopInformation);
    }

    /**
     * Helper function to get the correct simple validator
     *
     * @param string $customerCountryCode
     * @param string $shopCountryCode
     * @param int    $validationType
     *
     * @return VatIdValidatorInterface
     */
    private function createValidator($customerCountryCode, $shopCountryCode, $validationType)
    {
        if ($validationType === APIValidationType::EXTENDED) {
            return $this->createExtendedValidator($customerCountryCode, $shopCountryCode);
        }

        if ($customerCountryCode === 'DE') {
            return new SimpleMiasVatIdValidator($this->snippetManager);
        }

        if ($shopCountryCode !== 'DE') {
            return new SimpleMiasVatIdValidator($this->snippetManager);
        }

        return new SimpleBffVatIdValidator($this->snippetManager);
    }

    /**
     * Helper function to get the correct extended validator
     *
     * @param string $customerCountryCode
     * @param string $shopCountryCode
     *
     * @return VatIdValidatorInterface
     */
    private function createExtendedValidator($customerCountryCode, $shopCountryCode)
    {
        if ($customerCountryCode === 'DE') {
            return new ExtendedMiasVatIdValidator($this->snippetManager);
        }

        if ($shopCountryCode !== 'DE') {
            return new ExtendedMiasVatIdValidator($this->snippetManager);
        }

        return new ExtendedBffVatIdValidator($this->snippetManager, $this->config->get('confirmation'));
    }

    /**
     * Helper function to remove the VAT Id from the customer billing address
     *
     * @param int                  $billingAddressId
     * @param VatIdValidatorResult $result
     * @param bool                 $deleteVatIdFromAddress
     */
    private function removeVatIdFromBilling($billingAddressId, VatIdValidatorResult $result, $deleteVatIdFromAddress)
    {
        if (!$deleteVatIdFromAddress || empty($billingAddressId)) {
            return;
        }

        /** @var Address $billingAddress */
        $billingAddress = $this->getAddressRepository()->find($billingAddressId);

        if (!$billingAddress) {
            return;
        }

        $billingAddress->setVatId('');

        $this->modelManager->persist($billingAddress);
        $this->modelManager->flush($billingAddress);

        if ($this->isVatIdRequired($billingAddress->getCompany(), $billingAddress->getCountry()->getId())) {
            $result->setVatIdRequired();
        }
    }

    /**
     * Helper function to send an email to the shop owner, informing him about an invalid Vat Id
     *
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdValidatorResult     $result
     */
    private function sendShopOwnerEmail(VatIdCustomerInformation $customerInformation, VatIdValidatorResult $result)
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
        } else {
            $error = implode("\n", $result->getErrorMessages());
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
            Shopware()->Container()->get('pluginlogger')->log(Logger::ERROR, $e->getMessage());
        }
    }

    /**
     * Helper function returns the configured email address or false if deactivated or invalid
     *
     * @return bool|string
     */
    private function getEmailAddress()
    {
        $emailNotification = $this->config->get('shopEmailNotification');

        if (is_string($emailNotification)) {
            $emailAddress = $emailNotification;
        } elseif ($emailNotification) {
            $emailAddress = $this->config->get('sMAIL');
        } else {
            return false;
        }

        return filter_var($emailAddress, FILTER_VALIDATE_EMAIL);
    }
}
