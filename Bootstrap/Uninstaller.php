<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
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

namespace SwagVatIdValidation\Bootstrap;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Mail\Mail;
use Shopware\Models\Translation\Translation;

class Uninstaller
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * @param bool $keepUserData
     */
    public function uninstall($keepUserData)
    {
        if ($keepUserData) {
            return;
        }

        $this->removeMailTemplate();
    }

    /**
     * Helper function to remove the mail template
     */
    private function removeMailTemplate()
    {
        /** @var Mail $mail */
        $mail = $this->modelManager->getRepository(Mail::class)
            ->findOneBy(['name' => 'sSWAGVATIDVALIDATION_VALIDATIONERROR']);

        if (!$mail) {
            return;
        }

        $this->removeMailTranslations($mail->getId());

        $this->modelManager->remove($mail);
        $this->modelManager->flush($mail);
    }

    /**
     * Helper function to remove the translations of the mail template
     */
    private function removeMailTranslations($mailTemplateId)
    {
        $translations = $this->modelManager->getRepository(Translation::class)
            ->findBy(['key' => $mailTemplateId]);

        /** @var Translation $translation */
        foreach ($translations as $translation) {
            if ($translation->getType() !== 'config_mails') {
                continue;
            }

            $this->modelManager->remove($translation);
            $this->modelManager->flush($translation);
        }
    }
}
