<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

abstract class MiasVatIdValidator implements VatIdValidatorInterface
{

    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);
    abstract protected function addExtendedResults(VatIdValidatorResult $result, $response, VatIdCustomerInformation $customerInformation);

    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $snippets = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/miasValidator');

        try {
            $last = error_reporting(0);
            $client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
            error_reporting($last);
        } catch (\SoapFault $error) {
            // Verbindungsfehler, WSDL-Datei nicht erreichbar (passiert manchmal)
            return new VatIdValidatorResult(VatIdValidatorResult::UNAVAILABLE);
        }

        $data = $this->getData($customerInformation, $shopInformation);

        try {
            $response = $client->checkVatApprox($data);

            if ($response->valid == true) {
                // USt-ID ist gültig
                $result = new VatIdValidatorResult(VatIdValidatorResult::VALID);
                $this->addExtendedResults($result, $response, $customerInformation);
                return $result;
            }

            // USt-ID ist ungültig
            $errorMessage = $snippets->get('error1');
            return new VatIdValidatorResult(VatIdValidatorResult::INVALID, array('vatId' => $errorMessage));
        } catch (\SoapFault $error) {
            $errorMessage = strtoupper($error->faultstring);
            if (in_array($errorMessage, array('SERVICE_UNAVAILABLE', 'MS_UNAVAILABLE', 'TIMEOUT', 'SERVER_BUSY'))) {
                return new VatIdValidatorResult(VatIdValidatorResult::UNAVAILABLE);
            }

            $errorMessage = $snippets->get('error2');
            return new VatIdValidatorResult(VatIdValidatorResult::INVALID, array('vatId' => $errorMessage));
        }
    }
}