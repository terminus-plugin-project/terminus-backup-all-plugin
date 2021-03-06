#!/usr/bin/env bats

#
# test-create.bats
#
# Test plugin 'create' command
#

@test "output of plugin 'create' command" {
  run terminus backup-all:create --element=code --name=$TERMINUS_SITE
  [[ "$output" == *"${TERMINUS_SITE}"* ]]
  [ "$status" -eq 0 ]
}
