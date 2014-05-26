<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class VatIdValidatorResult
{
    /**
     * Status when the validation service was not available
     */
    const UNAVAILABLE = -1;

    /**
     * Status when the validation fails
     */
    const INVALID = 0;

    /**
     * Status when the validation was successful
     */
    const VALID = 1;

    /**
     * Status when the dummy validation was successful
     */
    const DUMMY_VALID = 2;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param $status
     * @param array $errors
     * @param VatIdCustomerInformation $customerInformation
     * @param VatIdInformation $shopInformation
     */
    public function __construct($status, $errors = array())
    {
        $this->status = $status;
        $this->errors = $errors;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->status === $this::VALID);
    }

    /**
     * @return bool
     */
    public function isDummyValid()
    {
        return ($this->status >= $this::VALID);
    }

    /**
     * @return integer
     */
    public function serviceNotAvailable()
    {
        return ($this->status === $this::UNAVAILABLE);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
         return $this->errors;
    }
}