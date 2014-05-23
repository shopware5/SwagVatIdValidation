<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

abstract class MiasVatIdValidator implements VatIdValidatorInterface
{

    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);


    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        try
        {
            $client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
        } catch (\SoapFault $error) {
            // Verbindungsfehler, WSDL-Datei nicht erreichbar (passiert manchmal)
            return new VatIdValidatorResult(VatIdValidatorResult::UNAVAILABLE);
        }

        $data = $this->getData($customerInformation, $shopInformation);

        try {
            $result = $client->checkVatApprox($data);

            if ($result->valid == true) {
                // USt-ID ist gültig
                return new VatIdValidatorResult(VatIdValidatorResult::VALID);
            } else {
                // USt-ID ist ungültig
                return new VatIdValidatorResult(VatIdValidatorResult::INVALID, array('ungültig'));
            }
        } catch (\SoapFault $error) {
            return new VatIdValidatorResult(VatIdValidatorResult::INVALID, array($error->faultstring));
        }
    }
}