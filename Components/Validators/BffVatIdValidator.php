<?php

/**
 * Shopware 5
 * Copyright (c) shopware AG
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
 * Class BffVatIdValidator
 * @package Shopware\Plugins\SwagVatIdValidation\Components\Validators
 */
abstract class BffVatIdValidator implements VatIdValidatorInterface
{
    /**
     * The Bff validator (http://evatr.bff-online.de) works only for requests from german VAT Ids for foreign VAT Ids.
     * When you request a qualified confirmation request, it returns whether an address data is correct or not.
     * Some countries (like Germany) does not return the address data, so the address data will not be checked.
     * Additionally you can order an official mail confirmation for qualified confirmation requests.
     */

    /** @var  VatIdValidatorResult */
    protected $result;

    /** @var bool */
    protected $confirmation;

    /**
     * Constructor sets the snippet namespace
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param bool $confirmation
     */
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager, $confirmation = false)
    {
        $this->result = new VatIdValidatorResult($snippetManager, 'bffValidator');
        $this->confirmation = $confirmation;
    }

    /**
     * Check process of a validator
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     * @return VatIdValidatorResult
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $data = $this->getData($customerInformation, $shopInformation);

        $apiRequest = 'http://evatr.bff-online.de/evatrRPC?';
        $apiRequest .= http_build_query($data, '', '&');

        $context = stream_context_create(
            [
                'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: text/html; charset=utf-8',
                'timeout' => 5,
                'user_agent' => 'Shopware'
                ]
            ]
        );
        $response = @file_get_contents($apiRequest, false, $context);

        $reg = '#<param>\s*<value><array><data>\s*<value><string>([^<]*)</string></value>\s*<value><string>([^<]*)</string></value>\s*</data></array></value>\s*</param>#msi';

        if (empty($response)) {
            $this->result->setServiceUnavailable();
            return $this->result;
        }

        if (preg_match_all($reg, $response, $matches)) {
            $response = array_combine($matches[1], $matches[2]);
            $this->createSimpleValidatorResult($response);
            $this->addExtendedResults($response);
        }

        return $this->result;
    }

    /**
     * Helper function to set the VAT Id result of a confirmation request
     * @param array $response
     */
    private function createSimpleValidatorResult($response)
    {
        if ($response['ErrorCode'] === '200') {
            return;
        }

        $this->result->setVatIdInvalid($response['ErrorCode']);
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
     * @param array $response
     */
    abstract protected function addExtendedResults($response);
}
