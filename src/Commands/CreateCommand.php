<?php

namespace TerminusPluginProject\TerminusBackupAll\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CreateCommand
 * @package TerminusPluginProject\TerminusBackupAll\Commands
 */
class CreateCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Creates backups of all site environments.
     *
     * @authorize
     *
     * @command backup-all:create
     * @aliases ball:create
     *
     * @option string $env [dev|test|live] Environment to be backed up
     * @option string $element [code|files|database|db] Element to be backed up
     * @option string $skip Comma separated list of elements, entire environments or specific site environments to omit from backups
     * @option string $changes [commit|skip|ignore] Determine how to handle pending filesystem changes
     * @option integer $keep-for Retention period, in days, to retain backup
     * @option flag $team Team-only filter
     * @option string $owner Owner filter; "me" or user UUID
     * @option string $org Organization filter; "all" or organization UUID
     * @option string $name Name filter
     * @option boolean $async Async Requests
     *
     * @usage terminus backup-all:create
     *     Creates a backup of all elements in all site environments and automatically commits any pending filesystem changes.
     * @usage terminus backup-all:create --env=<id>
     *     Creates a backup of all <id> environments only for all sites and automatically commits any pending filesystem changes.
     * @usage terminus backup-all:create --element=<element>
     *     Creates a backup of <element> elements only in all site environments and automatically commits any pending filesystem changes.
     * @usage terminus backup-all:create --skip=<element1|id1|site.env1,element2|id2|site.env2,etc.>
     *     Creates a backup only in site environments that do not skip elements, unique environments or specific site environments and automatically commits any pending filesystem changes.
     * @usage terminus backup-all:create --changes=<change>
     *     Creates a backup of all elements in all site environments and determines how to handle pending filesystem changes.
     * @usage terminus backup-all:create --keep-for=<days>
     *     Creates a backup of all elements in all site environments, automatically commits any pending filesystem changes and retains it for <days> days.
     * @usage terminus backup-all:create --element=<element> --keep-for=<days>
     *     Creates a backup of <element> elements only in all site environments, automatically commits any pending filesystem changes and retains it for <days> days.
     * @usage terminus backup-all:create --team
     *     Creates a backup of all elements in all site environments of which the currently logged-in user is a member of the team.
     * @usage terminus backup-all:create --owner=<user>
     *     Creates a backup of all elements in all site environments owned by the user with UUID <user>.
     * @usage terminus backup-all:create --owner=me
     *     Creates a backup of all elements in all site environments owned by the currently logged-in user.
     * @usage terminus backup-all:create --org=<org>
     *     Creates a backup of all elements in all site environments associated with the <org> organization.
     * @usage terminus backup-all:create --org=all
     *     Creates a backup of all elements in all site environments associated with any organization of which the currently logged-in is a member.
     * @usage terminus backup-all:create --name=<regex>
     *     Creates a backup of all elements in all site environments with a name that matches <regex>.
     * @usage terminus backup-all:create --async
     *     Creates a backup of all elements but does not wait for backup to finish request is done asynchronous.
     */
    public function create($options = ['env' => null, 'element' => null, 'skip' => null, 'changes' => 'commit', 'keep-for' => 365, 'team' => false, 'owner' => null, 'org' => null, 'name' => null, 'async' => false])
    {
        $async = $options['async'];
        // Validate the --element options value.
        $elements = ['code', 'database', 'files',];
        if (isset($options['element'])) {
            $element = $options['element'];
            if ($element == 'db') {
                $element = 'database';
            }
            if (!in_array($element, $elements)) {
                $message = 'Invalid --element option value.  Allowed values are code, database or files.';
                throw new TerminusNotFoundException($message);
            }
            $elements = [$element];
        }

        // Validate the --changes options value.
        $change = $options['changes'];
        $changes = ['commit', 'ignore', 'skip',];
        if (!in_array($change, $changes)) {
            $message = 'Invalid --changes option value.  Allowed values are commit, ignore or skip.';
            throw new TerminusNotFoundException($message);
        }

        // Get a list of items to omit from backups.
        $skips = [];
        if (isset($options['skip'])) {
            $skips = explode(',', $options['skip']);
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

        $count = 0;
        foreach ($sites as $site) {
            if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                foreach ($environments as $environment) {
                    if ($environment['initialized'] == 'true') {
                        $process = !isset($options['env']) ? true : ($environment['id'] == $options['env']);
                        if ($process) {
                            $options['env'] = $environment['id'];
                            foreach ($elements as $element) {
                                $check = false;
                                $backup = true;
                                $options['element'] = $element;
                                $site_env = $site['name'] . '.' . $environment['id'];
                                list(, $env) = $this->getSiteEnv($site_env);
                                if (in_array($element, $skips)) {
                                    $backup = false;
                                }
                                if (in_array($environment['id'], $skips)) {
                                    $backup = false;
                                }
                                if (in_array($site_env, $skips)) {
                                    $backup = false;
                                }
                                if (!in_array($environment['id'], ['test', 'live',])) {
                                    $check = ($element == 'code');
                                }
                                if ($backup && $check) {
                                    $mode = $env->get('connection_mode');
                                    if ($mode == 'sftp') {
                                        $diff = (array)$env->diffstat();
                                        if (!empty($diff)) {
                                            switch ($change) {
                                                case 'commit':
                                                    $message = 'Automatic backup commit of pending filesystem changes.';
                                                    $workflow = $env->commitChanges($message);
                                                    $message = 'Automatic backup commit of pending filesystem changes pushed for {site_env}.';
                                                    $this->log()->notice($message, ['site_env' => $site_env,]);
                                                    break;

                                                case 'ignore':
                                                    $message = 'Automatic backup commit ignored for {site_env}.';
                                                    $message .= ' Note there are still pending filesystem changes that';
                                                    $message .= ' will not be included in the backup.';
                                                    $this->log()->notice($message, ['site_env' => $site_env,]);
                                                    break;

                                                case 'skip':
                                                    $message = 'Automatic backup commit skipped for {site_env}.';
                                                    $message .= ' Note there are still pending filesystem changes and';
                                                    $message .= ' the backup has been aborted.';
                                                    $this->log()->notice($message, ['site_env' => $site_env,]);
                                                    $backup = false;
                                                    break;
                                            }
                                        }
                                    }
                                }
                                if ($backup) {
                                    if ($async) {
                                      $env->getBackups()->create($options);
                                      $message = 'Creating a backup of the {element} for {site_env}.';
                                    }else{
                                      $env->getBackups()->create($options)->wait();
                                      $message = 'Created a backup of the {element} for {site_env}.';
                                    }
                                    $this->log()->notice($message, [
                                        'element'  => $element,
                                        'site_env' => $site_env,
                                    ]);

                                    $count += 1;
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->log()->notice('{count} backups created.', ['count' => $count,]);
    }
}
