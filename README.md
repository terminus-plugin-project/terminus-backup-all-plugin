# Terminus Backup All Plugin

Version 2.x

[![CircleCI](https://circleci.com/gh/terminus-plugin-project/terminus-backup-all-plugin.svg?style=shield)](https://circleci.com/gh/terminus-plugin-project/terminus-backup-all-plugin)
[![Terminus v2.x Compatible](https://img.shields.io/badge/terminus-v2.x-green.svg)](https://github.com/terminus-plugin-project/terminus-backup-all-plugin/tree/2.x)
[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/terminus-plugin-project/terminus-backup-all-plugin/tree/1.x)
[![Terminus v0.x Compatible](https://img.shields.io/badge/terminus-v0.x-green.svg)](https://github.com/terminus-plugin-project/terminus-backup-all-plugin/tree/0.x)

Terminus plugin to backup all available Pantheon sites with one command.

## Usage:
```console
$ terminus backup-all:[create|get|list] [--env=<id>] [--element=<element>] [--framework=<framework>] [--skip=<items>] [--date=<YYYY-MM-DD>] [--changes=<change>] [--team] [--owner=<user>] [--org=<org>] [--name=<regex>] [--async]
```
The associative arguments are all optional and the same filtering rules as the `terminus site:list` command apply.

The **--env** option value filters by environment.  Valid values include **dev, test, live** or any valid multidev environment.

The **--element** option value filters by element.  Valid values include **code, database or files**.

The **--framework** option value filters by framework.  Valid values include **backdrop, drupal, drupal8 or wordpress**.

The **--skip** option value is a comma separated list of one or more elements, entire environments or specific site environments to omit from backups.

The **--date** option value filters by a specified date (or colon separated range) and returns the backups for any date if omitted.

The **--changes** option is only necessary when the environment is in sftp connection mode and decides how to handle pending filesystem changes.  Valid values include **commit, ignore or skip** and the default is **commit** which will create an automatic commit of any pending filesystem changes before completing the backup.  The difference between **ignore** and **skip** is **ignore** will continue and make the backup anyway *_(without pending filesystem changes)_*, whereas **skip** will not.

The **--async** option value will process the request asynchronously.

## Examples:
```console
$ terminus ball:create
```
This is an alias for the `terminus backup-all:create` command and will backup all elements of all environments for all available sites and perform the backup after committing pending filesystem changes.
```console
$ terminus ball:create --async
```
Same as above but process the request asynchronously.
```console
$ terminus ball:create --element=code --changes=ignore --skip=test,my-experiment.dev
```
Backup the code only of all environments for all available sites and perform the backup without committing pending filesystem changes, skipping all test environments and the specific site environment `my-experiment.dev`.
```console
$ terminus ball:create --framework=drupal,drupal8
```
Backup all elements of all environments for all available sites that include the drupal and drupal8 (Drupal 6, 7 and 8) frameworks and perform the backup after committing pending filesystem changes.
```console
$ terminus ball:list
```
This is an alias for the `terminus backup-all:list` command and will list the backups of all elements in all available site environments.
```console
$ terminus ball:list --env=dev
```
List the backups of all elements in the dev environment only of all available sites.
```console
$ terminus ball:list --element=code
```
List the backups of the code only for all available site environments.
```console
$ terminus ball:list --framework=drupal,drupal8
```
List the backups of all elements in all environments for all available sites that include the drupal and drupal8 (Drupal 6, 7 and 8) frameworks.
```console
$ terminus ball:list --date=YYYY-MM-DD
```
List the backups for all available site environments on the specified date.
```console
$ terminus ball:list --date=YYYY-MM-DD:YYYY-MM-DD
```
Same as above but within the specified colon separated date range.
```console
$ terminus ball:list --name=awesome --date=YYYY-MM-DD
```
List the backups for all available site environments on the specified date that contain `awesome` in the name.
```console
$ terminus ball:get
```
This is an alias for the `terminus backup-all:get` command and will retrieve the latest files backup for all available site environments.
```console
$ terminus ball:get --name=awesome
```
Retrieve the latest files backup for all available site environments that contain `awesome` in the name.
```console
$ terminus ball:get --element=db
```
Retrieve the latest database backup for all available site environments.
```console
$ terminus ball:get --framework=drupal,drupal8
```
Retrieve the latest files backup of all environments for all available sites that include the drupal and drupal8 (Drupal 6, 7 and 8) frameworks.
```console
$ terminus ball:get --env=dev --element=code --date=YYYY-MM-DD
```
Retrieve the latest code backup of the dev environment only for all available sites on the specified date.
```console
$ terminus ball:get --env=dev --element=code --date=YYYY-MM-DD:YYYY-MM-DD
```
Same as above but within the specified colon separated date range.
```console
$ terminus ball:get --name=awesome --date=YYYY-MM-DD
```
Retrieve the latest files backup for all available site environments on the specified date that contain `awesome` in the name.

## Installation:
For installation help, see [Extend with Plugins](https://pantheon.io/docs/terminus/plugins/).

```bash
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins terminus-plugin-project/terminus-backup-all-plugin:~2
```

## Testing:

Replace `my-test-site` with the site you want to test:
```bash
export TERMINUS_SITE=my-test-site
cd ~/.terminus/plugins/terminus-backup-all-plugin
composer install
composer test
```

## Configuration:
If you wish to automate backups, see the core `terminus backup:automatic` command.

## Help:
Run `terminus help ball:[create|get|list]` for help.
