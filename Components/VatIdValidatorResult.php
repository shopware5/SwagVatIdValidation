<?php

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class VatIdValidatorResult
{
    private $isValid;
    /**
     * @var array
     */
    private $errors;

    public function __construct($isValid, $errors = array())
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
         return $this->errors;
    }
}