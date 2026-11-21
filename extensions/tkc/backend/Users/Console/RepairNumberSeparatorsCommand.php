<?php

namespace App\Extension\tkc\backend\Users\Console;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use App\Extension\tkc\backend\Users\LegacyPreferenceStore;
use App\Extension\tkc\backend\Users\NumberSeparatorPolicy;
use App\Extension\tkc\backend\Users\PreferenceStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SUITE-179. Removes user-level number separators that conflict with the system defaults.
 * Run after tkc:config:apply. Users that are not deleted are checked, all statuses.
 * Output has user IDs and key names only.
 */
#[AsCommand(
    name: 'tkc:users:repair-number-separators',
    description: 'Clear user decimal/grouping separators that equal the other effective separator'
)]
class RepairNumberSeparatorsCommand extends Command
{
    private readonly PreferenceStore $store;

    public function __construct(
        private readonly DefaultLegacyHandler $legacyHandler,
        private readonly NumberSeparatorPolicy $policy,
        ?PreferenceStore $store = null
    ) {
        parent::__construct();
        $this->store = $store ?? new LegacyPreferenceStore();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show the changes, write nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool)$input->getOption('dry-run');
        $lines = [];
        $changed = 0;
        $skipped = 0;
        $checked = 0;
        $error = null;

        $buffering = ob_get_level();
        ob_start(); // the legacy code echoes HTML; drop it
        try {
            $this->legacyHandler->init();
            try {
                $this->legacyHandler->runLegacyEntryPoint();
                global $current_user;
                if (class_exists(\BeanFactory::class)) {
                    $current_user = \BeanFactory::newBean('Users');
                    $current_user->getSystemUser();
                    $GLOBALS['current_user'] = $current_user;
                }

                [$sysDec, $sysGrp] = $this->store->systemSeparators();
                foreach ($this->store->activeUserIds() as $userId) {
                    $checked++;
                    try {
                        $prefs = $this->store->readGlobal($userId);
                        if ($prefs === null) {
                            continue;
                        }
                        $keys = $this->policy->keysToClear($prefs, $sysDec, $sysGrp);
                        if ($keys === []) {
                            continue;
                        }
                        if (!$dryRun) {
                            foreach ($keys as $key) {
                                unset($prefs[$key]);
                            }
                            $this->store->writeGlobal($userId, $prefs);
                        }
                        $changed++;
                        $lines[] = sprintf('~ user %s: clear %s', $userId, implode(', ', $keys));
                    } catch (\UnexpectedValueException) {
                        $skipped++;
                        $lines[] = sprintf('! user %s: skipped, preferences do not decode', $userId);
                    }
                }
            } finally {
                $this->legacyHandler->close();
            }
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
        foreach ($lines as $line) {
            $output->writeln($line);
        }
        $output->writeln(sprintf(
            '%s: %d user(s) checked, %d change(s)%s, %d skipped.',
            $dryRun ? 'Dry run' : 'Done',
            $checked,
            $changed,
            $dryRun ? ' not written' : '',
            $skipped
        ));
        if ($changed > 0 && !$dryRun) {
            $output->writeln('Signed-in users see the change after their next sign-in.');
        }

        return Command::SUCCESS;
    }
}
