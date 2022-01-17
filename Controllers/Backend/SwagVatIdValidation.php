<?php
declare(strict_types=1);

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

use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;

class Shopware_Controllers_Backend_SwagVatIdValidation extends Shopware_Controllers_Backend_ExtJs
{
    public function validateVatIdAction(): void
    {
        $address = $this->getAddress($this->Request()->getParams());

        if ($address === null) {
            $this->View()->assign([
                'success' => true,
            ]);

            return;
        }

        $vatIdValidationService = $this->get('swag_vat_id_validation.validation_service');
        $result = $vatIdValidationService->validateVatId($address, false);

        $this->View()->assign([
            'success' => $result->isValid(),
            'errors' => $result->getErrorMessages(),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getAddress(array $data): ?Address
    {
        $addressId = (int) ($data['id'] ?? 0);
        $countryId = (int) ($data['countryId'] ?? 0);
        $vatId = $data['vatId'] ?? null;

        if ($vatId === null || \strlen($vatId) <= 0 || $countryId === 0) {
            return null;
        }

        $modelManager = $this->get('models');

        $address = null;
        if ($addressId > 0) {
            $address = $modelManager->getRepository(Address::class)->find($addressId);
        }

        if (!$address instanceof Address) {
            $address = new Address();
        }

        $address->fromArray($data);

        if ($address->getCountry() instanceof Country) {
            return $address;
        }

        $country = $modelManager->getRepository(Country::class)->find($data['countryId']);
        if (!$country instanceof Country) {
            return null;
        }

        $address->setCountry($country);

        return $address;
    }
}
