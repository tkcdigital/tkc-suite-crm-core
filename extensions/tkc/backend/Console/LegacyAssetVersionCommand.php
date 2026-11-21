<?php

namespace App\Extension\tkc\backend\Console;

use App\Extension\tkc\backend\Config\ConfigOverrideMerger;
use App\Extension\tkc\backend\Config\LegacyAssetVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * SUITE-181: set js_custom_version from a content hash of the legacy theme assets.
 * Prints only the version and the result, never other keys or file content.
 */
#[AsCommand(
    name: 'tkc:legacy:asset-version',
    description: 'Set js_custom_version in config_override.php from a hash of the legacy theme assets'
)]
class LegacyAssetVersionCommand extends Command
{
    private const KEY = 'js_custom_version';

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        private readonly ConfigOverrideMerger $merger,
        private readonly LegacyAssetVersion $assetVersion
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show the result, change nothing')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Override file path', 'public/legacy/config_override.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = str_starts_with((string)$input->getOption('target'), '/')
            ? (string)$input->getOption('target')
            : $this->projectDir . '/' . $input->getOption('target');

        try {
            $version = $this->assetVersion->compute($this->projectDir);
            $current = $this->merger->parseOverrideFile($target);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(self::KEY . ' = ' . $version);

        if (($current[self::KEY] ?? null) === $version) {
            $output->writeln('No change');
            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            $output->writeln('Would apply');
            return Command::SUCCESS;
        }

        $content = $this->merger->render($this->merger->merge($current, [self::KEY => $version]));
        $tmp = $target . '.tmp' . getmypid();
        if (file_put_contents($tmp, $content) === false || !rename($tmp, $target)) {
            @unlink($tmp);
            $output->writeln('<error>Could not write ' . basename($target) . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('Applied');
        return Command::SUCCESS;
    }
}
