<?php

namespace App\Extension\tkc\backend\Console;

use App\Extension\tkc\backend\Config\ConfigOverrideMerger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'tkc:config:apply',
    description: 'Merge scripts/config/config_override.template.php into public/legacy/config_override.php'
)]
class ConfigApplyCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        private readonly ConfigOverrideMerger $merger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show the diff, change nothing')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Template path', 'scripts/config/config_override.template.php')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Override file path', 'public/legacy/config_override.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $template = $this->absolute((string)$input->getOption('template'));
        $target = $this->absolute((string)$input->getOption('target'));

        try {
            $templateConfig = $this->merger->loadTemplate($template);
            $current = $this->merger->parseOverrideFile($target);
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $diff = $this->merger->diff($current, $templateConfig);
        if ($diff === []) {
            $output->writeln('No change: ' . basename($target) . ' already matches the template.');
            return Command::SUCCESS;
        }

        foreach ($diff as $line) {
            $output->writeln($line);
        }

        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf('Dry run: %d change(s) not written.', count($diff)));
            return Command::SUCCESS;
        }

        $content = $this->merger->render($this->merger->merge($current, $templateConfig));
        // The .php suffix makes Apache run a leftover temp file instead of serving the config as text (security F1).
        $tmp = $target . '.tmp' . getmypid() . '.php';
        if (file_put_contents($tmp, $content) === false || !rename($tmp, $target)) {
            @unlink($tmp);
            $output->writeln('<error>Could not write ' . $target . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('Applied %d change(s) to %s.', count($diff), basename($target)));
        return Command::SUCCESS;
    }

    private function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : $this->projectDir . '/' . $path;
    }
}
