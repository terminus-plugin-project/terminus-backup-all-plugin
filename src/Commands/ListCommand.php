<?php

namespace TerminusPluginProject\TerminusBackupAll\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package TerminusPluginProject\TerminusBackupAll\Commands;
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Lists backups of all site environments.
     *
     * @authorize
     *
     * @command backup-all:list
     * @aliases ball:list
     *
     * @option string $env [dev|test|live] Backup environment filter
     * @option string $element [code|files|database|db] Backup element filter
     * @option string $date [YYYY-MM-DD] Backup date filter
     *
     * @usage terminus backup-all:list
     *     Lists all backups in all site environments.
     * @usage terminus backup-all:list --element=<element>
     *     Lists all <element> backups of all site environments.
     *
     * @field-labels
     *     file: Filename
     *     size: Size
     *     date: Date
     *     initiator: Initiator
     * @return RowsOfFields
     */
    public function listBackups($options = ['env' => 'all', 'element' => 'all', 'date' => null,])
    {
        $rows = [];
        $element = $options['element'];
        $sites = $this->sites->serialize();
        foreach ($sites as $site) {
            if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                foreach ($environments as $environment) {
                    if ($environment['initialized'] == 'true') {
                        $show = ($options['env'] == 'all') ? true : ($environment['id'] == $options['env']);
                        if ($show) {
                            $site_env = $site['name'] . '.' . $environment['id'];
                            list(, $env) = $this->getSiteEnv($site_env, 'dev');

                            switch ($element) {
                                case 'all':
                                    $backup_element = null;
                                    break;
                                case 'db':
                                    $backup_element = 'database';
                                    break;
                                default:
                                    $backup_element = $element;
                            }

                            $backups = $env->getBackups()->getFinishedBackups($backup_element);

                            foreach ($backups as $backup) {
                                $rows[] = $backup->serialize();
                            }
                        }
                    }
                }
            }
        }

        if (!empty($rows)) {
            if (isset($options['date'])) {
                $new_rows = [];
                foreach ($rows as $row) {
                    if (strpos($row['date'], $options['date']) !== false) {
                        $new_rows[] = $row;
                    }
                }
                $rows = $new_rows;
            }
        }
        $this->log()->notice('You have {count} backups.', ['count' => count($rows),]);
        if (!empty($rows)) {
            return new RowsOfFields($rows);
        }
    }
}
