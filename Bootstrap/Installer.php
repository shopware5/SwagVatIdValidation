<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

    /**
     * @return void
     */
    public function install()
    {
        if (!$this->isMailExists()) {
            $this->createMailTemplate();
        }
    }

    /**
     * Helper function to create the mail template
     */
    private function createMailTemplate(): void
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
     */
    private function isMailExists(): bool
    {
        $mail = $this->modelManager->getRepository(Mail::class)
            ->findOneBy(['name' => 'sSWAGVATIDVALIDATION_VALIDATIONERROR']);

        if ($mail instanceof Mail) {
            return true;
        }

        return false;
    }

    private function getMail(): Mail
    {
        // Template
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

    private function getTranslation(Mail $mail): ?Translation
    {
        $shop = $this->modelManager->getRepository(Shop::class)->findOneBy([
            'locale' => $this->modelManager->getRepository(Locale::class)->findOneBy(['locale' => 'en_GB']),
        ]);

        if (!$shop instanceof Shop) {
            return null;
        }

        // Translation
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
