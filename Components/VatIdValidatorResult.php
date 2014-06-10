<?php
/**
 * Shopware 4
 * Copyright © shopware AG
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

namespace Shopware\Plugins\SwagVatIdValidation\Components;

class VatIdValidatorResult implements \Serializable
{
    //Flags
    const VAT_ID_OK = 1;
    const COMPANY_OK = 2;
    const STREET_OK = 4;
    const ZIP_CODE_OK = 8;
    const CITY_OK = 16;


    //States

    /**
     * Status 0 happens when
     * - validation service was unavailable
     * - the check is still not executed
     */
    const UNAVAILABLE = 0;
    const INVALID = 0;

    /**
     * Status 31 happens when
     * - the check was executed and each was valid
     */
    const VALID = 31;

    /** @var integer */
    private $status;

    /** @var array */
    private $errors;

    /** @var  array */
    private $flags;

    /** @var  \Shopware_Components_Snippet_Manager */
    private $snippetManager;

    /** @var  string */
    private $namespace;

    /** @var  \Enlight_Components_Snippet_Namespace */
    private $pluginSnippets;

    /** @var  \Enlight_Components_Snippet_Namespace */
    private $validatorSnippets;

    /**
     * If $snippetManager is null, the result only can be VALID
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param string $namespace
     */
    public function __construct(\Shopware_Components_Snippet_Manager $snippetManager = null, $namespace = '')
    {
        $this->snippetManager = $snippetManager;
        $this->init($namespace);
    }

    /**
     * Helper function to init the result. Used in constructor and unserialize()
     * @param string $namespace
     */
    private function init($namespace)
    {
        $this->status = $this::VALID;
        $this->errors = array();
        $this->flags = array();

        if (!$this->snippetManager) {
            return;
        }

        $this->pluginSnippets = $this->snippetManager->getNamespace('frontend/swag_vat_id_validation/main');
        $this->namespace = $namespace;

        if (empty($namespace)) {
            return;
        }

        $this->validatorSnippets = $this->snippetManager->getNamespace('frontend/swag_vat_id_validation/' . $namespace);
    }

    /**
     * Sets the VatId to 'invalid' and sets the validator error message by $errorCode
     * @param string $errorCode
     */
    public function setVatIdInvalid($errorCode)
    {
        if(!$this->snippetManager) {
            return;
        }

        $this->status = $this::INVALID;
        $this->errors[$errorCode] = $this->validatorSnippets->get('error' . $errorCode);
        $this->flags['ustid'] = true;
    }

    /**
     * Sets the result to the api service was not available
     */
    public function setServiceUnavailable()
    {
        if(!$this->snippetManager) {
            return;
        }

        $this->status = $this::UNAVAILABLE;
        $this->errors['unavailable'] = $this->pluginSnippets->get('messages/checkNotAvailable');
        $this->flags['ustid'] = true;
    }

    /**
     * Sets the company to invalid
     */
    public function setCompanyInvalid()
    {
        if(!$this->snippetManager) {
            return;
        }

        $this->status &= ~($this::COMPANY_OK);
        $this->errors['company'] = $this->pluginSnippets->get('validator/extended/error/company');
        $this->flags['company'] = true;
    }

    /**
     * Sets the street and streetnumber to invalid
     */
    public function setStreetInvalid()
    {
        if(!$this->snippetManager) {
            return;
        }

        $this->status &= ~($this::STREET_OK);
        $this->errors['street'] = $this->pluginSnippets->get('validator/extended/error/street');
        $this->flags['street'] = true;
        $this->flags['streetnumber'] = true;
    }

    /**
     * Sets the zipcode to invalid
     */
    public function setZipCodeInvalid()
    {
        if(!$this->snippetManager) {
            return;
        }

        $this->status &= ~($this::ZIP_CODE_OK);
        $this->errors['zipCode'] = $this->pluginSnippets->get('validator/extended/error/zipCode');
        $this->flags['zipcode'] = true;
    }

    /**
     * Sets the city to invalid
     */
    public function setCityInvalid()
    {
        if(!$this->snippetManager) {
            return;
        }

        $this->status &= ~($this::CITY_OK);
        $this->errors['city'] = $this->pluginSnippets->get('validator/extended/error/city');
        $this->flags['city'] = true;
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getErrorFlags()
    {
        return $this->flags;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->status === $this::VALID);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        $serializeArray = array(
            'namespace' => $this->namespace,
            'keys' => array_keys($this->errors)
        );

        return serialize($serializeArray);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $serializeArray = unserialize($serialized);

        $this->init($serializeArray['namespace']);

        foreach($serializeArray['keys'] as $errorCode)
        {
            $this->addError($errorCode);
        }
    }

    private function addError($errorCode)
    {
        if(!$this->snippetManager) {
            return;
        }

        if($errorCode === 'unavailable') {
            $this->setServiceUnavailable();
            return;
        }

        if($errorCode === 'company') {
            $this->setCompanyInvalid();
            return;
        }

        if($errorCode === 'street') {
            $this->setStreetInvalid();
            return;
        }

        if($errorCode === 'zipCode') {
            $this->setZipCodeInvalid();
            return;
        }

        if($errorCode === 'city') {
            $this->setCityInvalid();
            return;
        }

        $this->setVatIdInvalid($errorCode);
    }
}