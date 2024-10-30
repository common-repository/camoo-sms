<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Camoo\Sms\Exception\BackgroundProcessException;
use Camoo\Sms\Interfaces\OperatingSystemInterface;

class BackgroundProcess
{
    private const ALLOWED_OS = ['LINUX', 'FREEBSD', 'DARWIN'];

    public function __construct(private ?string $command = null, private readonly ?OperatingSystemInterface $os = null)
    {
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function run(string $sOutputFile = '/dev/null', bool $bAppend = false): int
    {
        if ($this->getCommand() === null) {
            throw new BackgroundProcessException('Command is missing');
        }

        $os = $this->os ?? new OperatingSystem();
        $sOS = $os->get();

        if (empty($sOS)) {
            throw new BackgroundProcessException('Operating System cannot be determined');
        }

        if (str_starts_with($sOS, 'WIN')) {
            shell_exec(sprintf('%s &', $this->getCommand()));

            return 0;
        }

        if (!in_array($sOS, self::ALLOWED_OS, true)) {
            throw new BackgroundProcessException(sprintf('Operating System "%s" not Supported', $sOS));
        }

        return (int)shell_exec(
            sprintf(
                '%s %s %s 2>&1 & echo $!',
                $this->getCommand(),
                ($bAppend) ? '>>' : '>',
                $sOutputFile
            )
        );
    }

    public function canBackground(): bool
    {
        return function_exists('shell_exec');
    }

    protected function getCommand(): ?string
    {
        if (null === $this->command) {
            return null;
        }

        return escapeshellcmd($this->command);
    }
}
