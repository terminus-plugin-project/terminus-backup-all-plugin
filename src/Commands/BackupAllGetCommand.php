<?php

namespace TerminusPluginProject\TerminusBackupAll\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Class BackupAllGetCommand
 * @package TerminusPluginProject\TerminusBackupAll\Commands
 */
class BackupAllGetCommand extends TerminusCommand implements SiteAwareInterface
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
     * @throws TerminusNotFoundException
     *
     * @usage terminus backup-all:get
     *     Displays the URL for the most recent backups of all site environments.
     * @usage terminus backup-all:get --element=code
     *     Displays the URL for the most recent code backup of all site environments.
     *
     * @field-labels
     *     env: Environment
     *     element: Element
     *     url: URL
     * @return RowsOfFields
     */
    public function getBackup(array $options = ['env' => 'all', 'element' => null,])
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
                                } else {
                                    $backup = array_shift($backups);
                                    $rows[] = [
                                        'env' => $site_env,
                                        'element' => ($element == 'database') ? 'db' : $element,
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
