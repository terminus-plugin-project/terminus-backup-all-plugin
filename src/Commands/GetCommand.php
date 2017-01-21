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
     * @option flag $team Team-only filter
     * @option string $owner Owner filter; "me" or user UUID
     * @option string $org Organization filter; "all" or organization UUID
     * @option string $name Name filter
     * @throws TerminusNotFoundException
     *
     * @usage terminus backup-all:get
     *     Displays the URL for the most recent files backup of all site environments.
     * @usage terminus backup-all:get --element=code
     *     Displays the URL for the most recent code backup of all site environments.
     * @usage terminus backup-all:get --team
     *     Displays the URL for the most recent files backups of which the currently logged-in user is a member of the team.
     * @usage terminus backup-all:get --owner=<user>
     *     Displays the URL for the most recent files backups owned by the user with UUID <user>.
     * @usage terminus backup-all:get --owner=me
     *     Displays the URL for the most recent files backups owned by the currently logged-in user.
     * @usage terminus backup-all:get --org=<org>
     *     Displays the URL for the most recent files backups associated with the <org> organization.
     * @usage terminus backup-all:get --org=all
     *     Displays the URL for the most recent files backups associated with any organization of which the currently logged-in is a member.
     * @usage terminus backup-all:get --name=<regex>
     *     Displays the URL for the most recent files backups with a name that matches <regex>.
     *
     * @field-labels
     *     url: URL
     * @return RowsOfFields
     */
    public function getBackup(array $options = ['env' => null, 'element' => null, 'date' => null, 'team' => false, 'owner' => null, 'org' => null, 'name' => null,])
    {
        // Filter sites, if necessary.
        $this->sites()->fetch(
            [
                'org_id' => isset($options['org']) ? $options['org'] : null,
                'team_only' => isset($options['team']) ? $options['team'] : false,
            ]
        );

        if (isset($options['name']) && !is_null($name = $options['name'])) {
            $this->sites->filterByName($name);
        }
        if (isset($options['owner']) && !is_null($owner = $options['owner'])) {
            if ($owner == 'me') {
                $owner = $this->session()->getUser()->id;
            }
            $this->sites->filterByOwner($owner);
        }

        $sites = $this->sites->serialize();

        if (empty($sites)) {
            $this->log()->notice('You have no sites.');
        }

        $rows = [];
        foreach ($sites as $site) {
            if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                foreach ($environments as $environment) {
                    if ($environment['initialized'] == 'true') {
                        $show = !isset($options['env']) ? true : ($environment['id'] == $options['env']);
                        if ($show) {
                            $site_env = $site['name'] . '.' . $environment['id'];
                            list(, $env) = $this->getSiteEnv($site_env);

                            if (isset($options['element'])) {
                                $element = ($options['element'] == 'db') ? 'database' : $options['element'];
                                $elements = [$element];
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
