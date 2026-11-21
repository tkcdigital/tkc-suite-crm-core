<?php

namespace App\Extension\tkc\backend\Tests\Unit\Mail;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use App\Extension\tkc\backend\Mail\Console\MailUseSinkCommand;
use App\Extension\tkc\backend\Mail\MailSinkPolicy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * SUITE-177: the command refuses to run outside the dev Docker environment.
 */
class MailUseSinkCommandTest extends TestCase
{
    private string|false $saved;

    protected function setUp(): void
    {
        $this->saved = getenv(MailUseSinkCommand::GUARD_ENV);
    }

    protected function tearDown(): void
    {
        putenv($this->saved === false
            ? MailUseSinkCommand::GUARD_ENV
            : MailUseSinkCommand::GUARD_ENV . '=' . $this->saved);
    }

    public function testGuardAcceptsOnlyTheValueOne(): void
    {
        self::assertTrue(MailUseSinkCommand::guardAllows('1'));
        foreach ([false, null, '', '0', 'true', 'yes', ' 1'] as $value) {
            self::assertFalse(MailUseSinkCommand::guardAllows($value), var_export($value, true));
        }
    }

    public function testRefusesAndTouchesNothingWithoutTheGuard(): void
    {
        putenv(MailUseSinkCommand::GUARD_ENV);

        $legacy = $this->createMock(DefaultLegacyHandler::class);
        $legacy->expects(self::never())->method('init');
        $legacy->expects(self::never())->method('runLegacyEntryPoint');

        $tester = new CommandTester(new MailUseSinkCommand($legacy, new MailSinkPolicy(['mailpit'])));
        $status = $tester->execute([]);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('Refused', $tester->getDisplay());
    }
}
