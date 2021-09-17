<?php
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
