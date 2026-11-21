<?php

namespace App\Extension\tkc\backend\Mail\Console;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use App\Extension\tkc\backend\Mail\MailSinkPolicy;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * DEV ONLY (SUITE-45). Points the system outbound email account at the Mailpit sink.
 * The command ships with the code to every environment, so it refuses to run unless
 * the env variable TKC_DEV_MAIL_SINK is "1" (set only in the dev compose.yaml, SUITE-177).
 */
#[AsCommand(
    name: 'tkc:mail:use-sink',
    description: 'DEV ONLY: set the system outbound email account to the Mailpit sink (SMTP, no auth)'
)]
class MailUseSinkCommand extends Command
{
    private const PORT = 1025;
    private const FROM_ADDR = 'crm@example.com';
    private const FROM_NAME = 'TKC CRM (dev)';
    public const GUARD_ENV = 'TKC_DEV_MAIL_SINK';
    private const MASKED_FIELDS = ['mail_smtppass', 'mail_smtpuser', 'smtp_from_addr'];

    public function __construct(
        private readonly DefaultLegacyHandler $legacyHandler,
        private readonly MailSinkPolicy $policy
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show the diff, change nothing');
    }

    public static function guardAllows(string|false|null $guardValue): bool
    {
        return $guardValue === '1';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!self::guardAllows(getenv(self::GUARD_ENV))) {
            $output->writeln(sprintf(
                '<error>Refused: tkc:mail:use-sink runs only where %s=1 (the dev Docker environment).</error>',
                self::GUARD_ENV
            ));
            return Command::FAILURE;
        }

        $dryRun = (bool)$input->getOption('dry-run');
        $wanted = [
            'mail_sendtype' => 'SMTP',
            'mail_smtptype' => 'other',
            'mail_smtpserver' => $this->policy->getPrimarySinkHost(),
            'mail_smtpport' => self::PORT,
            'mail_smtpauth_req' => 0,
            'mail_smtpssl' => 0,
            'mail_smtpuser' => '',
            'mail_smtppass' => '',
            'smtp_from_addr' => self::FROM_ADDR,
            'smtp_from_name' => self::FROM_NAME,
        ];

        $buffering = ob_get_level();
        ob_start(); // the legacy code echoes HTML; drop it
        $diff = [];
        $error = null;
        try {
            $diff = $this->apply($wanted, $dryRun);
        } catch (\Throwable $e) {
            $error = get_class($e) . ': ' . $e->getMessage();
        } finally {
            while (ob_get_level() > $buffering) {
                ob_end_clean();
            }
        }

        if ($error !== null) {
            $output->writeln('<error>' . $error . '</error>');
            return Command::FAILURE;
        }
        if ($diff === []) {
            $output->writeln('No change: the system outbound account already uses the mail sink.');
            return Command::SUCCESS;
        }
        foreach ($diff as $line) {
            $output->writeln($line);
        }
        $output->writeln($dryRun
            ? sprintf('Dry run: %d change(s) not written.', count($diff))
            : sprintf('Applied %d change(s) to the system outbound account.', count($diff)));

        return Command::SUCCESS;
    }

    /**
     * @param array<string,string|int> $wanted
     * @return list<string>
     */
    private function apply(array $wanted, bool $dryRun): array
    {
        $this->legacyHandler->init();
        try {
            $this->legacyHandler->runLegacyEntryPoint();

            global $current_user;
            $current_user = \BeanFactory::newBean('Users');
            $current_user->getSystemUser();
            $GLOBALS['current_user'] = $current_user;

            require_once 'include/OutboundEmail/OutboundEmail.php';
            $account = new \OutboundEmail();
            $existing = $account->getSystemEmail();
            $isNew = $existing === null || empty($account->id);

            $diff = [];
            foreach ($wanted as $field => $value) {
                $old = $isNew ? null : ($account->$field ?? null);
                if ($old !== null && (string)$old === (string)$value) {
                    continue;
                }
                $diff[] = sprintf(
                    '~ %s: %s -> %s',
                    $field,
                    $old === null ? '(unset)' : $this->show($field, (string)$old),
                    $this->show($field, (string)$value)
                );
            }
            if ($diff === [] || $dryRun) {
                return $diff;
            }

            if ($isNew) {
                $account = new \OutboundEmail();
                $account->getSystemMailerSettings(); // creates the system row with defaults
            }
            foreach ($wanted as $field => $value) {
                $account->$field = $value;
            }
            $account->mail_smtpdisplay = $wanted['mail_smtpserver'];
            $account->saveSystem();

            return $diff;
        } finally {
            $this->legacyHandler->close();
        }
    }

    private function show(string $field, string $value): string
    {
        if (in_array($field, self::MASKED_FIELDS, true) && $value !== self::FROM_ADDR) {
            return $value === '' ? "''" : '(set)';
        }

        return "'" . $value . "'";
    }
}
