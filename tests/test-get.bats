#!/usr/bin/env bats

#
# test-get.bats
#
# Test plugin 'get' command
#

TODAY=$(date --date="tomorrow" +%Y-%m-%d)

@test "output of plugin 'get' command" {
  run terminus backup-all:get --name=$TERMINUS_SITE --date=$TODAY
  [[ "$output" == *"${TERMINUS_SITE}"* ]]
  [ "$status" -eq 0 ]
}
