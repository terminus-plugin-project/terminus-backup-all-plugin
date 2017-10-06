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
     * @option string $date YYYY-MM-DD[:YYYY-MM-DD] Backup date (or colon separated range) filter
     * @option flag $team Team-only filter
     * @option string $owner Owner filter; "me" or user UUID
     * @option string $org Organization filter; "all" or organization UUID
     * @option string $name Name filter
     *
     * @usage terminus backup-all:list
     *     Lists all backups in all site environments. Separate multiple environments in a comma delimited list.
     * @usage terminus backup-all:list --env=<id>
     *     Lists all backups of all <id> site environments. Separate multiple environments in a comma delimited list.
     * @usage terminus backup-all:list --element=<element>
     *     Lists all <element> backups of all site environments. Separate multiple elements in a comma delimited list.
     * @usage terminus backup-all:list --team
     *     Lists all backups of which the currently logged-in user is a member of the team.
     * @usage terminus backup-all:list --owner=<user>
     *     Lists all backups owned by the user with UUID <user>.
     * @usage terminus backup-all:list --owner=me
     *     Lists all backups owned by the currently logged-in user.
     * @usage terminus backup-all:list --org=<org>
     *     Lists all backups associated with the <org> organization.
     * @usage terminus backup-all:list --org=all
     *     Lists all backups associated with any organization of which the currently logged-in is a member.
     * @usage terminus backup-all:list --name=<regex>
     *     Lists all backups with a name that matches <regex>.
     *
     * @field-labels
     *     file: Filename
     *     size: Size
     *     date: Date
     *     initiator: Initiator
     * @return RowsOfFields
     */
    public function listBackups($options = ['env' => null, 'element' => null, 'date' => null, 'team' => false, 'owner' => null, 'org' => null, 'name' => null,])
    {
        // Validate the --element option values.
        $valid_elements = ['code', 'database', 'files',];
        if (isset($options['element'])) {
            $elements = explode(',', $options['element']);
            foreach ($elements as $element) {
                if ($element == 'db') {
                    $element = 'database';
                }
                if (!in_array($element, $valid_elements)) {
                    $message = "Invalid --element option value '$element'.  Allowed values are code, database or files.";
                    throw new TerminusNotFoundException($message);
                }
            }
        } else {
            $elements = $valid_elements;
        }

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

        $envs = array();
        if (isset($options['env'])) {
            // Build valid environments list.
            $valid_envs = array();
            foreach ($sites as $site) {
                if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                    foreach ($environments as $environment) {
                        if ($environment['initialized'] == 'true') {
                            if (!in_array($environment['id'], $valid_envs)) {
                                $valid_envs[] = $environment['id'];
                            }
                        }
                    }
                }
            }

            // Validate the --env option values.
            $envs = explode(',', $options['env']);
            $envs_list = implode(', ', $valid_envs);
            foreach ($envs as $env) {
                if (!in_array($env, $valid_envs)) {
                    $message = "Invalid --env option value '$env'.  Allowed values are $envs_list.";
                    throw new TerminusNotFoundException($message);
                }
            }
        }

        $rows = [];
        foreach ($sites as $site) {
            if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                foreach ($environments as $environment) {
                    if ($environment['initialized'] == 'true') {
                        $show = !isset($options['env']) ? true : in_array($environment['id'], $envs);
                        if ($show) {
                            $site_env = $site['name'] . '.' . $environment['id'];
                            list(, $env) = $this->getSiteEnv($site_env, 'dev');
                            foreach ($elements as $element) {
                                switch ($element) {
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
        }

        if (!empty($rows)) {
            if (isset($options['date'])) {
                $dates = explode(':', $options['date']);
                $lower = $dates[0];
                if (isset($dates[1])) {
                    $upper = $dates[1];
                    if ($lower > $upper) {
                        $date = $lower;
                        $lower = $upper;
                        $upper = $date;
                    }
                } else {
                    $upper = $lower;
                }
                $new_rows = [];
                foreach ($rows as $row) {
                    $row_date = date('Y-m-d', strtotime($row['date']));
                    if ($row_date >= $lower and $row_date <= $upper) {
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
