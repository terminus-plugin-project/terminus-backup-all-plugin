#!/usr/bin/env bats

#
# test-list.bats
#
# Test plugin 'list' command
#

YESTERDAY=$(date --date="-1 day" +%Y-%m-%d)
TOMORROW=$(date --date="+1 day" +%Y-%m-%d)

@test "output of plugin 'list' command" {
  run terminus backup-all:list --name=$TERMINUS_SITE --date=$YESTERDAY:$TOMORROW
  [[ "$output" == *"${TERMINUS_SITE}"* ]]
  [ "$status" -eq 0 ]
}
