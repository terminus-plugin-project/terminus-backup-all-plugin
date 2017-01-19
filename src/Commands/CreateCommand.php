<?php

namespace TerminusPluginProject\TerminusBackupAll\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
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
     * Creates a backup of all site environments.
     *
     * @authorize
     *
     * @command backup-all:create
     * @aliases ball:create
     *
     * @option string $env [dev|test|live] Environment to be backed up
     * @option string $element [code|files|database|db] Element to be backed up
     * @option integer $keep-for Retention period, in days, to retain backup
     *
     * @usage terminus backup-all:create
     *     Creates a backup of all elements in all site environments.
     * @usage terminus backup-all:create --env=<id>
     *     Creates a backup of all <id> environments only for all sites.
     * @usage terminus backup-all:create --element=<element>
     *     Creates a backup of all <element> elements only in all site environments.
     * @usage terminus backup-all:create --keep-for=<days>
     *     Creates a backup of all elements in all site environments and retains it for <days> days.
     * @usage terminus backup-all:create --element=<element> --keep-for=<days>
     *     Creates a backup of all <element> elements only in all site environments and retains it for <days> days.
     */
    public function create($options = ['env' => 'all', 'element' => null, 'keep-for' => 365,])
    {
        $count = 0;
        $sites = $this->sites->serialize();
        foreach ($sites as $site) {
            if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                foreach ($environments as $environment) {
                    if ($environment['initialized'] == 'true') {
                        $create = ($options['env'] == 'all') ? true : ($environment['id'] == $options['env']);
                        if ($create) {
                            $site_env = $site['name'] . '.' . $environment['id'];
                            list(, $env) = $this->getSiteEnv($site_env);
                            if (isset($options['element']) && ($options['element'] == 'db')) {
                                $options['element'] = 'database';
                            }
                            $env->getBackups()->create($options)->wait();
                            if (isset($options['element'])) {
                                $this->log()->notice('Created a backup all {element} elements in the {site_env} environment.', [
                                    'element'  => $options['element'],
                                    'site_env' => $site_env,
                                ]);
                            } else {
                                $this->log()->notice('Created a backup of the {site_env} environment.', [
                                    'site_env' => $site_env,
                                ]);
                            }
                            $count += 1;
                        }
                    }
                }
            }
        }
        $this->log()->notice('{count} backups created.', ['count' => $count,]);
    }
}
