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

namespace Shopware\Plugins\SwagVatIdValidation\Components\Validators;

use Shopware\Plugins\SwagVatIdValidation\Components\VatIdCustomerInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdInformation;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;

/**
 * Class MiasVatIdValidator
 * @package Shopware\Plugins\SwagVatIdValidation\Components\Validators
 */
abstract class MiasVatIdValidator implements VatIdValidatorInterface
{
    /**
     * The Mias validator (http://ec.europa.eu/taxation_customs/vies/vatRequest.html) will be used in each case, the bff validator will not be used.
     * When you request an extended confirmation request, it returns in some cases the address data of the requested Vat ID.
     * Some countries (like Germany) do not return the address data, so there will only be a simple request.
     * If the address data was returned the extended validation checks the similarity to users inputted address data
     */

    /** @var  VatIdValidatorResult */
    protected $result;

    /**
     * Constructor sets the snippet namespace
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->result = new VatIdValidatorResult($snippetManager, 'miasValidator');
    }

    /**
     * Check process of a validator
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        try {
            $client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
        } catch (\SoapFault $error) {
            $this->result->setServiceUnavailable();
            return $this->result;
        }

        $data = $this->getData($customerInformation, $shopInformation);

        try {
            $response = $client->checkVatApprox($data);

            if ($response->valid == true) {
                // Vat Id is valid
                $this->addExtendedResults($response, $customerInformation);
                return $this->result;
            }

            // Vat Id is invalid
            $this->result->setVatIdInvalid('1');
            return $this->result;
        } catch (\SoapFault $error) {
            $errorMessage = strtoupper($error->faultstring);
            if (in_array($errorMessage, array('SERVICE_UNAVAILABLE', 'MS_UNAVAILABLE', 'TIMEOUT', 'SERVER_BUSY'))) {
                $this->result->setServiceUnavailable();
                return $this->result;
            }

            $this->result->setVatIdInvalid('2');
            return $this->result;
        }
    }

    /**
     * Helper function that returns an array in the format the validator needs it
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return array
     */
    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);

    /**
     * Helper function to set the address data results of a qualified confirmation request
     * @param $response
     * @param VatIdCustomerInformation $customerInformation
     */
    abstract protected function addExtendedResults($response, VatIdCustomerInformation $customerInformation);
}
