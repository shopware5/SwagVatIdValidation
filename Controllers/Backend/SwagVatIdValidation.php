<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
