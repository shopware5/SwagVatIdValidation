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
     *
     * @return void
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
    private function removeMailTemplate(): void
    {
        $mail = $this->modelManager->getRepository(Mail::class)->findOneBy(['name' => 'sSWAGVATIDVALIDATION_VALIDATIONERROR']);

        if (!$mail instanceof Mail) {
            return;
        }

        $this->removeMailTranslations((int) $mail->getId());

        $this->modelManager->remove($mail);
        $this->modelManager->flush($mail);
    }

    /**
     * Helper function to remove the translations of the mail template
     */
    private function removeMailTranslations(int $mailTemplateId): void
    {
        $translations = $this->modelManager->getRepository(Translation::class)
            ->findBy(['key' => $mailTemplateId]);

        foreach ($translations as $translation) {
            if ($translation->getType() !== 'config_mails') {
                continue;
            }

            $this->modelManager->remove($translation);
            $this->modelManager->flush($translation);
        }
    }
}
