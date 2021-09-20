<?php
/**
 * Shopware Plugins
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this plugin can be used under
 * a proprietary license as set forth in our Terms and Conditions,
 * section 2.1.2.2 (Conditions of Usage).
 *
 * The text of our proprietary license additionally can be found at and
 * in the LICENSE file you have received along with this plugin.
 *
 * This plugin is distributed in the hope that it will be useful,
 * with LIMITED WARRANTY AND LIABILITY as set forth in our
 * Terms and Conditions, sections 9 (Warranty) and 10 (Liability).
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the plugin does not imply a trademark license.
 * Therefore any rights, title and interest in our trademarks
 * remain entirely with us.
 */

namespace SwagVatIdValidation\Components\Validators;

use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Components\VatIdCustomerInformation;
use SwagVatIdValidation\Components\VatIdInformation;
use SwagVatIdValidation\Components\VatIdValidatorResult;

abstract class BffVatIdValidator implements VatIdValidatorInterface
{
    /**
     * The Bff validator (http://evatr.bff-online.de) works only for requests from german VAT Ids for foreign VAT Ids.
     * When you request a qualified confirmation request, it returns whether an address data is correct or not.
     * Some countries (like Germany) does not return the address data, so the address data will not be checked.
     * Additionally you can order an official mail confirmation for qualified confirmation requests.
     */

    /**
     * @var VatIdValidatorResult
     */
    protected $result;

    /**
     * @var bool
     */
    protected $confirmation;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    protected $snippetManager;

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * Constructor sets the snippet namespace
     */
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager, \Shopware_Components_Config $config)
    {
        $this->snippetManager = $snippetManager;
        $this->config = $config;
        $this->confirmation = $this->config->get(VatIdConfigReaderInterface::OFFICIAL_CONFIRMATION);
    }

    /**
     * {@inheritdoc}
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $this->result = new VatIdValidatorResult($this->snippetManager, 'bffValidator', $this->config);

        $data = $this->getData($customerInformation, $shopInformation);

        //The bff validator api does only support 'EL' as greece iso. Therefore, we replace the original GR with the EL.
        $data['UstId_2'] = \str_replace('GR', 'EL', $data['UstId_2']);

        $apiRequest = 'https://evatr.bff-online.de/evatrRPC?';
        $apiRequest .= \http_build_query($data, '', '&');

        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: text/html; charset=utf-8',
                'timeout' => 5,
                'user_agent' => 'Shopware',
            ],
        ]);
        $response = @\file_get_contents($apiRequest, false, $context);

        $reg = '#<param>\s*<value><array><data>\s*<value><string>([^<]*)</string></value>\s*<value><string>([^<]*)</string></value>\s*</data></array></value>\s*</param>#msi';

        if (empty($response)) {
            $this->result->setServiceUnavailable();

            return $this->result;
        }

        if (\preg_match_all($reg, $response, $matches)) {
            $response = \array_combine($matches[1], $matches[2]);
            $this->createSimpleValidatorResult($response);
            $this->addExtendedResults($response);
        }

        return $this->result;
    }

    /**
     * Helper function that returns an array in the format the validator needs it
     *
     * @return array
     */
    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);

    /**
     * Helper function to set the address data results of a qualified confirmation request
     *
     * @param array $response
     */
    abstract protected function addExtendedResults($response);

    /**
     * Helper function to set the VAT Id result of a confirmation request
     *
     * @param array $response
     */
    private function createSimpleValidatorResult($response)
    {
        if ($response['ErrorCode'] === '200'
            || $response['ErrorCode'] === '222'
        ) {
            return;
        }

        $this->result->setVatIdInvalid($response['ErrorCode']);
    }
}
