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
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidationStatus;
use Shopware\Plugins\SwagVatIdValidation\Components\VatIdValidatorResult;

abstract class MiasVatIdValidator implements VatIdValidatorInterface
{
    /**
     * The Mias validator (http://evatr.bff-online.de) will be used in each case, the bff validator will not be used.
     * When you request a qualified confirmation request, it returns in some cases the address data of the requested Vat ID.
     * Some countries (like Germany) does not return the address data, so there will only be a simple request.
     * If the address data was returned the extended validation checks the similarity to users inputted address data
     */

    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);
    abstract protected function addExtendedResults(VatIdValidatorResult $result, $response, VatIdCustomerInformation $customerInformation);

    /**
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/miasValidator');

        try {
            $last = error_reporting(0);
            $client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
            error_reporting($last);
        } catch (\SoapFault $error) {
            // Verbindungsfehler, WSDL-Datei nicht erreichbar (passiert manchmal)
            return new VatIdValidatorResult();
        }

        $data = $this->getData($customerInformation, $shopInformation);

        try {
            $response = $client->checkVatApprox($data);

            if ($response->valid == true) {
                // USt-ID ist gültig
                $result = new VatIdValidatorResult(VatIdValidationStatus::VAT_ID_VALID);
                $this->addExtendedResults($result, $response, $customerInformation);
                return $result;
            }

            // USt-ID ist ungültig
            $errorMessage = $snippets->get('error1');
            return new VatIdValidatorResult(VatIdValidationStatus::INVALID, array('vatId' => $errorMessage));
        } catch (\SoapFault $error) {
            $errorMessage = strtoupper($error->faultstring);
            if (in_array($errorMessage, array('SERVICE_UNAVAILABLE', 'MS_UNAVAILABLE', 'TIMEOUT', 'SERVER_BUSY'))) {
                return new VatIdValidatorResult();
            }

            $errorMessage = $snippets->get('error2');
            return new VatIdValidatorResult(VatIdValidationStatus::INVALID, array('vatId' => $errorMessage));
        }
    }
}