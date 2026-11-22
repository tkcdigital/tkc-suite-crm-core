<?php

namespace App\Extension\tkc\backend\Sequence\Console;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use App\Extension\tkc\backend\Sequence\Sequence;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SUITE-62. Shows, advances or sets a counter of the Sequence service. CLI only.
 *   tkc:sequence show <name> <year>
 *   tkc:sequence next <name> <year> [--count=N]     one value on each line
 *   tkc:sequence set <name> <year> <value> [--allow-lower] [--execute]   no change without --execute
 * The output has counter names and numbers only.
 */
#[AsCommand(
    name: 'tkc:sequence',
    description: 'Show, advance or set a counter of the sequence service (tkc_sequences)'
)]
class SequenceCommand extends Command
{
    public function __construct(private readonly DefaultLegacyHandler $legacyHandler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'show, next or set')
            ->addArgument('name', InputArgument::REQUIRED, 'Counter name, for example quote')
            ->addArgument('year', InputArgument::REQUIRED, 'Year, 2000 to 2099')
            ->addArgument('value', InputArgument::OPTIONAL, 'Last value (set only)')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'How many values to take (next only)', '1')
            ->addOption('allow-lower', null, InputOption::VALUE_NONE, 'Allow a value below the current value (set only)')
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Write the change (set only)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = (string)$input->getArgument('action');
        $name = (string)$input->getArgument('name');
        $yearArg = (string)$input->getArgument('year');
        $valueArg = $input->getArgument('value');
        $count = (string)$input->getOption('count');

        if (!in_array($action, ['show', 'next', 'set'], true)) {
            $output->writeln('<error>The action must be show, next or set.</error>');
            return Command::INVALID;
        }
        if (!ctype_digit($yearArg)) {
            $output->writeln('<error>The year must be a number.</error>');
            return Command::INVALID;
        }
        if ($action === 'set' && ($valueArg === null || !ctype_digit((string)$valueArg))) {
            $output->writeln('<error>set needs a value that is a number of 0 or more.</error>');
            return Command::INVALID;
        }
        if ($action === 'next' && (!ctype_digit($count) || (int)$count < 1 || (int)$count > 100000)) {
            $output->writeln('<error>The count must be from 1 to 100000.</error>');
            return Command::INVALID;
        }
        $year = (int)$yearArg;

        $lines = [];
        $error = null;
        $buffering = ob_get_level();
        ob_start(); // the legacy code echoes HTML; drop it
        try {
            $this->legacyHandler->init();
            try {
                $this->legacyHandler->runLegacyEntryPoint();
                $sequence = Sequence::legacy();
                switch ($action) {
                    case 'show':
                        $current = $sequence->current($name, $year);
                        $lines[] = sprintf('%s %d: %s', $name, $year, $current === null ? 'no counter' : (string)$current);
                        break;
                    case 'next':
                        for ($i = 0; $i < (int)$count; $i++) {
                            $lines[] = (string)$sequence->next($name, $year);
                        }
                        break;
                    case 'set':
                        $value = (int)$valueArg;
                        if ($input->getOption('execute')) {
                            $sequence->setLastValue($name, $year, $value, (bool)$input->getOption('allow-lower'));
                            $lines[] = sprintf('%s %d: last value set to %d', $name, $year, $value);
                        } else {
                            $current = $sequence->current($name, $year);
                            $lines[] = sprintf(
                                'Dry run: %s %d would change from %s to %d. Use --execute to write.',
                                $name,
                                $year,
                                $current === null ? 'no counter' : (string)$current,
                                $value
                            );
                        }
                        break;
                }
            } finally {
                $this->legacyHandler->close();
            }
        } catch (\Throwable $e) {
            $error = $e instanceof \InvalidArgumentException || $e instanceof \RuntimeException
                ? $e->getMessage()
                : get_class($e);
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

        return Command::SUCCESS;
    }
}
