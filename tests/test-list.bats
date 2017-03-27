#!/usr/bin/env bats

#
# test-list.bats
#
# Test plugin 'list' command
#

TODAY=$(date --date="tomorrow" +%Y-%m-%d)

@test "output of plugin 'list' command" {
  run terminus backup-all:list --name=$TERMINUS_SITE --date=$TODAY
  [[ "$output" == *"${TERMINUS_SITE}"* ]]
  [ "$status" -eq 0 ]
}
