# Terminus Backup All Plugin

[![Terminus v1.x Compatible](https://img.shields.io/badge/terminus-v1.x-green.svg)](https://github.com/terminus-plugin-project/terminus-backup-all-plugin/tree/1.x)
[![Terminus v0.x Compatible](https://img.shields.io/badge/terminus-v0.x-green.svg)](https://github.com/terminus-plugin-project/terminus-backup-all-plugin/tree/0.x)

Terminus plugin to backup all available Pantheon sites with one command.

## Usage:
```
$ terminus backup-all:[create|get|list] [--env=<id>] [--element=<element>] [--date=<YYYY-MM-DD>] [--changes=<change>]
```
The associative arguments are all optional.

The **--env** argument value filters by environment and defaults to **all**.  Valid values also include **dev, test, live** or any valid multi-site environment.

The **--element** argument value filters by element and defaults to **all**.  Valid values also include **code, database or files**.

The **--date** argument value filters by a specified date and returns the backups for any date if omitted.

The **--changes** argument is only necessary when the environment is in sftp connection mode and decides how to handle pending filesystem changes.  Valid values include **commit, ignore or skip** and the default is **commit** which will create an automatic commit of any pending filesystem changes before completing the backup.  The difference between **ignore** and **skip** is **ignore** will continue and make the backup anyway *(without pending filesystem changes)*, whereas **skip** will not.

## Examples:
```
$ terminus ball:create
```
This is an alias for the `terminus backup-all:create` command and will backup all elements of all environments for all available sites and perform the backup after committing pending filesystem changes
```
$ terminus ball:create --env=dev --element=code --changes=ignore
```
Backup the code only of the dev environment only for all available sites and perform the backup without committing pending filesystem changes
```
$ terminus ball:list
```
This is an alias for the `terminus backup-all:list` command and will list the backups of all elements in all available site environments
```
$ terminus ball:list --env=dev
```
List the backups of all elements in the dev environment only of all available sites
```
$ terminus ball:list --element=code
```
List the backups of the code only for all available site environments
```
$ terminus ball:list --date=YYYY-MM-DD
```
List the backups for all available site environments on the specified date
```
$ terminus ball:get
```
This is an alias for the `terminus backup-all:get` command and will retrieve the latest files backup for all available site environments
```
$ terminus ball:get --element=db
```
Retrieve the latest database backup for all available site environments
```
$ terminus ball:get --env=dev --element=code --date=YYYY-MM-DD
```
Retrieve the latest code backup of the dev environment only for all available sites on the specified date

## Installation:
For installation help, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/).

```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins terminus-plugin-project/terminus-backup-all-plugin:~1
```

## Configuration:

This plugin requires no configuration to use.

## Help:
Run `terminus help ball:[create|get|list]` for help.
