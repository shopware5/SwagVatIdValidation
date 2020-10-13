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

namespace SwagVatIdValidation\Bundle\AccountBundle\Constraints;

use Shopware\Models\Customer\Address;
use SwagVatIdValidation\Components\ValidationServiceInterface;
use SwagVatIdValidation\Components\VatIdValidatorResult;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AdvancedVatIdValidator extends ConstraintValidator
{
    /**
     * @var ValidationServiceInterface
     */
    private $validationService;

    public function __construct(ValidationServiceInterface $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var AdvancedVatId $constraint */
        if (!$constraint instanceof AdvancedVatId) {
            throw new \RuntimeException('Invalid constraint for validator given.');
        }

        if (empty($value)) {
            return;
        }

        /** @var Form $form */
        $form = $this->context->getRoot();
        if (!$form) {
            return;
        }

        /** @var Address $address */
        $address = $constraint->address;
        if (!$address) {
            $address = $form->getData();
        }

        /** @var VatIdValidatorResult $result */
        $result = $this->validationService->validateVatId($address, false);

        if (empty($result->getErrorMessages())) {
            return;
        }

        foreach ($result->getErrorMessages() as $errorMessage) {
            $this->context->buildViolation($errorMessage)
                ->atPath($this->context->getPropertyPath())
                ->addViolation();
        }
    }
}
