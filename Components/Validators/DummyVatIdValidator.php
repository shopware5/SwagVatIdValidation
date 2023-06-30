<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components\Validators;

use SwagVatIdValidation\Components\EUStates;
use SwagVatIdValidation\Components\VatIdConfigReaderInterface;
use SwagVatIdValidation\Components\VatIdCustomerInformation;
use SwagVatIdValidation\Components\VatIdInformation;
use SwagVatIdValidation\Components\VatIdValidatorResult;

/**
 * Dummy validation
 * The dummy validator checks if the VAT ID could be valid. Empty VAT IDs are also okay.
 * The validator fails when:
 * - VAT ID is shorter than 4 or longer than 14 chars
 * - Country Code includes non-alphabetical chars
 * - VAT Number includes non-alphanumerical chars
 * - VAT Number only has alphabetical chars
 */
class DummyVatIdValidator implements VatIdValidatorInterface
{
    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * Constructor sets the snippet namespace
     */
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager, \Shopware_Components_Config $config)
    {
        $this->snippetManager = $snippetManager;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function check(VatIdCustomerInformation $customerInformation, VatIdInformation $shopInformation = null)
    {
        $result = new VatIdValidatorResult($this->snippetManager, 'dummyValidator', $this->config);

        $exceptedNonEuISOs = $this->config->get(VatIdConfigReaderInterface::DISABLED_COUNTRY_ISO_LIST);

        if (!\is_array($exceptedNonEuISOs)) {
            $exceptedNonEuISOs = \explode(',', $exceptedNonEuISOs);
        }
        $exceptedNonEuISOs = \array_map('trim', $exceptedNonEuISOs);

        $isExcepted = \in_array($customerInformation->getBillingCountryIso(), $exceptedNonEuISOs, true);

        // An empty VAT Id can't be valid
        if ($customerInformation->getVatId() === '') {
            // Set the error code to 1 to avoid vatIds with only a "."
            $result->setVatIdInvalid('1');

            return $result;
        }

        // If there is a VAT Id for a Non-EU-countries, its invalid
        if (!EUStates::isEUCountry($customerInformation->getBillingCountryIso()) && !$isExcepted) {
            $result->setVatIdInvalid('5');
            $result->setCountryInvalid();

            return $result;
        }

        // All VAT IDs have a length of 4 to 14 chars (romania has a min. length of 4 characters)
        if (\strlen($customerInformation->getVatId()) < 4) {
            $result->setVatIdInvalid('1');
        } elseif (\strlen($customerInformation->getVatId()) > 14) {
            $result->setVatIdInvalid('2');
        }

        $isExcepted = \in_array($customerInformation->getCountryCode(), $exceptedNonEuISOs, true);

        // The country code has to be an EU prefix and has to match the billing country
        if (!EUStates::isEUCountry($customerInformation->getCountryCode()) && !$isExcepted) {
            $result->setVatIdInvalid('3');
        } elseif ($customerInformation->getCountryCode() !== $customerInformation->getBillingCountryIso()) {
            // The country greece has two different ISO codes. GR and EL
            // Since shopware does only know "GR" as "Greece", we have to manually check for "EL" here.
            if ($customerInformation->getCountryCode() !== 'EL' || $customerInformation->getBillingCountryIso() !== 'GR') {
                $result->setVatIdInvalid('6');
                $result->setCountryInvalid();
            }
        }

        // The VAT number always only consists of alphanumerical chars
        if (!\ctype_alnum($customerInformation->getVatNumber())) {
            $result->setVatIdInvalid('4');
        }

        // If the VAT number only consists alphas its invalid
        if (\ctype_alpha($customerInformation->getVatNumber())) {
            $result->setVatIdInvalid('4');
        }

        return $result;
    }
}
