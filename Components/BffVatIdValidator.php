<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

abstract class BffVatIdValidator implements VatIdValidatorInterface
{
    protected $confirmation;

    public function __construct($confirmation = false)
    {
        $this->confirmation = $confirmation;
    }

    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);

    /**
     * @param array $data
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $data = $this->getData($customerInformation, $shopInformation);

        $apiRequest = 'http://evatr.bff-online.de/evatrRPC?';
        $apiRequest .= http_build_query($data, '', '&');

        $context = stream_context_create(array('http' => array(
                'method' => 'GET',
                'header' => 'Content-Type: text/html; charset=utf-8',
                'timeout' => 5,
                'user_agent' => 'Shopware/' . Shopware()->Config()->get('sVERSION')
            )));
        $response = @file_get_contents($apiRequest, false, $context);

        $reg = '#<param>\s*<value><array><data>\s*<value><string>([^<]*)</string></value>\s*<value><string>([^<]*)</string></value>\s*</data></array></value>\s*</param>#msi';
        if (!empty($response) && preg_match_all($reg, $response, $matches)) {
            $response = array_combine($matches[1], $matches[2]);
        }

        if ($response['ErrorCode'] === '200') {
            return new VatIdValidatorResult(VatIdValidatorResult::VALID);
        }

        if (in_array($response['ErrorCode'], array(205, 208, 999))) {
            return new VatIdValidatorResult(VatIdValidatorResult::UNAVAILABLE, array(), $customerInformation, $shopInformation);
        }

        $error = Shopware()->Snippets()->getNamespace('frontend/swag_vat_id_validation/bffValidator')->get(
            'validator/bff/error' . $response['ErrorCode']
        );

        return new VatIdValidatorResult(VatIdValidatorResult::INVALID, array($error));
    }
}