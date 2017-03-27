#!/usr/bin/env bats

#
# test-get.bats
#
# Test plugin 'get' command
#

UTC_DATE=$(date --date='TZ="Etc/UTC"' +%Y-%m-%d)

@test "output of plugin 'get' command" {
  run terminus backup-all:get --name=$TERMINUS_SITE --date=$UTC_DATE
  [[ "$output" == *"${TERMINUS_SITE}"* ]]
  [ "$status" -eq 0 ]
}
