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
