#!/usr/bin/env bats

#
# test-list.bats
#
# Test plugin 'list' command
#

UTC_DATE=$(date --date='TZ="Etc/UTC"' +%Y-%m-%d)

@test "output of plugin 'list' command" {
  run terminus backup-all:list --name=$TERMINUS_SITE --date=$UTC_DATE
  [[ "$output" == *"${TERMINUS_SITE}"* ]]
  [ "$status" -eq 0 ]
}
