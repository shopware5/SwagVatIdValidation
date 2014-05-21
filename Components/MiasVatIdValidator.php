<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

abstract class MiasVatIdValidator implements VatIdValidatorInterface
{

    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);


    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $client = new \SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");

        if (!$client)
        {
            // Verbindungsfehler, WSDL-Datei nicht erreichbar (passiert manchmal)
            return new VatIdValidatorResult(false, array('Verbindungsfehler'));
        }

        $data = $this->getData($customerInformation, $shopInformation);

        try {
            $result = $client->checkVatApprox($data);

            if ($result->valid == true) {
                // USt-ID ist gültig
                return new VatIdValidatorResult(true);
            } else {
                // USt-ID ist ungültig
                return new VatIdValidatorResult(false, array('ungültig'));
            }
        } catch (\SoapFault $error) {
            return new VatIdValidatorResult(false, array($error->faultstring));
        }
    }
}