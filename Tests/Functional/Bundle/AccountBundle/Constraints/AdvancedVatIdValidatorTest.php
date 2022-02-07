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
