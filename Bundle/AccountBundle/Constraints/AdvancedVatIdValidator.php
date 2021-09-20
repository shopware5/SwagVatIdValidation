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
