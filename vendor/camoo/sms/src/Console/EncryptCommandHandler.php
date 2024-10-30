<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Exception;
use nicoSWD\GPG\GPG;
use nicoSWD\GPG\PublicKey;

final class EncryptCommandHandler
{
    public function __construct(private readonly ?GPG $gpg = null)
    {
    }

    /** @throws Exception */
    public function handle(EncryptCommand $command): string
    {
        if (!is_file($command->publicKeyFile)) {
            return $command->message;
        }

        $keyContent = @file_get_contents($command->publicKeyFile);

        if (empty($keyContent)) {
            return $command->message;
        }

        $pubString = new PublicKey($keyContent);

        $pgpHandler = $this->gpg ?? new GPG();

        return $pgpHandler->encrypt($pubString, $command->message);
    }
}
