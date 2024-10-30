<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Camoo\Sms\Constants;
use Camoo\Sms\Exception\BackgroundProcessException;
use Camoo\Sms\Lib\Utils;
use Exception;

final class AddBulkCommandHandler
{
    private const PHP_BIN = 'php';

    public function __construct(private readonly ?BackgroundProcess $backgroundProcess = null)
    {
    }

    /** @throws Exception */
    public function handle(AddBulkCommand $command): int
    {
        $sTmpName = Utils::randomStr() . '.bulk';
        $binPath = $command->binPath ?? self::PHP_BIN;
        if ($binPath !== 'php' && !is_executable($binPath)) {
            return 0;
        }

        $tmpContent = file_put_contents(
            Constants::getSMSPath() . 'tmp/' . $sTmpName,
            json_encode($command->data) . PHP_EOL,
            LOCK_EX
        );

        if ($tmpContent === false) {
            return 0;
        }

        return $this->add($command, $binPath, $sTmpName);
    }

    private function add(AddBulkCommand $command, string $binPath, string $sTmpName): int
    {
        $sBIN = $binPath . ' -f ' . Constants::getSMSPath() . 'bin/camoo.php';
        $sPASS = serialize([$command->callback, $sTmpName,
            ['api_key' => $command->credentials->key, 'api_secret' => $command->credentials->secret]]);
        $process = $this->backgroundProcess ?? new BackgroundProcess();

        if (!$process->canBackground()) {
            throw new BackgroundProcessException('function "shell_exec" is required for background process');
        }

        return $process->setCommand($sBIN . ' ' . base64_encode($sPASS))->run();
    }
}
