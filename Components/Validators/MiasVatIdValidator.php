<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components\Validators;

use Psr\Log\LogLevel;
use Shopware\Components\Logger as PluginLogger;
use SwagVatIdValidation\Components\VatIdCustomerInformation;
use SwagVatIdValidation\Components\VatIdInformation;
use SwagVatIdValidation\Components\VatIdValidatorResult;

abstract class MiasVatIdValidator implements VatIdValidatorInterface
{
    /**
     * The Mias validator (http://ec.europa.eu/taxation_customs/vies/vatRequest.html) will be used in each case, the bff validator will not be used.
     * When you request an extended confirmation request, it returns in some cases the address data of the requested Vat ID.
     * Some countries (like Germany) do not return the address data, so there will only be a simple request.
     * If the address data was returned the extended validation checks the similarity to users inputted address data
     */

    /**
     * @var VatIdValidatorResult
     */
    protected $result;

    /**
     * @var PluginLogger
     */
    protected $pluginLogger;

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
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager, PluginLogger $pluginLogger, \Shopware_Components_Config $config)
    {
        $this->pluginLogger = $pluginLogger;
        $this->snippetManager = $snippetManager;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation)
    {
        $this->result = new VatIdValidatorResult($this->snippetManager, 'miasValidator', $this->config);

        try {
            $client = new \SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
        } catch (\SoapFault $error) {
            $this->result->setServiceUnavailable();

            return $this->result;
        }

        $data = $this->getData($customerInformation, $shopInformation);

        try {
            $response = $client->checkVatApprox($data);

            if ((bool) $response->valid === true) {
                // Vat Id is valid
                $this->addExtendedResults($response, $customerInformation);

                return $this->result;
            }

            // Vat Id is invalid
            $this->result->setVatIdInvalid('1');

            return $this->result;
        } catch (\SoapFault $error) {
            $errorMessage = \strtoupper($error->faultstring);
            $errorTypes = [
                'GLOBAL_MAX_CONCURRENT_REQ',
                'MS_MAX_CONCURRENT_REQ',
                'SERVICE_UNAVAILABLE',
                'MS_UNAVAILABLE',
                'TIMEOUT',
                'SERVER_BUSY',
            ];

            if (\in_array($errorMessage, $errorTypes, true)) {
                $this->result->setServiceUnavailable();

                return $this->result;
            }

            $this->result->setVatIdInvalid('2');

            return $this->result;
        } catch (\Throwable $exception) {
            $this->result->setServiceUnavailable();
            $this->pluginLogger->log(LogLevel::ERROR, $exception->getMessage());

            return $this->result;
        }
    }

    /**
     * Helper function that returns an array in the format the validator needs it
     *
     * @return array{countryCode: string, vatNumber: string, traderName: string|null, traderCompanyType: string, traderPostcode: string, traderCity: string, requesterCountryCode: string, requesterVatNumber: string}
     */
    abstract protected function getData(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation);

    /**
     * Helper function to set the address data results of a qualified confirmation request
     *
     * @param object $response
     *
     * @return void
     */
    abstract protected function addExtendedResults($response, VatIdCustomerInformation $customerInformation);
}
