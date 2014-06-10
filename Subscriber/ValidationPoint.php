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
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\VatIdValidatorInterface;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\DummyVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\SimpleBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\ExtendedBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\SimpleMiasVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\ExtendedMiasVatIdValidator;

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;

use Enlight\Event\SubscriberInterface;

use Shopware\Models\Customer\Billing;

/**
 * Class ValidationPoint
 * @package Shopware\Plugins\SwagVatIdValidation\Subscriber
 */
abstract class ValidationPoint implements SubscriberInterface
{
    /** @var  \Enlight_Config */
    protected $config;

    /** @var  \Shopware_Components_Snippet_Manager */
    private $snippetManager;

    /** @var  ModelManager */
    private $modelManager;

    /** @var  \Shopware_Components_TemplateMail */
    private $templateMail;

    /** @var  \Shopware\Models\Customer\BillingRepository */
    private $billingRepository;

    /**
     * @param \Enlight_Config $config
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param \Enlight_Components_Session_Namespace $session
     * @param ModelManager $modelManager
     * @param \Shopware_Components_TemplateMail $templateMail
     */
    public function __construct(\Enlight_Config $config, \Shopware_Components_Snippet_Manager $snippetManager, ModelManager $modelManager = null, \Shopware_Components_TemplateMail $templateMail = null)
    {
        $this->config = $config;
        $this->snippetManager = $snippetManager;
        $this->modelManager = $modelManager;
        $this->templateMail = $templateMail;
        $this->billingRepository = null;
    }

    /**
     * Helper function to get the BillingRepository
     * @return \Shopware\Models\Customer\BillingRepository
     */
    protected function getBillingRepository()
    {
        if (!$this->billingRepository) {
            $this->billingRepository = $this->modelManager->getRepository('\Shopware\Models\Customer\Billing');
        }

        return $this->billingRepository;
    }

    /**
     * Helper function for the whole validation process
     * If billing Id is set, the matching customer billing address will be removed if validation result is invalid
     * @param string $vatId
     * @param string $company
     * @param string $street
     * @param string $zipCode
     * @param string $city
     * @param integer|null $billingId
     * @return VatIdValidatorResult
     */
    public function validate($vatId, $company, $street, $zipCode, $city, $billingId = null)
    {
        //Dummy validation
        $customerInformation = new VatIdCustomerInformation($vatId, $company, $street, $zipCode, $city);
        $result = $this->validateWithDummyValidator($customerInformation);

        if (!$result->isValid()) {
            $this->removeVatIdFromBilling($billingId, $customerInformation, $result);
            return $result;
        }

        //API validation
        $shopInformation = new VatIdInformation($this->config->get('vatId'));
        $result = $this->validateWithApiValidator($customerInformation, $shopInformation);

        if (!$result->isValid()) {
            $this->removeVatIdFromBilling($billingId, $customerInformation, $result);
        }

        return $result;
    }

    /**
     * Helper function to check a VAT Id with the dummy validator
     * @param VatIdCustomerInformation $customerInformation
     * @return VatIdValidatorResult
     */
    private function validateWithDummyValidator(VatIdCustomerInformation $customerInformation)
    {
        $validator = new DummyVatIdValidator($this->snippetManager);
        $result = $validator->check($customerInformation);

        return $result;
    }

    /**
     * Helper function to check a VAT Id with an API Validator (Bff or Mias, simple or extended)
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    private function validateWithApiValidator(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        if ($customerInformation->getVatId() === '') {
            return new VatIdValidatorResult();
        }

        $validator = $this->createValidator($customerInformation->getCountryCode(), $shopInformation->getCountryCode());
        $result = $validator->check($customerInformation, $shopInformation);

        return $result;
    }

    /**
     * Helper function to get the correct validator
     * @param string $customerCountryCode
     * @param string $shopCountryCode
     * @return VatIdValidatorInterface
     */
    private function createValidator($customerCountryCode, $shopCountryCode)
    {
        if ($this->config->get('extendedCheck')) {
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
     * @param string $customerCountryCode
     * @param string $shopCountryCode
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
     * @param integer $billingId
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdValidatorResult $result
     */
    private function removeVatIdFromBilling($billingId, VatIdCustomerInformation $customerInformation, VatIdValidatorResult $result)
    {
        if (empty($billingId)) {
            return;
        }

        /** @var Billing $billing */
        $billing = $this->getBillingRepository()->findOneById($billingId);
        $billing->setVatId('');

        $this->modelManager->persist($billing);
        $this->modelManager->flush();

        $this->sendShopOwnerEmail($customerInformation, $result);
    }

    /**
     * Helper function to send an email to the shop owner, informing him about an invalid Vat Id
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdValidatorResult $result
     */
    private function sendShopOwnerEmail(VatIdCustomerInformation $customerInformation, VatIdValidatorResult $result)
    {
        $email = $this->config->get('shopEmailNotification');
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if (empty($email)) {
            return;
        }

        $context = array(
            'sVatId' => $customerInformation->getVatId(),
            'sCompany' => $customerInformation->getCompany(),
            'sStreet' => $customerInformation->getStreet(),
            'sZipCode' => $customerInformation->getZipCode(),
            'sCity' => $customerInformation->getCity(),
            'sError' => implode("\n", $result->getErrorMessages())
        );

        $mail = $this->templateMail->createMail('sSWAGVATIDVALIDATION_VALIDATIONERROR', $context);
        $mail->addTo($email);
        $mail->send();
    }
}