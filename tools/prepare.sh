#!/bin/bash

cd "$(dirname "$0")/.."
source tools/common.sh

test -d build/generated-classes || exec_cmd mkdir -p build/generated-classes

exec_cmd php composer.phar install
exec_cmd vendor/bin/propel model:build
exec_cmd vendor/bin/propel config:convert
exec_cmd php composer.phar dump-autoload
exec_cmd php tools/GenerateApiSchema.php
print_message 'done.'
