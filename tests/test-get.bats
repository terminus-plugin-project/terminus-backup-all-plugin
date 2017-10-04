#!/usr/bin/env bats

#
# test-get.bats
#
# Test plugin 'get' command
#

YESTERDAY=$(date --date="-1 day" +%Y-%m-%d)
TOMORROW=$(date --date="+1 day" +%Y-%m-%d)

@test "output of plugin 'get' command" {
  run terminus backup-all:get --name=$TERMINUS_SITE --date=$YESTERDAY:$TOMORROW
  [[ "$output" == *"${TERMINUS_SITE}"* ]]
  [ "$status" -eq 0 ]
}
