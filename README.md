# Terminus Backup All Plugin

[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/terminus-plugin-project/terminus-backup-all-plugin/tree/1.x)
[![Terminus v0.x Compatible](https://img.shields.io/badge/terminus-v0.x-green.svg)](https://github.com/terminus-plugin-project/terminus-backup-all-plugin/tree/0.x)

Terminus plugin to backup all available Pantheon sites with one command.

## Usage:
```
$ terminus backup-all:[create|get|list] [--env=<id>] [--element=<element>] [--skip=<items>] [--date=<YYYY-MM-DD>] [--changes=<change>] [--team] [--owner=<user>] [--org=<org>] [--name=<regex>]
```
The associative arguments are all optional and the same filtering rules as the `terminus sites:list` command apply.

The **--env** option value filters by environment.  Valid values include **dev, test, live** or any valid multi-site environment.

The **--element** option value filters by element.  Valid values include **code, database or files**.

The **--skip** option value is a comma separated list of one or more elements, entire environments or specific site environments to omit from backups.

The **--date** option value filters by a specified date and returns the backups for any date if omitted.

The **--changes** option is only necessary when the environment is in sftp connection mode and decides how to handle pending filesystem changes.  Valid values include **commit, ignore or skip** and the default is **commit** which will create an automatic commit of any pending filesystem changes before completing the backup.  The difference between **ignore** and **skip** is **ignore** will continue and make the backup anyway *(without pending filesystem changes)*, whereas **skip** will not.

## Examples:
```
$ terminus ball:create
```
This is an alias for the `terminus backup-all:create` command and will backup all elements of all environments for all available sites and perform the backup after committing pending filesystem changes.
```
$ terminus ball:create --env=dev --element=code --changes=ignore --skip=test,my-experiment.dev
```
Backup the code only of the dev environment only for all available sites and perform the backup without committing pending filesystem changes, skipping all test environments and the specific site environment `my-experiment.dev`.
```
$ terminus ball:list
```
This is an alias for the `terminus backup-all:list` command and will list the backups of all elements in all available site environments.
```
$ terminus ball:list --env=dev
```
List the backups of all elements in the dev environment only of all available sites.
```
$ terminus ball:list --element=code
```
List the backups of the code only for all available site environments.
```
$ terminus ball:list --date=YYYY-MM-DD
```
List the backups for all available site environments on the specified date.
```
$ terminus ball:list --name=awesome --date=YYYY-MM-DD
```
List the backups for all available site environments on the specified date that contain awesome in the name.
```
$ terminus ball:get
```
This is an alias for the `terminus backup-all:get` command and will retrieve the latest files backup for all available site environments.
```
$ terminus ball:get --name=awesome
```
Retrieve the latest files backup for all available site environments that contain `awesome` in the name.
```
$ terminus ball:get --element=db
```
Retrieve the latest database backup for all available site environments.
```
$ terminus ball:get --env=dev --element=code --date=YYYY-MM-DD
```
Retrieve the latest code backup of the dev environment only for all available sites on the specified date.
```
$ terminus ball:get --name=awesome --date=YYYY-MM-DD
```
Retrieve the latest files backup for all available site environments on the specified date that contain `awesome` in the name.

## Installation:
For installation help, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/).

```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins terminus-plugin-project/terminus-backup-all-plugin:~1
```

## Configuration:

If you wish to automate backups, see the core `terminus backup:automatic` command.

## Help:
Run `terminus help ball:[create|get|list]` for help.
