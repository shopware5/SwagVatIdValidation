<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Tests\Functional\Bundle\AccountBundle\Constraints;

use PHPUnit\Framework\TestCase;
use Shopware\Models\Customer\Address;
use SwagVatIdValidation\Bundle\AccountBundle\Constraints\AdvancedVatId;
use SwagVatIdValidation\Bundle\AccountBundle\Constraints\AdvancedVatIdValidator;
use SwagVatIdValidation\Tests\ContainerTrait;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AdvancedVatIdValidatorTest extends TestCase
{
    use ContainerTrait;

    public function testValidateWithAddressWithoutId(): void
    {
        $validator = new AdvancedVatIdValidator(
            $this->getContainer()->get('swag_vat_id_validation.validation_service')
        );

        $context = $this->createMock(ExecutionContext::class);
        $context->expects(static::once())->method('getRoot')->willReturn('foo');
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintViolationBuilder->expects(static::once())->method('atPath')->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects(static::once())->method('addViolation');
        $context->expects(static::once())->method('buildViolation')->willReturn($constraintViolationBuilder);
        $validator->initialize($context);

        $constraint = new AdvancedVatId();
        $constraint->address = new Address();
        $validator->validate('DE123456789', $constraint);
    }
}
