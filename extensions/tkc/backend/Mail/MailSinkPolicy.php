<?php

namespace App\Extension\tkc\backend\Mail;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Decides which outbound email hosts count as the dev mail sink (SUITE-45).
 * isSinkHost() and filterNonSink() are pure logic. Only loadOutboundAccounts() touches the legacy DB.
 */
class MailSinkPolicy
{
    /** @var list<string> */
    private array $sinkHosts;

    /**
     * @param list<string> $sinkHosts
     */
    public function __construct(
        #[Autowire('%tkc.mail.sink_hosts%')] array $sinkHosts,
        private readonly ?DefaultLegacyHandler $legacyHandler = null
    ) {
        $this->sinkHosts = array_values(array_filter(array_map(
            static fn($h) => strtolower(trim((string)$h)),
            $sinkHosts
        ), static fn($h) => $h !== ''));
    }

    /**
     * @return list<string>
     */
    public function getSinkHosts(): array
    {
        return $this->sinkHosts;
    }

    public function getPrimarySinkHost(): string
    {
        if ($this->sinkHosts === []) {
            throw new \RuntimeException('No mail sink host configured (tkc.mail.sink_hosts).');
        }

        return $this->sinkHosts[0];
    }

    public function isSinkHost(string $host): bool
    {
        return in_array(strtolower(trim($host)), $this->sinkHosts, true);
    }

    /**
     * Pure part: keep the rows whose host is not a sink host.
     *
     * @param iterable<array{id:string,type:string,mail_smtpserver:?string}> $rows
     * @return list<array{id:string,type:string,host:string}>
     */
    public function filterNonSink(iterable $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $host = (string)($row['mail_smtpserver'] ?? '');
            if ($this->isSinkHost($host)) {
                continue;
            }
            $result[] = ['id' => (string)$row['id'], 'type' => (string)($row['type'] ?? ''), 'host' => $host];
        }

        return $result;
    }

    /**
     * All non-deleted outbound accounts (system, system-override, user) that do not use a sink host.
     *
     * @return list<array{id:string,type:string,host:string}>
     */
    public function findNonSinkOutboundAccounts(): array
    {
        return $this->filterNonSink($this->loadOutboundAccounts());
    }

    /**
     * @return list<array{id:string,type:string,mail_smtpserver:?string}>
     */
    private function loadOutboundAccounts(): array
    {
        if ($this->legacyHandler === null) {
            throw new \LogicException('Legacy handler is needed to read outbound accounts.');
        }

        $this->legacyHandler->init();
        try {
            $this->legacyHandler->runLegacyEntryPoint();
            $db = \DBManagerFactory::getInstance();
            $result = $db->query('SELECT id, type, mail_smtpserver FROM outbound_email WHERE deleted = 0');
            $rows = [];
            while ($row = $db->fetchByAssoc($result)) {
                $rows[] = $row;
            }

            return $rows;
        } finally {
            $this->legacyHandler->close();
        }
    }
}
