<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Bundle\AccountBundle\Constraints;

use Shopware\Models\Customer\Address;
use SwagVatIdValidation\Components\ValidationServiceInterface;
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
     *
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AdvancedVatId) {
            throw new \RuntimeException('Invalid constraint for validator given.');
        }

        if (empty($value)) {
            return;
        }

        $form = $this->context->getRoot();
        if (!$form) {
            return;
        }

        $address = $constraint->address;
        if (!$address instanceof Address) {
            $address = $form->getData();
        }

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
