<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagVatIdValidation\Components;

interface DependencyProviderInterface
{
    /**
     * @return \Enlight_Components_Session_Namespace
     */
    public function getSession();

    /**
     * @return \Enlight_Controller_Front
     */
    public function getFront();
}
