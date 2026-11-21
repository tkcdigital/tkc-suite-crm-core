<?php

namespace App\Extension\tkc\backend\Tests\Unit\Mail;

use App\Extension\tkc\backend\Mail\MailSinkPolicy;
use PHPUnit\Framework\TestCase;

class MailSinkPolicyTest extends TestCase
{
    public function testIsSinkHostIgnoresCaseAndWhitespace(): void
    {
        $policy = new MailSinkPolicy(['mailpit']);
        $this->assertTrue($policy->isSinkHost('MailPit'));
        $this->assertTrue($policy->isSinkHost("  mailpit \n"));
        $this->assertFalse($policy->isSinkHost('smtp.example.com'));
    }

    public function testEmptyHostsAreDropped(): void
    {
        $policy = new MailSinkPolicy(['', '  ', 'Mailpit']);
        $this->assertSame(['mailpit'], $policy->getSinkHosts());
        $this->assertFalse($policy->isSinkHost(''));
    }

    public function testFilterNonSinkSkipsSinkRowsAndKeepsEmptyHostRows(): void
    {
        $policy = new MailSinkPolicy(['mailpit']);
        $rows = [
            ['id' => '1', 'type' => 'system', 'mail_smtpserver' => 'mailpit'],
            ['id' => '2', 'type' => 'user', 'mail_smtpserver' => 'smtp.example.com'],
            ['id' => '3', 'type' => 'user', 'mail_smtpserver' => ''],
            ['id' => '4', 'type' => 'system-override', 'mail_smtpserver' => null],
        ];

        $this->assertSame(
            [
                ['id' => '2', 'type' => 'user', 'host' => 'smtp.example.com'],
                ['id' => '3', 'type' => 'user', 'host' => ''],
                ['id' => '4', 'type' => 'system-override', 'host' => ''],
            ],
            $policy->filterNonSink($rows)
        );
    }
}
