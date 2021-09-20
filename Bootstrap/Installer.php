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
use Shopware\Models\Shop\Locale;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Translation\Translation;

class Installer
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function install()
    {
        if (!$this->isMailExists()) {
            $this->createMailTemplate();
        }
    }

    /**
     * Helper function to create the mail template
     */
    private function createMailTemplate()
    {
        $mail = $this->getMail();

        $this->modelManager->persist($mail);
        $this->modelManager->flush($mail);

        $translation = $this->getTranslation($mail);

        if (!$translation) {
            return;
        }

        $this->modelManager->persist($translation);
        $this->modelManager->flush($translation);
    }

    /**
     * Check, if mail template already exists (because secureUninstall and update)
     *
     * @return bool
     */
    private function isMailExists()
    {
        $mail = $this->modelManager->getRepository(Mail::class)
            ->findOneBy(['name' => 'sSWAGVATIDVALIDATION_VALIDATIONERROR']);

        if ($mail) {
            return true;
        }

        return false;
    }

    /**
     * @return Mail
     */
    private function getMail()
    {
        //Template
        $content = "Hallo,\n\nbei der Überprüfung der USt-IdNr. {\$sVatId} der Firma\n\n{\$sCompany}\n{\$sStreet}\n{\$sZipCode} {\$sCity}\n\nLändercode: {\$sCountryCode}\n\nist ein Fehler aufgetreten:\n\n{\$sError}\n\n{config name=shopName}";

        $mail = new Mail();
        $mail->setName('sSWAGVATIDVALIDATION_VALIDATIONERROR');
        $mail->setFromMail('');
        $mail->setFromName('');
        $mail->setSubject('Bei der Überprüfung der Ust-IdNr. {$sVatId} ist ein Fehler aufgetreten');
        $mail->setContent($content);
        $mail->setMailtype(Mail::MAILTYPE_SYSTEM);

        return $mail;
    }

    /**
     * @return Translation|null
     */
    private function getTranslation(Mail $mail)
    {
        $shop = $this->modelManager->getRepository(Shop::class)
            ->findOneBy(['locale' => $this->modelManager->getRepository(Locale::class)
                ->findOneBy(['locale' => 'en_GB']), ]);

        if (!$shop) {
            return null;
        }

        //Translation
        $translation = new Translation();
        $translation->setShop($shop);
        $translation->setType('config_mails');
        $translation->setKey($mail->getId());
        $translation->setData(
            \serialize(
                [
                    'subject' => 'An error occurred when validating VAT ID {$sVatId}.',
                    'content' => "Hello,\n\nAn error occurred during the validation of VAT ID {\$sVatId} associated with the following company:\n\n{\$sCompany}\n{\$sStreet}\n{\$sZipCode} {\$sCity}\n\nCountry code: {\$sCountryCode}\n\nThe following errors were detected:\n\n{\$sError}\n\n{config name=shopName}",
                ]
            )
        );

        return $translation;
    }
}
