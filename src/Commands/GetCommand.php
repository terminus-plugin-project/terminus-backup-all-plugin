<?php

namespace TerminusPluginProject\TerminusBackupAll\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Class GetCommand
 * @package TerminusPluginProject\TerminusBackupAll\Commands
 */
class GetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the URL for the most recent backups of all site environments.
     *
     * @authorize
     *
     * @command backup-all:get
     * @aliases ball:get
     *
     * @option string $env [dev|test|live] Backup environment to retrieve
     * @option string $element [code|files|database|db] Backup element to retrieve
     * @option string $date [YYYY-MM-DD] Backup date to retrieve
     * @throws TerminusNotFoundException
     *
     * @usage terminus backup-all:get
     *     Displays the URL for the most recent backups of all site environments.
     * @usage terminus backup-all:get --element=code
     *     Displays the URL for the most recent code backup of all site environments.
     *
     * @field-labels
     *     url: URL
     * @return RowsOfFields
     */
    public function getBackup(array $options = ['env' => 'all', 'element' => null, 'date' => null,])
    {
        $rows = [];
        $sites = $this->sites->serialize();
        foreach ($sites as $site) {
            if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                foreach ($environments as $environment) {
                    if ($environment['initialized'] == 'true') {
                        $show = ($options['env'] == 'all') ? true : ($environment['id'] == $options['env']);
                        if ($show) {
                            $site_env = $site['name'] . '.' . $environment['id'];
                            list(, $env) = $this->getSiteEnv($site_env);

                            if (isset($options['element'])) {
                                $element = ($options['element'] == 'db') ? 'database' : $options['element'];
                                $elements = ["'" . $element . "'",];
                            } else {
                                $elements = ['code', 'database', 'files',];
                            }
                            foreach ($elements as $element) {
                                $backups = $env->getBackups()->getFinishedBackups($element);
                                if (empty($backups)) {
                                    $this->log()->notice(
                                        'No backups available for the {element} element of {site_env}.',
                                        ['element' => $element, 'site_env' => $site_env,]
                                    );
                                } elseif (isset($options['date'])) {
                                    foreach ($backups as $backup) {
                                        if (strpos($backup->getDate(), $options['date']) !== false) {
                                            $rows[] = [
                                                'url' => $backup->getUrl(),
                                            ];
                                        }
                                    }
                                } else {
                                    $backup = array_shift($backups);
                                    $rows[] = [
                                        'url' => $backup->getUrl(),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->log()->notice('You have {count} backups.', ['count' => count($rows),]);
        if (!empty($rows)) {
            return new RowsOfFields($rows);
        }
    }
}
