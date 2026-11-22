<?php

namespace App\Extension\tkc\backend\Console;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SUITE-64: run only the new TKC migrations (App\Extension\tkc\Migrations).
 * Core migrations are never run or recorded here: they belong to the upstream package-upgrade flow
 * and have side effects (config.php, .env.local). Prints version names and counts, never record contents.
 */
#[AsCommand(
    name: 'tkc:migrations:migrate',
    description: 'Run the new TKC data migrations (core migrations are not run)'
)]
class MigrationsMigrateCommand extends Command
{
    public const TKC_NAMESPACE = 'App\\Extension\\tkc\\Migrations\\';

    public function __construct(private readonly DependencyFactory $dependencyFactory)
    {
        parent::__construct();
    }

    /**
     * @param string[] $versions fully qualified version class names
     * @return string[]
     */
    public static function filterTkc(array $versions): array
    {
        return array_values(array_filter(
            $versions,
            static fn(string $v): bool => str_starts_with($v, self::TKC_NAMESPACE)
        ));
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'List the new TKC migrations, run nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->dependencyFactory->getMetadataStorage()->ensureInitialized();
            $new = array_map(
                static fn($m): string => (string)$m->getVersion(),
                $this->dependencyFactory->getMigrationStatusCalculator()->getNewMigrations()->getItems()
            );
            $tkc = self::filterTkc($new);
            $core = count($new) - count($tkc);

            if ($tkc === []) {
                $output->writeln('No new TKC migrations.');
            } elseif ($input->getOption('dry-run')) {
                foreach ($tkc as $version) {
                    $output->writeln('Would run ' . $version);
                }
            } else {
                $versions = array_map(static fn(string $v): Version => new Version($v), $tkc);
                $plan = $this->dependencyFactory->getMigrationPlanCalculator()->getPlanForVersions($versions, Direction::UP);
                $this->dependencyFactory->getMigrator()->migrate($plan, (new MigratorConfiguration())->setAllOrNothing(false));
                foreach ($tkc as $version) {
                    $output->writeln('Ran ' . $version);
                }
            }
            $output->writeln(sprintf('%d core migrations not run (upstream-merge flow only).', $core));
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
