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


use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;

use Enlight\Event\SubscriberInterface;

use Shopware\CustomModels\SwagVatIdValidation\VatIdCheck;
use Shopware\Models\Customer\Billing;

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

    /** @var \Shopware\CustomModels\SwagVatIdValidation\Repository */
    private $vatIdCheckRepository;

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
     * Helper function to get the VatIdCheckRepository
     * @return \Shopware\CustomModels\SwagVatIdValidation\Repository
     */
    protected function getVatIdCheckRepository()
    {
        if (!$this->vatIdCheckRepository) {
            $this->vatIdCheckRepository = Shopware()->Models()->getRepository(
                '\Shopware\CustomModels\SwagVatIdValidation\VatIdCheck'
            );
        }

        return $this->vatIdCheckRepository;
    }

    public function onValidateStep2FilterResult(\Enlight_Event_EventArgs $arguments)
    {
        $post = $arguments->getPost();
        $errors = $arguments->getReturn();

        $errors = $this->validateVatId($post['register']['billing'], $errors);

        return $errors;
    }

    /**
     * Helper function includes the complete check process
     * @param array $billing
     * @param array $return
     * @return array
     */
    private function validateVatId($billing, $return = array())
    {
        if ($billing['ustid'] === '') {
            return $return;
        }

        $customer = new VatIdCustomerInformation(
            $billing['ustid'],
            $billing['company'],
            $billing['street'] . ' ' . $billing['streetnumber'],
            $billing['zipcode'],
            $billing['city']
        );

        $requester = new VatIdInformation($this->config->get('vatId'));

        $validatorResult = $this->validate($customer, $requester, true);

        $errors = $this->evaluateValidatorResult($validatorResult);

        if (!empty($errors[0])) {
            $email = $this->config->get('shopEmailNotification');
            if (!empty($email)) {
                $context = array(
                    'sVatId' => $customer->getVatId(),
                    'sError' => implode("\n", $validatorResult->getErrors())
                );

//                $this->sendMailByTemplate('VALIDATIONERROR', $email, $context);
            }
        }

        return array(array_merge($return[0], $errors[0]), array_merge($return[1], $errors[1]));
    }

    /**
     * Helper function to validate a VatId, if validator is not available, the dummy validator can be used optionally
     * @param VatIdCustomerInformation $customer
     * @param VatIdInformation $requester
     * @param bool $dummyValidation
     * @return VatIdValidatorResult
     */
    protected function validate(VatIdCustomerInformation $customer, VatIdInformation $requester, $dummyValidation = false) {
        //Get the correct validator (using an api)
        $validator = $this->createValidator($customer->getCountryCode(), $requester->getCountryCode());

        //Send the request to the validator
        $validatorResult = $validator->check($customer, $requester);

        //if dummy should not validate, return the api's validator result
        if (!$dummyValidation) {
            return $validatorResult;
        }

        //if the api service was unavailable the dummy validator checks, whether the vatId could be valid
        if ($validatorResult->serviceNotAvailable()) {
            $dummyValidator = new DummyVatIdValidator();
            $validatorResult = $dummyValidator->check($customer, $requester);
        }

        //return the validator result
        return $validatorResult;
    }

    /**
     * Helper function evaluates validators result and returns the error messages
     * @param VatIdValidatorResult $validatorResult
     * @return array
     */
    private function evaluateValidatorResult(VatIdValidatorResult $validatorResult)
    {
        $session = Shopware()->Session();
        unset($session['vatIdValidationStatus']);

        $errors = array(
            $validatorResult->getErrors(),
            array()
        );

        foreach ($errors[0] as $key => $error) {
            $key = strtolower($key);

            if ($key === 'vatid') {
                $errors[1]['ustid'] = true;
                break;
            }

            if ($key === 'street') {
                $errors[1]['streetnumber'] = true;
            }

            $errors[1][$key] = true;
        }

        if ($validatorResult->isVatIdValid() || $validatorResult->isDummyValid()) {
            $session['vatIdValidationStatus'] = $validatorResult->getStatus();
        }

        return $errors;
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
     * Helper method to save the VatIdCheck in the database and optionally remove the VatId from users billing
     * @param Billing $billing
     * @param int $status
     * @param bool $removeBillingVatId
     * @internal param string $vatId
     */

    protected function saveVatIdCheck(Billing $billing, $status = 0, $removeBillingVatId = true)
    {
        $vatIdCheck = $this->getVatIdCheckRepository()->getVatIdCheckByBillingId($billing->getId());

        if (!$vatIdCheck) {
            $vatIdCheck = new VatIdCheck();
            $vatIdCheck->setBillingAddress($billing);
        }

        $vatIdCheck->setVatId($billing->getVatId());
        $vatIdCheck->setStatus($status);

        Shopware()->Models()->persist($vatIdCheck);
        Shopware()->Models()->flush();

        if ($removeBillingVatId) {
            $billing->setVatId('');

            Shopware()->Models()->persist($billing);
            Shopware()->Models()->flush();
        }
    }
}