<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class VatIdValidatorResult extends VatIdValidationStatus
{
    /**
     * @var array
     */
    private $errors;

    /**
     * @param $status
     * @param array $errors
     */
    public function __construct($status = 0, $errors = array())
    {
        parent::__construct($status);
        $this->errors = $errors;
    }

    /**
     * @param string $error
     * @param string $key
     */
    public function addError($error, $key = '')
    {
        $this->errors[$key] = $error;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
         return $this->errors;
    }
}