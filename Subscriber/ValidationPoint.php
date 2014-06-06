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

use Shopware\Plugins\SwagVatIdValidation\Components\Validators\VatIdValidatorInterface;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\DummyVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\SimpleBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\ExtendedBffVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\SimpleMiasVatIdValidator;
use Shopware\Plugins\SwagVatIdValidation\Components\Validators\ExtendedMiasVatIdValidator;


use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidationStatus;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;

use Enlight\Event\SubscriberInterface;

use Shopware\Models\Customer\Billing;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult2;

/**
 * This example is going to show how to test your methods without global shopware state
 *
 * Class Account
 * @package Shopware\Plugins\SwagScdExample\Subscriber
 */
abstract class ValidationPoint implements SubscriberInterface
{
    public static $action;

    /** @var  \Enlight_Config */
    protected $config;

    /** @var  \Shopware\Models\Customer\BillingRepository */
    private $billingRepository;

    public function __construct($config, $action)
    {
        $this->config = $config;
        self::$action = $action;
    }

    /**
     * Helper function to get the BillingRepository
     * @return \Shopware\Models\Customer\BillingRepository
     */
    protected function getBillingRepository()
    {
        if (!$this->billingRepository) {
            $this->billingRepository = Shopware()->Models()->getRepository('\Shopware\Models\Customer\Billing');
        }

        return $this->billingRepository;
    }

    /**
     * @param \Enlight_Event_EventArgs $arguments
     * @return array|mixed
     */
    public function onValidateStep2FilterResult(\Enlight_Event_EventArgs $arguments)
    {
        $post = $arguments->getPost();
        $errors = $arguments->getReturn();

        $result = $this->validate(
            $post['register']['billing']['ustid'],
            $post['register']['billing']['company'],
            $post['register']['billing']['street'],
            $post['register']['billing']['zipcode'],
            $post['register']['billing']['city']
        );

        $errors = array(
            array_merge($result->getErrors(), $errors[0]),
            array_merge(array(), $errors[1])
        );

        $session = Shopware()->Session();
        $session->offsetSet('vatIdValidationStatus', $result->getStatus());

        return $errors;
    }

    /**
     * Helper function for the whole validation process
     * If billing Id is set, the matching customer billing address will be removed if validation result is invalid
     * @param $vatId
     * @param $company
     * @param $street
     * @param $zipCode
     * @param $city
     * @param null $billingId
     * @return VatIdValidatorResult
     */
    public function validate($vatId, $company, $street, $zipCode, $city, $billingId = null)
    {
        //Dummy validation
        $customerInformation = new VatIdCustomerInformation($vatId, $company, $street, $zipCode, $city);
        $result = $this->validateWithDummyValidator($customerInformation);

        if (!$result->isValid()) {
            //show error, remove VAT ID, send e-mail
            $this->evaluateValidatorResult($billingId, $customerInformation, $result);
            return $result;
        }

        //API validation
        $shopInformation = new VatIdInformation($this->config->get('vatId'));
        $result = $this->validateWithApiValidator($customerInformation, $shopInformation);

        if (!$result->isValid()) {
            //show error, remove VAT ID, send e-mail
            $this->evaluateValidatorResult($billingId, $customerInformation, $result);
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
        $validator = new DummyVatIdValidator();
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
            return new VatIdValidatorResult(VatIdValidationStatus::VALID);
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
            return new SimpleMiasVatIdValidator();
        }

        if ($shopCountryCode !== 'DE') {
            return new SimpleMiasVatIdValidator();
        }

        return new SimpleBffVatIdValidator();
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
            return new ExtendedMiasVatIdValidator();
        }

        if ($shopCountryCode !== 'DE') {
            return new ExtendedMiasVatIdValidator();
        }

        return new ExtendedBffVatIdValidator($this->config->get('confirmation'));
    }

    /**
     * Helper function to evaluate the validator result
     * @param $billingId
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdValidatorResult $result
     */
    private function evaluateValidatorResult($billingId, VatIdCustomerInformation $customerInformation, VatIdValidatorResult $result)
    {
        $this->removeVatIdFromBilling($billingId);
        $this->sendShopOwnerEmail($customerInformation, $result);
    }

    /**
     * Helper function to remove the customer billing address
     * @param $billingId
     */
    private function removeVatIdFromBilling($billingId)
    {
        if (empty($billingId)) {
            return;
        }

        /** @var Billing $billing */
        $billing = $this->getBillingRepository()->findOneById($billingId);
        $billing->setVatId('');

        Shopware()->Models()->persist($billing);
        Shopware()->Models()->flush();
    }

    /**
     * Helper function to send an email to the shop owner, informing him about an invalid Vat Id
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdValidatorResult $result
     */
    private function sendShopOwnerEmail(VatIdCustomerInformation $customerInformation, VatIdValidatorResult $result)
    {
        $email = $this->config->get('shopEmailNotification');

        if (empty($email)) {
            return;
        }

        $context = array(
            'sVatId' => $customerInformation->getVatId(),
            'sCompany' => $customerInformation->getCompany(),
            'sStreet' => $customerInformation->getStreet(),
            'sZipCode' => $customerInformation->getZipCode(),
            'sCity' => $customerInformation->getCity(),
            'sError' => implode("\n", $result->getErrors())
        );

        $mail = Shopware()->TemplateMail()->createMail('sSWAGVATIDVALIDATION_VALIDATIONERROR', $context);
        $mail->addTo($email);
        $mail->send();
    }
}