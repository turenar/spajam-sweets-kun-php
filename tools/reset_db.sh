#!/bin/bash

cd "$(dirname "$0")/.."
source tools/common.sh

exec_cmd tools/migrate_db.sh
exec_cmd vendor/bin/propel sql:build
exec_cmd vendor/bin/propel sql:insert
exec_cmd php batch/exec_sql.php < db/data.sql
print_message 'done.'
