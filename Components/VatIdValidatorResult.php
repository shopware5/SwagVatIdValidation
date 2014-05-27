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
    private $extendedStatus;

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
        $this->extendedStatus = array(
            'company' => $this::UNAVAILABLE,
            'street' => $this::UNAVAILABLE,
            'zipCode' => $this::UNAVAILABLE,
            'city' => $this::UNAVAILABLE
        );

        $this->errors = $errors;
    }

    public function setExtendedStatus($company, $street, $zipCode, $city)
    {
        $this->extendedStatus = array(
            'company' => $company,
            'street' => $street,
            'zipCode' => $zipCode,
            'city' => $city
        );
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
     * @return bool
     */
    public function serviceNotAvailable()
    {
        return ($this->status === $this::UNAVAILABLE);
    }

    /**
     * @return bool
     */
    public function isCompanyValid()
    {
        return ($this->extendedStatus['company'] === $this::VALID);
    }

    /**
     * @return bool
     */
    public function isStreetValid()
    {
        return ($this->extendedStatus['street'] === $this::VALID);
    }

    /**
     * @return bool
     */
    public function isZipCodeValid()
    {
        return ($this->extendedStatus['zipCode'] === $this::VALID);
    }

    /**
     * @return bool
     */
    public function isCityValid()
    {
        return ($this->extendedStatus['city'] === $this::VALID);
    }

    /**
     * @return bool
     */
    public function isCompanyAnswered()
    {
        return ($this->extendedStatus['company'] !== $this::UNAVAILABLE);
    }

    /**
     * @return bool
     */
    public function isStreetAnswered()
    {
        return ($this->extendedStatus['street'] !== $this::UNAVAILABLE);
    }

    /**
     * @return bool
     */
    public function isZipCodeAnswered()
    {
        return ($this->extendedStatus['zipCode'] !== $this::UNAVAILABLE);
    }

    /**
     * @return bool
     */
    public function isCityAnswered()
    {
        return ($this->extendedStatus['city'] !== $this::UNAVAILABLE);
    }

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