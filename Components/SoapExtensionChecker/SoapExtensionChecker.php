<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components\SoapExtensionChecker;

class SoapExtensionChecker
{
    /**
     * @var bool
     */
    private $isSoapExtensionInstalled;

    public function __construct()
    {
        $this->isSoapExtensionInstalled = \extension_loaded('soap');
    }

    public function check(): bool
    {
        if ($this->isSoapExtensionInstalled === false) {
            throw new \RuntimeException('PHP soap extension is not installed');
        }

        return true;
    }
}
