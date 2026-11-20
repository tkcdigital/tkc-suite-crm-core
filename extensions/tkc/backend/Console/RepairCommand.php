<?php

namespace App\Extension\tkc\backend\Console;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI version of Admin > Repair > Quick Repair and Rebuild (SUITE-13, D10).
 * Rebuilds the Ext/ files, clears the legacy caches and shows (or runs) the repairDatabase SQL.
 */
#[AsCommand(
    name: 'tkc:repair',
    description: 'Quick Repair and Rebuild: rebuild extensions, clear legacy caches, show or run the database repair SQL'
)]
class RepairCommand extends Command
{
    public function __construct(private readonly DefaultLegacyHandler $legacyHandler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the SQL, change nothing in the database (default)')
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Run the SQL')
            ->addOption('module', null, InputOption::VALUE_REQUIRED, 'Repair only this module (for example Cases)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('execute') && $input->getOption('dry-run')) {
            $output->writeln('<error>Use either --dry-run or --execute.</error>');
            return Command::INVALID;
        }
        $execute = (bool)$input->getOption('execute');
        $module = $input->getOption('module') ?: null;

        $buffering = ob_get_level();
        ob_start(); // the legacy code echoes HTML; drop it
        try {
            $sql = $this->runRepair($execute, $module, $error);
        } catch (\Throwable $e) {
            $error = get_class($e) . ': ' . $e->getMessage();
            $sql = '';
        } finally {
            while (ob_get_level() > $buffering) {
                ob_end_clean();
            }
        }

        if ($error !== null) {
            $output->writeln('<error>' . $error . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('Extensions rebuilt, legacy caches cleared.');
        if (trim($sql) === '') {
            $output->writeln('Database is in sync: no SQL.');
        } else {
            $output->writeln($execute ? 'SQL executed:' : 'SQL that --execute would run (not run):');
            $output->writeln($this->formatSql($sql));
        }
        if (!$execute) {
            $output->writeln('Dry run: the database was not changed.');
        }
        $output->writeln('Next: php bin/console cache:clear');

        return Command::SUCCESS;
    }

    private function runRepair(bool $execute, ?string $module, ?string &$error): string
    {
        $error = null;
        $this->legacyHandler->init();
        try {
            $this->legacyHandler->runLegacyEntryPoint();

            global $current_user, $beanFiles, $beanList, $dictionary, $mod_strings;
            $current_user = \BeanFactory::newBean('Users');
            $current_user->getSystemUser();
            $GLOBALS['current_user'] = $current_user;
            if (empty($mod_strings)) {
                $mod_strings = [];
            }

            if ($module !== null && !isset($beanList[$module])) {
                $error = 'Unknown module: ' . $module;
                return '';
            }

            $this->rebuildAndClear($execute);

            return $this->repairDatabase($execute, $module);
        } finally {
            $this->legacyHandler->close();
        }
    }

    private function rebuildAndClear(bool $execute): void
    {
        require_once 'modules/Administration/QuickRepairAndRebuild.php';
        $rc = new \RepairAndClear();
        $rc->module_list = []; // always clear all caches; --module only limits the SQL
        $rc->show_output = false;
        $rc->execute = $execute;

        $rc->clearTpls();
        $rc->clearJsFiles();
        $rc->clearVardefs();
        $rc->clearJsLangFiles();
        $rc->clearLanguageCache();
        $rc->clearDashlets();
        $rc->clearSugarFeedCache();
        $rc->clearSmarty();
        $rc->clearThemeCache();
        $rc->clearXMLfiles();
        $rc->clearSearchCache();
        $rc->clearExternalAPICache();
        $rc->rebuildExtensions();
        $rc->clearVardefs();
        if ($execute) {
            $rc->rebuildAuditTables(); // creates tables
        }
    }

    /**
     * Same loop as modules/Administration/repairDatabase.php, but returns the SQL.
     */
    private function repairDatabase(bool $execute, ?string $module): string
    {
        global $beanFiles, $beanList, $dictionary;
        $db = \DBManagerFactory::getInstance();
        $GLOBALS['reload_vardefs'] = true;
        \VardefManager::clearVardef();
        $dictionary = []; // drop vardefs loaded during bootstrap, before the extension rebuild

        $sql = '';
        $repaired = [];
        $wantedBean = $module !== null ? $beanList[$module] : null;

        foreach ($beanFiles as $bean => $file) {
            if ($wantedBean !== null && $bean !== $wantedBean) {
                continue;
            }
            if (!file_exists($file)) {
                continue;
            }
            require_once $file;
            unset($GLOBALS['dictionary'][$bean]);
            $focus = new $bean();
            if (($focus instanceof \SugarBean) && !isset($repaired[$focus->table_name])) {
                $sql .= $db->repairTable($focus, $execute);
                $repaired[$focus->table_name] = true;
            }
            if (($focus instanceof \SugarBean) && $focus->hasCustomFields() && !isset($repaired[$focus->table_name . '_cstm'])) {
                $df = new \DynamicField($focus->module_dir);
                if (method_exists($df, 'repairCustomFields')) {
                    $df->bean = $focus;
                    $sql .= $df->repairCustomFields($execute);
                    $repaired[$focus->table_name . '_cstm'] = true;
                }
            }
        }

        if ($module === null) {
            $old = $dictionary;
            unset($dictionary);
            include 'modules/TableDictionary.php';
            foreach ($dictionary as $meta) {
                if (!isset($meta['table']) || isset($repaired[$meta['table']])) {
                    continue;
                }
                $sql .= $db->repairTableParams(
                    $meta['table'],
                    $meta['fields'],
                    $meta['indices'] ?? [],
                    $execute,
                    $meta['engine'] ?? null
                );
                $repaired[$meta['table']] = true;
            }
            $dictionary = $old;
        }

        return $sql;
    }

    private function formatSql(string $sql): string
    {
        $out = [];
        foreach (explode("\n", $sql) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $out[] = str_ends_with($line, '*/') ? $line : $line . ';';
        }

        return implode("\n", $out);
    }
}
